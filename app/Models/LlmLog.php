<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmLog extends Model
{
    protected $fillable = [
        'agent_type',
        'product_id',
        'seo_draft_id',
        'seo_job_id',
        'llm_configuration_id',
        'model',
        'api_url',
        'request_headers',
        'request_body',
        'product_data',
        'system_prompt',
        'user_prompt',
        'response_status',
        'response_headers',
        'response_body',
        'parsed_output',
        'error_message',
        'execution_time_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'success',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'product_data' => 'array',
        'response_headers' => 'array',
        'parsed_output' => 'array',
        'success' => 'boolean',
        'execution_time_ms' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'response_status' => 'integer',
    ];

    /**
     * Get the product associated with this log.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the SEO draft associated with this log.
     */
    public function seoDraft(): BelongsTo
    {
        return $this->belongsTo(SeoDraft::class);
    }

    /**
     * Get the SEO job associated with this log.
     */
    public function seoJob(): BelongsTo
    {
        return $this->belongsTo(SeoJob::class);
    }

    /**
     * Get the LLM configuration used for this call.
     */
    public function llmConfiguration(): BelongsTo
    {
        return $this->belongsTo(LlmConfiguration::class);
    }

    /**
     * Scope to filter by agent type.
     */
    public function scopeWriter($query)
    {
        return $query->where('agent_type', 'writer');
    }

    /**
     * Scope to filter by agent type.
     */
    public function scopeAuditor($query)
    {
        return $query->where('agent_type', 'auditor');
    }

    /**
     * Scope to filter successful calls.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to filter failed calls.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (! $this->execution_time_ms) {
            return 'N/A';
        }

        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms.'ms';
        }

        return round($this->execution_time_ms / 1000, 2).'s';
    }

    /**
     * Get formatted token usage.
     */
    public function getFormattedTokensAttribute(): string
    {
        if (! $this->total_tokens) {
            return 'N/A';
        }

        return number_format($this->total_tokens).' tokens';
    }

    /**
     * Check if this log has an error.
     */
    public function hasError(): bool
    {
        return ! $this->success || ! empty($this->error_message);
    }

    /**
     * Get truncated request body for display.
     */
    public function getTruncatedRequestBodyAttribute(): string
    {
        if (! $this->request_body) {
            return 'N/A';
        }

        return strlen($this->request_body) > 200
            ? substr($this->request_body, 0, 200).'...'
            : $this->request_body;
    }

    /**
     * Get truncated response body for display.
     */
    public function getTruncatedResponseBodyAttribute(): string
    {
        if (! $this->response_body) {
            return 'N/A';
        }

        return strlen($this->response_body) > 200
            ? substr($this->response_body, 0, 200).'...'
            : $this->response_body;
    }
}
