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
        // เพิ่ม log เพื่อบันทึกการเริ่มต้นสร้างตาราง
        \Illuminate\Support\Facades\Log::info('กำลังสร้างตาราง risk_assessments กับการอ้างอิงไปยังตาราง division_risks');
        
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->date('assessment_date');
            $table->unsignedTinyInteger('likelihood_level'); // 1-4
            $table->unsignedTinyInteger('impact_level'); // 1-4
            $table->unsignedTinyInteger('risk_score')->virtualAs('likelihood_level * impact_level');
            $table->foreignId('division_risk_id')->constrained('division_risks');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
        // เพิ่ม log เพื่อบันทึกการสร้างตารางสำเร็จ
        \Illuminate\Support\Facades\Log::info('สร้างตาราง risk_assessments เรียบร้อยแล้ว');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // เพิ่ม log เพื่อบันทึกการลบตาราง
        \Illuminate\Support\Facades\Log::info('กำลังลบตาราง risk_assessments');
        
        Schema::dropIfExists('risk_assessments');
    }
};