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
        Schema::create('medication_doses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at'); 
            $table->dateTime('taken_at')->nullable(); 
            
            $table->enum('status', ['pending', 'taken', 'missed'])->default('pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_doses');
    }
};
