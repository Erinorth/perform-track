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
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->date('assessment_date');
            $table->unsignedTinyInteger('likelihood_level'); // 1-4
            $table->unsignedTinyInteger('impact_level'); // 1-4
            $table->unsignedTinyInteger('risk_score')->virtualAs('likelihood_level * impact_level');
            $table->foreignId('department_risk_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
