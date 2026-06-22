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
        Schema::create('sleep_analytics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            
            // تاريخ اليوم (عشان يكون عندنا ريكورد واحد لكل مريض في اليوم)
            $table->date('date');
            
            // بيانات النوم اللي جاية من موديل الذكاء الاصطناعي
            $table->float('sleep_duration')->default(0); // مدة النوم بالساعات
            $table->integer('sleep_quality')->default(0); // جودة النوم (مثلاً من 1 لـ 10)
            $table->string('disorder_prediction')->nullable();

            $table->timestamps();

            $table->unique(['patient_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleep_analytics');
    }
};
