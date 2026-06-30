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
        Schema::create('conditions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            
            $table->string('disease_name');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->date('diagnosed_at');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conditions');
    }
};
