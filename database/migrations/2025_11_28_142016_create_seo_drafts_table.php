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
        Schema::create('seo_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('original_data');
            $table->json('generated_draft');
            $table->json('audit_flags')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('status')->default('PENDING_REVIEW');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_drafts');
    }
};
