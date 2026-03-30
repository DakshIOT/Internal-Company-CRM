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
        Schema::create('function_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('function_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->string('name_snapshot');
            $table->string('code_snapshot')->nullable();
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->timestamps();

            $table->unique(['function_entry_id', 'package_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('function_packages');
    }
};
