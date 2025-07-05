<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rxcui');
            $table->string('name');
            $table->json('base_names')->nullable();
            $table->json('dosage_forms')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'rxcui']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_medications');
    }
};
