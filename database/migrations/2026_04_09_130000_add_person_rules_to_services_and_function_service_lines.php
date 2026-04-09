<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('uses_persons')->default(true)->after('standard_rate_minor');
            $table->unsignedInteger('default_persons')->nullable()->after('uses_persons');
        });

        DB::table('services')->update([
            'uses_persons' => true,
            'default_persons' => 1,
        ]);

        Schema::table('function_service_lines', function (Blueprint $table) {
            $table->boolean('uses_persons')->default(true)->after('rate_minor');
        });

        DB::table('function_service_lines')->update([
            'uses_persons' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('function_service_lines', function (Blueprint $table) {
            $table->dropColumn('uses_persons');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['uses_persons', 'default_persons']);
        });
    }
};
