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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete(); 
            
            // Vitals
            $table->integer('heart_rate')->nullable();
            $table->integer('spo2')->nullable();
            $table->float('body_temperature')->nullable();
            $table->integer('ecg_signal')->nullable();
            
            // Movement
            $table->float('acc_x')->nullable();
            $table->float('acc_y')->nullable();
            $table->float('acc_z')->nullable();
            
            // Environment
            $table->float('uv_index')->nullable();
            
            // Location
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            
            $table->timestamp('device_timestamp')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
