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
            $table->json('product_ids')->nullable()->after('magento_store_view');
            $table->json('filter_criteria')->nullable()->after('product_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_jobs', function (Blueprint $table) {
            $table->dropColumn(['product_ids', 'filter_criteria']);
        });
    }
};
