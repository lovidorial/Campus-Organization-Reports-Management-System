<?php

namespace Tests\Unit;

use App\Services\DocumentValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentValidationServiceTest extends TestCase
{
    public function test_valid_communication_letter_passes_all_configured_checks(): void
    {
        $service = new DocumentValidationService();
        $file = $this->pdf('Organization: Campus Council<br>Activity: Leadership Seminar<br>Date: October 28, 2026<br>Signed by: President');

        $result = $service->validateUpload($file, 'communication_letter', [
            'organization_name' => 'Campus Council',
            'activity_title' => 'Leadership Seminar',
            'activity_date' => '2026-10-28',
        ]);

        $this->assertSame('valid', $result['status']);
        $this->assertSame([], $result['notes']);
    }

    public function test_invalid_communication_letter_reports_exact_missing_reasons(): void
    {
        $service = new DocumentValidationService();
        $file = $this->pdf('Activity: Leadership Seminar<br>Signed by: President');

        $result = $service->validateUpload($file, 'communication_letter', [
            'organization_name' => 'Campus Council',
            'activity_title' => 'Leadership Seminar',
            'activity_date' => '2026-10-28',
        ]);

        $this->assertSame('invalid', $result['status']);
        $this->assertContains('Organization name not found.', $result['notes']);
        $this->assertContains('No date found.', $result['notes']);
    }

    public function test_wrong_organization_is_invalid(): void
    {
        $service = new DocumentValidationService();
        $file = $this->pdf('Other Organization<br>Activity: Leadership Seminar<br>Date: October 28, 2026<br>Approved by: Dean');

        $result = $service->validateUpload($file, 'communication_letter', [
            'organization_name' => 'Campus Council',
            'activity_title' => 'Leadership Seminar',
            'activity_date' => '2026-10-28',
        ]);

        $this->assertSame('invalid', $result['status']);
        $this->assertContains('Organization name not found.', $result['notes']);
    }

    public function test_corrupt_pdf_is_invalid(): void
    {
        $service = new DocumentValidationService();
        $file = UploadedFile::fake()->createWithContent('corrupt.pdf', 'not a PDF');

        $result = $service->validateUpload($file, 'communication_letter', [
            'organization_name' => 'Campus Council',
            'activity_title' => 'Leadership Seminar',
            'activity_date' => '2026-10-28',
        ]);

        $this->assertSame('invalid', $result['status']);
        $this->assertContains('File is corrupted or unreadable.', $result['notes']);
    }

    public function test_scanned_pdf_without_ocr_text_is_marked_for_review(): void
    {
        $service = new class extends DocumentValidationService
        {
            protected function extractWithOcr(string $path, string $mime): ?string
            {
                return null;
            }
        };
        $file = $this->pdf('');

        $result = $service->validateUpload($file, 'communication_letter', []);

        $this->assertSame('needs_review', $result['status']);
        $this->assertContains('Text could not be extracted; OCR is unavailable.', $result['notes']);
    }

    public function test_ocr_unavailable_fallback_does_not_throw(): void
    {
        $service = new class extends DocumentValidationService
        {
            protected function extractWithOcr(string $path, string $mime): ?string
            {
                return null;
            }
        };
        $file = $this->pdf('');

        $this->assertSame('needs_review', $service->validateUpload($file, 'communication_letter')['status']);
    }

    public function test_scanned_pdf_can_validate_when_ocr_returns_readable_text(): void
    {
        $service = new class extends DocumentValidationService
        {
            protected function extractWithOcr(string $path, string $mime): ?string
            {
                return 'Campus Council Leadership Seminar October 28, 2026 Signed by President';
            }
        };
        $file = $this->pdf('');

        $result = $service->validateUpload($file, 'communication_letter', [
            'organization_name' => 'Campus Council',
            'activity_title' => 'Leadership Seminar',
            'activity_date' => '2026-10-28',
        ]);

        $this->assertSame('valid', $result['status']);
        $this->assertSame([], $result['notes']);
    }

    private function pdf(string $html): UploadedFile
    {
        $pdf = Pdf::loadHTML('<html><body>' . $html . '</body></html>')->output();

        return UploadedFile::fake()->createWithContent('communication-letter.pdf', $pdf);
    }
}