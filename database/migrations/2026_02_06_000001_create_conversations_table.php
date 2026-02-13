<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 32)->unique();
            $table->string('conversation_status')->nullable();
            $table->boolean('handoff_to_human')->default(false);
            $table->timestampsTz();

            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
