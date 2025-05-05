<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // สร้างตาราง
    public function up(): void
    {
        Schema::create('organizational_risk_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_risk_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    // ลบตาราง
    public function down(): void
    {
        Schema::dropIfExists('organizational_risk_attachments');
    }
};
