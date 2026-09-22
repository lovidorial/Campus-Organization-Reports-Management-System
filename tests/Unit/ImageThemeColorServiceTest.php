<?php

namespace Tests\Unit;

use App\Services\ImageThemeColorService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageThemeColorServiceTest extends TestCase
{
    public function test_light_image_is_darkened_for_white_text(): void
    {
        $this->assertSame('#b4b4b4', $this->extractColor(255, 255, 255));
    }

    public function test_dark_image_keeps_its_average_color(): void
    {
        $this->assertSame('#101d2a', $this->extractColor(20, 30, 40));
    }

    public function test_near_white_background_is_excluded_from_logo_color(): void
    {
        $this->assertSame('#d92e2e', $this->extractPattern(function ($image): void {
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            imagefilledrectangle($image, 8, 8, 15, 15, imagecolorallocate($image, 180, 0, 0));
        }));
    }

    private function extractColor(int $red, int $green, int $blue): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required for image theme color tests.');
        }

        return $this->extractPattern(function ($image) use ($red, $green, $blue): void {
            imagefill($image, 0, 0, imagecolorallocate($image, $red, $green, $blue));
        });
    }

    private function extractPattern(callable $draw): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required for image theme color tests.');
        }

        $image = imagecreatetruecolor(10, 10);
        $draw($image);
        $path = tempnam(sys_get_temp_dir(), 'theme-color-') . '.png';
        imagepng($image, $path);
        imagedestroy($image);

        try {
            return app(ImageThemeColorService::class)->fromUploadedFile(
                new UploadedFile($path, 'theme.png', 'image/png', null, true)
            );
        } finally {
            @unlink($path);
        }
    }
}