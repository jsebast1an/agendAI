<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('wa_phone_number', 32)->unique();
            $table->string('timezone', 50)->default('America/Guayaquil');
            $table->timestampsTz();

            $table->index('wa_phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
