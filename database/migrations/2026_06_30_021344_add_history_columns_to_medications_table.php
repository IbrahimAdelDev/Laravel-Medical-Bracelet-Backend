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
        Schema::table('medications', function (Blueprint $table) {
            $table->foreignId('condition_id')->nullable()->after('patient_id')->constrained('conditions')->onDelete('set null');
            
            $table->string('stop_reason', 500)->nullable()->after('end_date');

            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropForeign(['condition_id']);
            $table->dropIndex(['end_date']);
            
            $table->dropColumn([
                'condition_id',
                'start_date',
                'end_date',
                'stop_reason'
            ]);
        });
    }
};
