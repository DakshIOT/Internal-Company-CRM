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
            $table->string('person_input_mode', 20)->default('fixed')->after('default_persons');
        });

        DB::table('services')->update([
            'person_input_mode' => DB::raw("CASE WHEN uses_persons = 1 THEN 'fixed' ELSE 'none' END"),
        ]);

        Schema::table('function_service_lines', function (Blueprint $table) {
            $table->string('person_input_mode', 20)->default('fixed')->after('uses_persons');
        });

        DB::table('function_service_lines')->update([
            'person_input_mode' => DB::raw("CASE WHEN uses_persons = 1 THEN 'fixed' ELSE 'none' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('function_service_lines', function (Blueprint $table) {
            $table->dropColumn('person_input_mode');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('person_input_mode');
        });
    }
};
