<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How the customer found Tamin Falat and free notes, on each call; individual or legal, on the
     * customer. Nullable in the database so earlier rows stay valid; the forms require source and type.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('source')->nullable()->after('request');
            $table->text('notes')->nullable()->after('source');
            $table->index('source');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('type')->nullable()->after('phone');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'notes']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
