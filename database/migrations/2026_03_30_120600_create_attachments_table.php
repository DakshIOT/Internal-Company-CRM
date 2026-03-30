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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
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
        Schema::dropIfExists('attachments');
    }
};
