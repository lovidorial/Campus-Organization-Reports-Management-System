<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}