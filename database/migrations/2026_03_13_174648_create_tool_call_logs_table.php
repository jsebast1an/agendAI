<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tool_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('tool_name', 100);
            $table->json('input')->nullable();
            $table->json('result')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->boolean('success')->default(true);
            $table->string('error_message')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index('tool_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_call_logs');
    }
};
