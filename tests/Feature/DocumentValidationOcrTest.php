<?php

namespace Tests\Feature;

use App\Services\DocumentValidationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentValidationOcrTest extends TestCase
{
    public function test_text_based_pdfs_use_the_distinct_existing_document_rules(): void
    {
        $service = new DocumentValidationService();
        $file = $this->pdf('Campus Organization Student Leadership Seminar March 10, 2025 Signed by President');

        $letter = $service->validateUpload($file, 'communication_letter', $this->letterContext());
        $narrative = $service->validateUpload($file, 'narrative_report');

        $this->assertSame('valid', $letter['status']);
        $this->assertSame('valid', $narrative['status']);

        $letterWithoutOrganization = $service->validateUpload(
            $this->pdf('Student Leadership Seminar March 10, 2025 Signed by President'),
            'communication_letter',
            $this->letterContext()
        );
        $this->assertSame('invalid', $letterWithoutOrganization['status']);
        $this->assertContains('Organization name not found.', $letterWithoutOrganization['notes']);
        $this->assertSame('valid', $service->validateUpload($this->pdf('A long narrative document without letter signature rules.'), 'narrative_report')['status']);
    }

    public function test_single_and_multiple_page_scanned_pdfs_run_ocr_for_both_document_types(): void
    {
        foreach (['communication_letter', 'narrative_report'] as $documentType) {
            $singlePageService = new TestableDocumentValidationService([
                'Campus Organization Student Leadership Seminar March 10, 2025 Signed by President',
            ]);
            $singlePageResult = $singlePageService->validateUpload($this->blankPdf(), $documentType, $this->letterContext());
            $this->assertSame('valid', $singlePageResult['status'], $documentType);

            $multiPageService = new TestableDocumentValidationService([
                'Campus Organization Student Leadership Seminar',
                'March 10, 2025 Signed by President. The organization hosted the activity successfully.',
            ]);
            $multiPageResult = $multiPageService->validateUpload($this->blankPdf(), $documentType, $this->letterContext());
            $this->assertSame('valid', $multiPageResult['status'], $documentType);
            $this->assertCount(2, $multiPageService->renderedPages, $documentType);
            $this->assertFalse(is_dir($multiPageService->temporaryDirectory), 'OCR page images should be removed after validation.');
        }
    }

    public function test_blurry_or_insufficient_ocr_text_is_kept_for_manual_review_for_both_types(): void
    {
        foreach (['communication_letter', 'narrative_report'] as $documentType) {
            $service = new TestableDocumentValidationService(['C*mpus ??']);
            $result = $service->validateUpload($this->blankPdf(), $documentType, $this->letterContext());

            $this->assertSame('needs_review', $result['status'], $documentType);
            $this->assertStringContainsString('extract enough readable text', implode(' ', $result['notes']));
        }
    }

    public function test_ocr_unavailable_keeps_scanned_documents_for_manual_review_for_both_types(): void
    {
        foreach (['communication_letter', 'narrative_report'] as $documentType) {
            $service = new TestableDocumentValidationService([], false);
            $result = $service->validateUpload($this->blankPdf(), $documentType, $this->letterContext());

            $this->assertSame('needs_review', $result['status'], $documentType);
            $this->assertStringContainsString('OCR is unavailable', implode(' ', $result['notes']));
            $this->assertStringNotContainsString(sys_get_temp_dir(), implode(' ', $result['notes']));
        }
    }

    public function test_corrupt_or_encrypted_pdf_extraction_failure_is_needs_review_for_both_types(): void
    {
        foreach (['communication_letter', 'narrative_report'] as $documentType) {
            $service = new TestableDocumentValidationService([], false);
            $result = $service->validateUpload(
                UploadedFile::fake()->createWithContent('unreadable.pdf', "%PDF-1.7\n" . str_repeat("\0", 80)),
                $documentType,
                $this->letterContext()
            );

            $this->assertSame('needs_review', $result['status'], $documentType);
            $this->assertStringContainsString('extraction failed', implode(' ', $result['notes']));
        }
    }

    private function pdf(string $text): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'document.pdf',
            Pdf::loadHTML('<html><body>' . e($text) . '</body></html>')->output()
        );
    }

    private function blankPdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'scanned.pdf',
            Pdf::loadHTML('<html><body></body></html>')->output()
        );
    }

    private function letterContext(): array
    {
        return [
            'organization_name' => 'Campus Organization',
            'activity_title' => 'Student Leadership Seminar',
            'activity_date' => '2025-03-10',
        ];
    }
}

class TestableDocumentValidationService extends DocumentValidationService
{
    public array $renderedPages = [];
    public ?string $temporaryDirectory = null;

    public function __construct(private readonly array $pageTexts, private readonly bool $ocrAvailable = true)
    {
    }

    protected function tesseractWrapperAvailable(): bool
    {
        return $this->ocrAvailable;
    }

    protected function findTesseractExecutable(): ?string
    {
        return $this->ocrAvailable ? 'test-tesseract' : null;
    }

    protected function pdfRendererAvailable(): bool
    {
        return true;
    }

    protected function renderPdfPages(string $path, string $temporaryDirectory): ?array
    {
        $this->temporaryDirectory = $temporaryDirectory;
        foreach ($this->pageTexts as $index => $text) {
            $imagePath = $temporaryDirectory . DIRECTORY_SEPARATOR . sprintf('page-%04d.png', $index + 1);
            file_put_contents($imagePath, 'mock rendered page');
            $this->renderedPages[$imagePath] = $text;
        }

        return array_keys($this->renderedPages);
    }

    protected function recognizeImage(string $imagePath, string $tesseractPath, string $ocrClass): string
    {
        return $this->renderedPages[$imagePath] ?? '';
    }
}
