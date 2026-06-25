<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_email')->nullable();

            // Cada empresa traz a SUA conta httpSMS.
            $table->text('httpsms_api_key')->nullable();          // encriptada
            $table->string('httpsms_base_url')->default('https://api.httpsms.com/v1');

            // Webhook da empresa para receber estados de entrega (status callback).
            $table->string('status_callback_url')->nullable();
            $table->text('callback_secret')->nullable();          // encriptada

            $table->unsignedInteger('messages_per_minute')->default(60); // rate limit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
