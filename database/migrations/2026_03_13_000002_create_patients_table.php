<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('wa_id', 64);
            $table->string('name')->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'wa_id']);
            $table->index('wa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
