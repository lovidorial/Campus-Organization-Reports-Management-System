<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\OrganizationWorkflow;
use App\Models\User;
use App\Models\WorkflowEvent;
use App\Models\WorkflowSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class DocumentValidationService
{
    public function validateUpload(UploadedFile|string $file, string $documentType, array $context = []): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : Storage::disk('private')->path($file);
        $size = is_file($path) ? (int) ceil(filesize($path) / 1024) : 0;
        $rules = $this->rulesFor($documentType, $context);
        $notes = [];

        if (! is_file($path) || ! is_readable($path)) {
            return $this->result('invalid', ['File could not be read.']);
        }

        if ($size < 1 || $size > (int) ($rules['max_size_kb'] ?? config('document_validation.max_size_kb', 20480))) {
            return $this->result('invalid', [$size < 1 ? 'File is empty.' : 'File exceeds the maximum allowed size.']);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        if (strtolower(pathinfo($file instanceof UploadedFile ? $file->getClientOriginalName() : $path, PATHINFO_EXTENSION)) === 'pdf'
            && file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            return $this->result('invalid', ['File is corrupted or unreadable.']);
        }
        if (! in_array($mime, $rules['mimes'] ?? ['application/pdf'], true)) {
            return $this->result('invalid', ['Unsupported file type.']);
        }

        try {
            if ($mime === 'application/pdf') {
                $parser = new Parser();
                $text = trim($parser->parseFile($path)->getText());
            } else {
                $text = '';
            }
        } catch (Throwable) {
            return $this->result('invalid', ['File is corrupted or unreadable.']);
        }

        if ($text === '') {
            $ocrText = $this->extractWithOcr($path, $mime);
            if ($ocrText === null) {
                return $this->result('needs_review', ['Text could not be extracted; OCR is unavailable.']);
            }
            $text = trim($ocrText);
        }

        if ($text === '') {
            return $this->result('invalid', ['Document is blank.']);
        }

        $normalizedText = $this->normalize($text);
        $manualReview = false;
        if (($rules['require_non_blank'] ?? true) && trim($normalizedText) === '') {
            $notes[] = 'Document is blank.';
        }

        $organizationName = $context['organization_name'] ?? null;
        if (($rules['require_organization_name'] ?? false) && ! filled($organizationName)) {
            $manualReview = true;
            $notes[] = 'Organization name unavailable; manual review required.';
        } elseif (($rules['require_organization_name'] ?? false)
            && ! str_contains($normalizedText, $this->normalize($organizationName))) {
            $notes[] = 'Organization name not found.';
        }

        if (($rules['require_activity_match'] ?? false)) {
            $titleMatch = filled($context['activity_title'] ?? null)
                && str_contains($normalizedText, $this->normalize($context['activity_title']));
            $dateMatch = filled($context['activity_date'] ?? null)
                && $this->containsDate($normalizedText, (string) $context['activity_date']);
            if (! $titleMatch && ! $dateMatch) {
                $notes[] = 'Activity title or date not found.';
            }
        }

        if (($rules['require_date'] ?? false) && ! preg_match('/\b(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}|(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(e)?|jul(y)?|aug(ust)?|sep(t(ember)?)?|oct(ober)?|nov(ember)?|dec(ember)?)\s+\d{1,2},?\s+\d{4})\b/i', $text)) {
            $notes[] = 'No date found.';
        }

        if (($rules['require_signature_indicator'] ?? false)
            && ! preg_match('/\b(signature|signed\s+by|approved\s+by|authorized\s+by|attested\s+by)\b/i', $text)) {
            $notes[] = 'Signature or approved-by indicator not found.';
        }

        foreach ($rules['required_keywords'] ?? [] as $keyword) {
            if (! str_contains($normalizedText, $this->normalize((string) $keyword))) {
                $notes[] = 'Required keyword not found: ' . $keyword . '.';
            }
        }

        return $this->result($notes === [] ? 'valid' : ($manualReview ? 'needs_review' : 'invalid'), $notes);
    }

    public function validateAndStore(UploadedFile|string $file, string $documentType, array $context, Model $record): array
    {
        $result = $this->validateUpload($file, $documentType, $context);
        $record->forceFill([
            'validation_status' => $result['status'],
            'validation_notes' => implode("\n", $result['notes']),
            'validated_at' => now(),
        ])->saveQuietly();

        $this->logValidation($record, $documentType, $result);

        return $result;
    }

    public function logValidation(Model $record, string $documentType, array $result): void
    {
        $this->logWorkflowEvent($record, $documentType, $result);
    }

    private function rulesFor(string $documentType, array $context): array
    {
        $rules = config('document_validation.types.' . $documentType, []);
        if ($documentType === 'workflow_submissions') {
            $rules = array_replace(
                config('document_validation.types.workflow_submissions.default', []),
                config('document_validation.types.workflow_submissions.' . ($context['workflow_document_type'] ?? 'default'), [])
            );
        }

        return $rules;
    }

    protected function extractWithOcr(string $path, string $mime): ?string
    {
        $ocrClass = 'thiagoalessio\\TesseractOCR\\TesseractOCR';
        if (! class_exists($ocrClass)) {
            return null;
        }
        $tesseractPath = (new ExecutableFinder())->find('tesseract');
        if ($tesseractPath === null) {
            return null;
        }

        $files = [$path];
        $temporaryFiles = [];
        if ($mime === 'application/pdf') {
            $imagickClass = 'Imagick';
            if (! class_exists($imagickClass)) {
                return null;
            }

            try {
                $image = new $imagickClass();
                $image->setResolution(180, 180);
                $image->readImage($path . '[0]');
                $temporary = tempnam(sys_get_temp_dir(), 'orgtrack-ocr-') . '.png';
                $image->setImageFormat('png');
                $image->writeImage($temporary);
                $image->clear();
                $files = [$temporary];
                $temporaryFiles[] = $temporary;
            } catch (Throwable) {
                return null;
            }
        }

        try {
            $text = '';
            foreach ($files as $imagePath) {
                $ocr = new $ocrClass($imagePath);
                $text .= $ocr->executable($tesseractPath)->run() . "\n";
            }

            return $text;
        } catch (Throwable) {
            return null;
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                @unlink($temporaryFile);
            }
        }
    }

    private function logWorkflowEvent(Model $record, string $documentType, array $result): void
    {
        $workflow = null;
        $submissionId = null;

        if ($record instanceof WorkflowSubmission) {
            $workflow = $record->workflow;
            $submissionId = $record->id;
        } elseif ($record instanceof Gpoa) {
            $workflow = OrganizationWorkflow::firstOrCreate([
                'user_id' => $record->user_id,
                'term' => $record->term,
                'school_year' => $record->school_year,
            ]);
        } elseif ($record instanceof ActivityRequest) {
            $gpoa = Gpoa::find($record->gpoa_id)
                ?? $record->gpoaActivity?->gpoa;
            $owner = User::find($record->user_id);
            [$term, $schoolYear] = $gpoa
                ? [$gpoa->term, $gpoa->school_year]
                : ($owner?->currentPeriod() ?? [now()->format('Y') . ' Term', now()->format('Y') . '-' . now()->addYear()->format('Y')]);
            $workflow = OrganizationWorkflow::firstOrCreate([
                'user_id' => $record->user_id,
                'term' => $term,
                'school_year' => $schoolYear,
            ]);
        } else {
            $workflowId = data_get($record, 'organization_workflow_id');
            $workflow = $workflowId ? OrganizationWorkflow::find($workflowId) : null;
        }

        if (! $workflow) {
            return;
        }

        WorkflowEvent::create([
            'organization_workflow_id' => $workflow->id,
            'workflow_submission_id' => $submissionId,
            'user_id' => auth()->id(),
            'event_type' => 'document_validated',
            'description' => ucfirst(str_replace('_', ' ', $documentType)) . ' validation: ' . $result['status'],
            'metadata' => ['status' => $result['status'], 'notes' => $result['notes']],
        ]);
    }

    private function containsDate(string $text, string $date): bool
    {
        try {
            $parsedDate = \Illuminate\Support\Carbon::parse($date);
        } catch (Throwable) {
            return false;
        }

        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y', 'F j, Y', 'M j, Y', 'F d, Y', 'M d, Y'] as $format) {
            if (str_contains($text, $this->normalize($parsedDate->format($format)))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->toString();
    }

    private function result(string $status, array $notes): array
    {
        return ['status' => $status, 'notes' => array_values(array_unique($notes))];
    }
}