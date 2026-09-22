<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One incoming customer call: what they wanted, who they were referred to, and when to call them back.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('request');
            $table->string('status')->default('awaiting');
            $table->date('follow_up_on');
            $table->unsignedTinyInteger('unanswered_attempts')->default(0);
            $table->timestamps();

            $table->index(['status', 'follow_up_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
