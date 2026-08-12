<?php

namespace App\Services;

use App\Models\OrganizationClassification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class OrganizationClassifierService
{
    private const SIMILARITY_THRESHOLD = 80;

    public function classify(string $orgName): ?array
    {
        $normalizedInput = $this->normalize($orgName);
        if ($normalizedInput === '') {
            return null;
        }

        $classifications = OrganizationClassification::all();
        if ($classifications->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($classifications as $classification) {
            $names = array_merge([$classification->org_name], Arr::wrap($classification->aliases));
            foreach ($names as $name) {
                $normalizedCandidate = $this->normalize($name);
                if ($normalizedCandidate === '') {
                    continue;
                }

                if ($normalizedInput === $normalizedCandidate) {
                    return [
                        'classification' => $classification->classification,
                        'college_area' => $classification->college_area,
                        'matched_name' => $name,
                    ];
                }

                $score = $this->similarityScore($normalizedInput, $normalizedCandidate);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'classification' => $classification->classification,
                        'college_area' => $classification->college_area,
                        'matched_name' => $name,
                        'score' => $score,
                    ];
                }
            }
        }

        if ($bestMatch && $bestMatch['score'] >= self::SIMILARITY_THRESHOLD) {
            return Arr::except($bestMatch, ['score']);
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Z0-9\s]/', ' ', mb_strtoupper($value));
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function similarityScore(string $a, string $b): int
    {
        similar_text($a, $b, $percent);
        $distance = levenshtein($a, $b);

        if ($distance <= 2) {
            return max($percent, 90);
        }

        return (int) round($percent);
    }
}
