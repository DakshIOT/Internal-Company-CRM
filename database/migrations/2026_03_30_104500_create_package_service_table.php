<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('package_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['package_id', 'service_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('package_service');
    }
};
