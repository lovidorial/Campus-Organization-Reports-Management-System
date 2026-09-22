<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageThemeColorService
{
    public function fromUploadedFile(UploadedFile $file): ?string
    {
        $mimeType = $file->getMimeType();
        $image = match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($file->getRealPath()) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($file->getRealPath()) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : false,
            default => false,
        };

        if (! $image) {
            return null;
        }

        $sample = function_exists('imagescale') ? imagescale($image, 24, 24) : false;
        imagedestroy($image);

        if (! $sample) {
            return null;
        }

        $filteredRed = $filteredGreen = $filteredBlue = $filteredPixelCount = 0;
        $allRed = $allGreen = $allBlue = $allPixelCount = 0;

        for ($x = 0; $x < imagesx($sample); $x++) {
            for ($y = 0; $y < imagesy($sample); $y++) {
                $rgb = imagecolorat($sample, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $pixelBrightness = ($red * 299 + $green * 587 + $blue * 114) / 1000;

                $allRed += $red;
                $allGreen += $green;
                $allBlue += $blue;
                $allPixelCount++;

                if ($pixelBrightness > 235 || $pixelBrightness < 20) {
                    continue;
                }

                $filteredRed += $red;
                $filteredGreen += $green;
                $filteredBlue += $blue;
                $filteredPixelCount++;
            }
        }

        imagedestroy($sample);

        if ($filteredPixelCount > 0) {
            $red = $filteredRed / $filteredPixelCount;
            $green = $filteredGreen / $filteredPixelCount;
            $blue = $filteredBlue / $filteredPixelCount;
        } else {
            $red = $allRed / $allPixelCount;
            $green = $allGreen / $allPixelCount;
            $blue = $allBlue / $allPixelCount;
        }

        [$red, $green, $blue] = $this->boostSaturation($red, $green, $blue);
        $red = (int) round($red);
        $green = (int) round($green);
        $blue = (int) round($blue);
        $brightness = ($red * 299 + $green * 587 + $blue * 114) / 1000;

        if ($brightness > 180) {
            $scale = 180 / $brightness;
            $red = (int) round($red * $scale);
            $green = (int) round($green * $scale);
            $blue = (int) round($blue * $scale);
        }

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    /**
     * Increase chroma after averaging, which otherwise tends to wash logos out.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function boostSaturation(float $red, float $green, float $blue): array
    {
        $red /= 255;
        $green /= 255;
        $blue /= 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return [$red * 255, $green * 255, $blue * 255];
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5
            ? $delta / (2 - $max - $min)
            : $delta / ($max + $min);
        $saturation = min(1, $saturation * 1.3);

        $hue = match ($max) {
            $red => ($green - $blue) / $delta + ($green < $blue ? 6 : 0),
            $green => ($blue - $red) / $delta + 2,
            default => ($red - $green) / $delta + 4,
        } / 6;

        $q = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - $lightness * $saturation;
        $p = 2 * $lightness - $q;

        return [
            $this->hueToRgb($p, $q, $hue + 1 / 3) * 255,
            $this->hueToRgb($p, $q, $hue) * 255,
            $this->hueToRgb($p, $q, $hue - 1 / 3) * 255,
        ];
    }

    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }

        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }
}