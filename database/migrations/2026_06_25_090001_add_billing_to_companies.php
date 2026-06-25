<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Preço por segmento de SMS (para faturação) e moeda.
            $table->decimal('price_per_segment', 10, 4)->default(0)->after('messages_per_minute');
            $table->string('currency', 8)->default('MZN')->after('price_per_segment');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['price_per_segment', 'currency']);
        });
    }
};
