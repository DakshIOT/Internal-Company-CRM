<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('function_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_minor')->default(0)->after('code_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('function_packages', function (Blueprint $table) {
            $table->dropColumn('discount_minor');
        });
    }
};
