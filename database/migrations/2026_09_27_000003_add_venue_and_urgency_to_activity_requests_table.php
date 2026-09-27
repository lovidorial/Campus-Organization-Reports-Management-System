<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->boolean('is_urgent')->default(false);
            $table->text('urgent_reason')->nullable();
            $table->index(['venue_id', 'date', 'end_date']);
        });

        $venueIds = [];
        foreach (DB::table('activity_requests')->select('venue')->distinct()->get() as $request) {
            $name = trim((string) $request->venue);
            if ($name === '') {
                continue;
            }

            $venue = DB::table('venues')->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
            if (! $venue) {
                $venueId = DB::table('venues')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $venue = (object) ['id' => $venueId, 'name' => $name];
            }

            $venueIds[mb_strtolower($name)] = (int) $venue->id;
        }

        foreach (DB::table('activity_requests')->select('id', 'venue')->get() as $request) {
            $key = mb_strtolower(trim((string) $request->venue));
            if (isset($venueIds[$key])) {
                DB::table('activity_requests')->where('id', $request->id)->update(['venue_id' => $venueIds[$key]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('activity_requests', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'date', 'end_date']);
            $table->dropConstrainedForeignId('venue_id');
            $table->dropColumn(['is_urgent', 'urgent_reason']);
        });
    }
};