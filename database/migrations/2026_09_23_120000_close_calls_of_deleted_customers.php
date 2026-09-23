<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Calls whose customer was deleted before the customer took its calls with it. They were still in
 * the list with an empty name, so they are closed here as their customer's deletion would have.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('calls')
            ->whereNull('calls.deleted_at')
            ->whereIn('calls.customer_id', fn ($query) => $query->select('id')->from('customers')->whereNotNull('deleted_at'))
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        // nothing to undo: restoring a customer brings their calls back
    }
};
