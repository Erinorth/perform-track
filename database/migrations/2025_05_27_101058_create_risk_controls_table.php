<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง risk_controls ที่เชื่อมโยงกับ division_risks
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\Log::info('กำลังสร้างตาราง risk_controls ที่เชื่อมโยงกับ division_risks');
        
        Schema::create('risk_controls', function (Blueprint $table) {
            $table->id();
            
            // แก้ไขให้อ้างอิงไปที่ตาราง division_risks ที่ถูกต้อง
            $table->foreignId('division_risk_id')->constrained('division_risks')->onDelete('cascade');
            
            $table->string('control_name')->comment('ชื่อการควบคุมความเสี่ยง');
            $table->text('description')->nullable()->comment('รายละเอียดการควบคุม');
            $table->string('owner')->nullable()->comment('ผู้รับผิดชอบ');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('สถานะการใช้งาน');
            $table->enum('control_type', ['preventive', 'detective', 'corrective', 'compensating'])
                  ->nullable()
                  ->comment('ประเภทการควบคุม: preventive=ป้องกัน, detective=ตรวจจับ, corrective=แก้ไข, compensating=ชดเชย');
            $table->text('implementation_details')->nullable()->comment('รายละเอียดการดำเนินการ');
            $table->date('implementation_date')->nullable()->comment('วันที่เริ่มดำเนินการ');
            $table->date('review_date')->nullable()->comment('วันที่ทบทวน');
            $table->decimal('effectiveness_score', 3, 2)->nullable()->comment('คะแนนประสิทธิผล (0-5)');
            $table->text('notes')->nullable()->comment('หมายเหตุเพิ่มเติม');
            
            $table->timestamps();
            $table->softDeletes();

            // สร้าง indexes เพื่อเพิ่มประสิทธิภาพ
            $table->index('division_risk_id', 'idx_risk_controls_division_risk');
            $table->index('status', 'idx_risk_controls_status');
            $table->index('control_type', 'idx_risk_controls_type');
            $table->index(['division_risk_id', 'status'], 'idx_risk_controls_division_status');
            $table->index(['status', 'control_type'], 'idx_risk_controls_status_type');
        });

        \Illuminate\Support\Facades\Log::info('สร้างตาราง risk_controls เรียบร้อยแล้ว');
    }

    /**
     * ลบตาราง risk_controls
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\Log::info('กำลังลบตาราง risk_controls');
        
        Schema::dropIfExists('risk_controls');
        
        \Illuminate\Support\Facades\Log::info('ลบตาราง risk_controls เรียบร้อยแล้ว');
    }
};
