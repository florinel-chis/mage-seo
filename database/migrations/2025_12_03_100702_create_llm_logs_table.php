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
        Schema::create('llm_logs', function (Blueprint $table) {
            $table->id();

            // Context
            $table->string('agent_type'); // 'writer' or 'auditor'
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('seo_draft_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('seo_job_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('llm_configuration_id')->nullable()->constrained()->onDelete('set null');

            // Request details
            $table->string('model')->nullable(); // e.g., 'gemini-2.5-flash'
            $table->text('api_url')->nullable(); // Full API URL
            $table->json('request_headers')->nullable(); // Request headers (sanitized)
            $table->longText('request_body')->nullable(); // Full request payload as JSON
            $table->json('product_data')->nullable(); // Product data sent to LLM
            $table->text('system_prompt')->nullable(); // System prompt used
            $table->text('user_prompt')->nullable(); // User prompt used

            // Response details
            $table->integer('response_status')->nullable(); // HTTP status code
            $table->json('response_headers')->nullable(); // Response headers
            $table->longText('response_body')->nullable(); // Full API response
            $table->json('parsed_output')->nullable(); // Parsed/extracted data
            $table->text('error_message')->nullable(); // Error if failed

            // Performance metrics
            $table->integer('execution_time_ms')->nullable(); // Time taken in milliseconds
            $table->integer('prompt_tokens')->nullable(); // Tokens in prompt
            $table->integer('completion_tokens')->nullable(); // Tokens in completion
            $table->integer('total_tokens')->nullable(); // Total tokens used

            // Metadata
            $table->boolean('success')->default(false); // Whether call succeeded
            $table->timestamps();

            // Indexes
            $table->index('agent_type');
            $table->index('product_id');
            $table->index('seo_draft_id');
            $table->index('success');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_logs');
    }
};
