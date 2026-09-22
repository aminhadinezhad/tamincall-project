<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each attempt to call the customer back. Unanswered attempts are kept too, so the history shows them.
     */
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('answered');
            $table->boolean('purchased')->nullable();
            $table->string('no_purchase_reason')->nullable();
            // 1 (very unhappy) to 5 (very happy)
            $table->unsignedTinyInteger('agent_satisfaction')->nullable();
            $table->unsignedTinyInteger('overall_satisfaction')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
