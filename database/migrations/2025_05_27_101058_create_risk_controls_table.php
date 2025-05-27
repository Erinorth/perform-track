<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// สร้างตารางการควบคุมความเสี่ยง
return new class extends Migration
{
    public function up(): void
    {
        \Log::info('Creating risk_controls table linked to divisionrisks');
        
        Schema::create('risk_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_risk_id')->constrained('divisionrisks')->onDelete('cascade'); // อ้างอิงความเสี่ยงระดับฝ่าย
            $table->string('control_name'); // ชื่อการควบคุม
            $table->text('description')->nullable(); // รายละเอียดการควบคุม
            $table->string('owner')->nullable(); // ผู้รับผิดชอบหลัก
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('สถานะการใช้งาน'); // สถานะ
            $table->enum('control_type', ['preventive', 'detective', 'corrective', 'compensating'])->nullable()->comment('ประเภทการควบคุม'); // ประเภทการควบคุม
            $table->text('implementation_details')->nullable()->comment('รายละเอียดการดำเนินการ'); // รายละเอียดการดำเนินการ
            $table->timestamps();
            $table->softDeletes();

            // เพิ่ม indexes สำหรับการค้นหาและรายงาน
            $table->index('division_risk_id');
            $table->index('status');
            $table->index('control_type'); // เพิ่ม index สำหรับ control_type
            $table->index(['division_risk_id', 'status']); // compound index สำหรับ filter ตามฝ่ายและสถานะ
            $table->index(['status', 'control_type']); // compound index สำหรับ filter ตามสถานะและประเภท
        });

        \Log::info('Created risk_controls table');
    }

    public function down(): void
    {
        \Log::info('Dropping risk_controls table');
        Schema::dropIfExists('risk_controls');
    }
};
