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
        Schema::table('magento_stores', function (Blueprint $table) {
            $table->string('sync_status')->default('idle')->after('api_token'); // idle, syncing, completed, failed
            $table->integer('total_products')->nullable()->after('sync_status');
            $table->integer('products_fetched')->default(0)->after('total_products');
            $table->timestamp('last_sync_started_at')->nullable()->after('products_fetched');
            $table->timestamp('last_sync_completed_at')->nullable()->after('last_sync_started_at');
            $table->text('sync_error')->nullable()->after('last_sync_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magento_stores', function (Blueprint $table) {
            $table->dropColumn([
                'sync_status',
                'total_products',
                'products_fetched',
                'last_sync_started_at',
                'last_sync_completed_at',
                'sync_error',
            ]);
        });
    }
};
