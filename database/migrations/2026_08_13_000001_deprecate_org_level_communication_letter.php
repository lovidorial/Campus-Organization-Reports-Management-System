<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Archive DOC_COMMUNICATION records by marking them as non-current
        // These will be deprecated as communication letters are now activity-level
        \DB::table('workflow_submissions')
            ->where('document_type', 'communication_letter')
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    public function down(): void
    {
        // Restore the is_current flag for communication letter records if rolling back
        // Note: This is a best-effort rollback; full restoration may require additional logic
    }
};
