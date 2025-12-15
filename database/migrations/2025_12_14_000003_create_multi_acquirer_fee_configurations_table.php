<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = (string) config('multi-acquirer.database.tables.fee_configurations', 'fee_configurations');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name', 64)->index();
            $table->string('payment_method', 32)->index();
            $table->unsignedTinyInteger('installments')->default(1);
            $table->decimal('percentage', 10, 6)->default(0);
            $table->unsignedInteger('fixed_fee_cents')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['gateway_name', 'payment_method', 'installments'], 'fee_cfg_unique');
        });
    }

    public function down(): void
    {
        $table = (string) config('multi-acquirer.database.tables.fee_configurations', 'fee_configurations');

        Schema::dropIfExists($table);
    }
};

