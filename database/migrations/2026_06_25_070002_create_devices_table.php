<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('httpsms_id')->nullable()->unique(); // id devolvido pela API httpSMS
            $table->string('name');
            $table->string('phone_number')->index();
            $table->string('model')->nullable();
            $table->string('status')->default('offline')->index(); // online|offline
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable(); // 0-100
            $table->boolean('charging')->default(false);
            $table->unsignedTinyInteger('signal_strength')->nullable(); // 0-100
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
