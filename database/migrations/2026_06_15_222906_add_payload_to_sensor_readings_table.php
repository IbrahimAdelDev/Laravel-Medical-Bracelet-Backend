<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            // إضافة العمود الجديد
            $table->json('payload')->nullable()->after('patient_id');

            // حذف الأعمدة القديمة
            $table->dropColumn([
                'heart_rate',
                'spo2',
                'body_temperature',
                'ecg_signal',
                'acc_x',
                'acc_y',
                'acc_z',
                'uv_index',
                'lat',
                'lon',
                'device_timestamp',
            ]);
        });
    }

    public function down()
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            // إعادة الأعمدة القديمة
            $table->float('heart_rate')->nullable();
            $table->float('spo2')->nullable();
            $table->float('body_temperature')->nullable();
            $table->text('ecg_signal')->nullable();
            $table->float('acc_x')->nullable();
            $table->float('acc_y')->nullable();
            $table->float('acc_z')->nullable();
            $table->float('uv_index')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->timestamp('device_timestamp')->nullable();

            // حذف payload
            $table->dropColumn('payload');
        });
    }
};
