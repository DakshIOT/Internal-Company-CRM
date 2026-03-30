<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('function_service_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('function_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_selected')->default(false);
            $table->string('item_name_snapshot');
            $table->unsignedBigInteger('rate_minor')->default(0);
            $table->unsignedInteger('persons')->default(0);
            $table->unsignedBigInteger('extra_charge_minor')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('line_total_minor')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('function_service_lines');
    }
};
