<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_jobs', function (Blueprint $table) {
            $table->foreignId('llm_config_id')->nullable()->after('filter_criteria')->constrained('llm_configurations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_jobs', function (Blueprint $table) {
            $table->dropForeign(['llm_config_id']);
            $table->dropColumn('llm_config_id');
        });
    }
};
