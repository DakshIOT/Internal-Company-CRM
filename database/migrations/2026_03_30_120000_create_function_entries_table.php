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
        Schema::create('function_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('name');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('package_total_minor')->default(0);
            $table->unsignedBigInteger('extra_charge_total_minor')->default(0);
            $table->unsignedBigInteger('discount_total_minor')->default(0);
            $table->unsignedBigInteger('function_total_minor')->default(0);
            $table->unsignedBigInteger('paid_total_minor')->default(0);
            $table->bigInteger('pending_total_minor')->default(0);
            $table->unsignedBigInteger('frozen_fund_minor')->default(0);
            $table->bigInteger('net_total_after_frozen_fund_minor')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'venue_id', 'entry_date']);
            $table->index(['venue_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('function_entries');
    }
};
