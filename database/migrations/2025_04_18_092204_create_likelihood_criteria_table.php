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
        Schema::create('likelihood_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level'); // 1-4
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('division_risk_id')->constrained('division_risks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likelihood_criteria');
    }
};