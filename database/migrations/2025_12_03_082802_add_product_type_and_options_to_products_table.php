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
        Schema::table('products', function (Blueprint $table) {
            $table->string('type_id')->nullable()->after('sku'); // simple, configurable, bundle, etc.
            $table->json('extension_attributes')->nullable()->after('attributes'); // bundle options, configurable options, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type_id', 'extension_attributes']);
        });
    }
};
