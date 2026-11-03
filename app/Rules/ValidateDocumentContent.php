<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ZipArchive;

class ValidateDocumentContent implements ValidationRule
{
    public function __construct(private readonly array $extensions)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof \Illuminate\Http\UploadedFile || ! $value->isValid()) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if (! in_array($extension, $this->extensions, true) || ! $this->contentMatches($value->getRealPath(), $extension)) {
            $fail('The uploaded file is corrupt, incomplete, or does not match its file type.');
        }
    }

    private function contentMatches(string|false $path, string $extension): bool
    {
        if (! $path || ! is_file($path) || filesize($path) === 0) {
            return false;
        }

        return match ($extension) {
            'pdf' => $this->isCompletePdf($path),
            'jpg', 'jpeg', 'png', 'webp', 'gif' => $this->isDecodableImage($path),
            'docx' => $this->isOfficeArchive($path, 'word/document.xml'),
            'xlsx' => $this->isOfficeArchive($path, 'xl/workbook.xml'),
            default => false,
        };
    }

    private function isCompletePdf(string $path): bool
    {
        $size = filesize($path);
        if ($size === false || $size < 32) {
            return false;
        }

        $contents = file_get_contents($path);
        if ($contents === false || ! str_starts_with($contents, '%PDF-')) {
            return false;
        }

        $eofPosition = strrpos($contents, '%%EOF');
        $xrefPosition = strrpos($contents, 'startxref');

        return $eofPosition !== false
            && $xrefPosition !== false
            && $xrefPosition < $eofPosition
            && strlen($contents) - $eofPosition <= 2048;
    }

    private function isDecodableImage(string $path): bool
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if ($image === false) {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    private function isOfficeArchive(string $path, string $requiredEntry): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return false;
        }

        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            return false;
        }

        $isValid = $archive->locateName('[Content_Types].xml') !== false
            && $archive->locateName($requiredEntry) !== false;
        $archive->close();

        return $isValid;
    }
}
