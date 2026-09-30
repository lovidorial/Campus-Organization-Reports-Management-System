<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('gpoas')
            ->where('status', 'submitted')
            ->update([
                'status' => 'approved',
                'approved_at' => DB::raw('COALESCE(approved_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        // Existing approvals are not reverted.
    }
};