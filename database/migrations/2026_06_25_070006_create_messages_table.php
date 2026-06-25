<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // idempotência local
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('httpsms_id')->nullable()->index(); // id da mensagem na API httpSMS
            $table->string('direction')->default('outbound')->index(); // outbound|inbound
            $table->string('from_number')->nullable();
            $table->string('to_number')->index();
            $table->text('content');
            $table->unsignedSmallInteger('segments')->default(1);

            // pending|scheduled|queued|sending|sent|delivered|failed|expired|received
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();

            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
