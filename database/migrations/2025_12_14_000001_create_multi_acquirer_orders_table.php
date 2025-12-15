<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = (string) config('multi-acquirer.database.tables.orders', 'orders');

        Schema::create($table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->json('customer_data');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $table = (string) config('multi-acquirer.database.tables.orders', 'orders');

        Schema::dropIfExists($table);
    }
};

