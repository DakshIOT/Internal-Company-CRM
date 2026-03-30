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
        Schema::create('function_extra_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('function_entry_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('name');
            $table->string('mode', 20);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['function_entry_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('function_extra_charges');
    }
};
