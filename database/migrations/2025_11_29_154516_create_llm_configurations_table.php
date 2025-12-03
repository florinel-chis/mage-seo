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
        Schema::create('llm_configurations', function (Blueprint $table) {
            $table->id();

            // Configuration identity
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('prompt_type'); // 'writer' or 'auditor'
            $table->boolean('is_active')->default(false);
            $table->integer('version')->default(1);

            // LLM Provider Settings
            $table->string('provider')->default('openai'); // 'openai', 'anthropic', etc.
            $table->string('model')->default('gpt-4o-mini'); // gpt-4, gpt-4o-mini, claude-3-opus, etc.

            // Model Parameters
            $table->decimal('temperature', 3, 2)->default(0.70); // 0.00 - 2.00
            $table->integer('max_tokens')->default(500);
            $table->decimal('top_p', 3, 2)->nullable(); // 0.00 - 1.00
            $table->decimal('frequency_penalty', 3, 2)->nullable(); // -2.00 - 2.00
            $table->decimal('presence_penalty', 3, 2)->nullable(); // -2.00 - 2.00

            // Prompts
            $table->text('system_prompt');
            $table->text('user_prompt_template');

            // Response Format (for structured output)
            $table->json('response_schema')->nullable();

            // Optional: Store-specific configuration
            $table->foreignId('magento_store_id')->nullable()->constrained()->onDelete('cascade');

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['prompt_type', 'is_active']);
            $table->index(['magento_store_id', 'prompt_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_configurations');
    }
};
