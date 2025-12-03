<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LlmConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'prompt_type',
        'is_active',
        'version',
        'provider',
        'model',
        'temperature',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty',
        'system_prompt',
        'user_prompt_template',
        'response_schema',
        'magento_store_id',
        'created_by',
        'updated_by',
        'last_used_at',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'temperature' => 'decimal:2',
        'top_p' => 'decimal:2',
        'frequency_penalty' => 'decimal:2',
        'presence_penalty' => 'decimal:2',
        'response_schema' => 'array',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    /**
     * Get the Magento store this configuration belongs to (optional).
     */
    public function magentoStore(): BelongsTo
    {
        return $this->belongsTo(MagentoStore::class);
    }

    /**
     * Get the user who created this configuration.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this configuration.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the active configuration for a specific prompt type.
     *
     * @param string $promptType 'writer' or 'auditor'
     * @param int|null $magentoStoreId
     * @return self|null
     */
    public static function getActive(string $promptType, ?int $magentoStoreId = null): ?self
    {
        return static::query()
            ->where('prompt_type', $promptType)
            ->where('is_active', true)
            ->where(function ($query) use ($magentoStoreId) {
                // Prefer store-specific config, fallback to global (null)
                if ($magentoStoreId) {
                    $query->where('magento_store_id', $magentoStoreId)
                        ->orWhereNull('magento_store_id');
                } else {
                    $query->whereNull('magento_store_id');
                }
            })
            ->orderByRaw('magento_store_id IS NOT NULL DESC') // Store-specific first
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    /**
     * Increment usage counter and update last_used_at.
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Render the user prompt with data placeholders.
     *
     * @param array $data Data to replace in template (supports multiple keys)
     * @return string
     */
    public function renderUserPrompt(array $data): string
    {
        $template = $this->user_prompt_template;

        // Replace all placeholders in {{key}} format
        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';

            // Convert arrays/objects to JSON
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            $template = str_replace($placeholder, $value, $template);
        }

        // Backward compatibility: also handle {{product_json}}
        if (! str_contains($template, '{{product_json}}') && isset($data['product'])) {
            // Already replaced via loop above
        }

        return $template;
    }

    /**
     * Get the OpenAI API parameters as an array.
     *
     * @return array
     */
    public function getApiParameters(): array
    {
        $params = [
            'model' => $this->model,
            'temperature' => (float) $this->temperature,
            'max_tokens' => $this->max_tokens,
        ];

        if ($this->top_p !== null) {
            $params['top_p'] = (float) $this->top_p;
        }

        if ($this->frequency_penalty !== null) {
            $params['frequency_penalty'] = (float) $this->frequency_penalty;
        }

        if ($this->presence_penalty !== null) {
            $params['presence_penalty'] = (float) $this->presence_penalty;
        }

        return $params;
    }

    /**
     * Scope to filter by prompt type.
     */
    public function scopeWriter($query)
    {
        return $query->where('prompt_type', 'writer');
    }

    /**
     * Scope to filter by prompt type.
     */
    public function scopeAuditor($query)
    {
        return $query->where('prompt_type', 'auditor');
    }

    /**
     * Scope to filter active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
