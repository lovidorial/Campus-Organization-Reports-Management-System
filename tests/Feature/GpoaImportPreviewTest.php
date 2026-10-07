<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GpoaImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preview_requires_login_and_rejects_unsupported_extensions(): void
    {
        $route = route('gpoa.import-preview');
        $file = UploadedFile::fake()->create('approved-gpoa.pdf', 100, 'application/pdf');

        $this->postJson($route, [
            'file' => $file,
            'school_year' => '2026-2027',
        ])->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->postJson($route, [
                'file' => UploadedFile::fake()->create('approved-gpoa.pdf', 100, 'application/pdf'),
                'school_year' => '2026-2027',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_import_preview_returns_missing_column_warnings_and_titles_past_the_row_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray([['PROGRAM', 'CATEGORY', 'TIME FRAME']]);
        for ($index = 1; $index <= 35; $index++) {
            $category = $index === 1 ? 'sPoRtS' : ($index === 2 ? 'not a category' : 'Sports');
            $worksheet->fromArray([['Activity ' . $index, $category, 'Oct. 3']], null, 'A' . ($index + 1));
        }
        $path = tempnam(sys_get_temp_dir(), 'gpoa-preview-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $this->actingAs($admin)
                ->postJson(route('gpoa.import-preview'), [
                    'file' => new UploadedFile($path, 'GPOA-2026-2027.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                    'school_year' => '2026-2027',
                ])
                ->assertOk()
                ->assertJsonPath('rows.0.category', 'Sports')
                ->assertJsonPath('rows.1.category', null)
                ->assertJsonMissing(['Category not recognized: not a category'])
                ->assertJsonPath('skipped', 1)
                ->assertJsonPath('skipped_rows.0', 'Activity 35')
                ->assertJsonMissing(['Column not found: Source of Funds']);
        } finally {
            @unlink($path);
        }
    }
}