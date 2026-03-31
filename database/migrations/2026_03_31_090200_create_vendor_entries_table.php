<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_vendor_id')->constrained('venue_vendors')->restrictOnDelete();
            $table->string('vendor_name_snapshot');
            $table->date('entry_date');
            $table->string('name');
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'venue_id', 'entry_date']);
            $table->index(['venue_id', 'venue_vendor_id', 'entry_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_entries');
    }
};
