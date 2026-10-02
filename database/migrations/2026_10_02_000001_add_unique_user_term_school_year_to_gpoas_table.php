<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('gpoas')
            ->select('user_id', 'term', 'school_year')
            ->selectRaw('COUNT(*) as duplicate_count')
            ->groupBy('user_id', 'term', 'school_year')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(function ($duplicate): string {
                $ids = DB::table('gpoas')
                    ->where('user_id', $duplicate->user_id)
                    ->where('term', $duplicate->term)
                    ->where('school_year', $duplicate->school_year)
                    ->orderBy('id')
                    ->pluck('id')
                    ->implode(',');

                return "user_id={$duplicate->user_id}, term={$duplicate->term}, school_year={$duplicate->school_year}, gpoa_ids={$ids}";
            })->implode('; ');

            throw new \RuntimeException('Cannot add the GPOA unique index until duplicate records are reviewed and resolved manually: ' . $details);
        }

        Schema::table('gpoas', function (Blueprint $table): void {
            $table->unique(['user_id', 'term', 'school_year'], 'gpoas_user_term_school_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('gpoas', function (Blueprint $table): void {
            $table->dropUnique('gpoas_user_term_school_year_unique');
        });
    }
};
