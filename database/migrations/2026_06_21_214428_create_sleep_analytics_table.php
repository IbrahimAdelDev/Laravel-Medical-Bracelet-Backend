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
            
            $table->date('date');
            
            $table->float('sleep_duration')->default(0); 
            $table->integer('sleep_quality')->default(0); 
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
