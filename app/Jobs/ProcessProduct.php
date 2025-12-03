<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\SeoJob;
use App\Services\LLM\WriterAuditor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of seconds to wait before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SeoJob $seoJob,
        public Product $product
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WriterAuditor $writerAuditor): void
    {
        try {
            Log::info('Processing SEO for product', [
                'job_id' => $this->seoJob->id,
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'llm_config_id' => $this->seoJob->llm_config_id,
            ]);

            // Set logging context for LLM calls
            $writerAuditor->setContext(
                productId: $this->product->id,
                seoDraftId: null, // Will be set after draft creation
                seoJobId: $this->seoJob->id
            );

            // Call Writer-Auditor pipeline with optional LLM configuration
            $result = $writerAuditor->generate($this->product, $this->seoJob->llm_config_id);

            // Validate response structure
            if (! isset($result['generated_draft']) || ! isset($result['audit'])) {
                throw new Exception('Invalid response from WriterAuditor: missing required fields');
            }

            // Save draft in transaction
            DB::transaction(function () use ($result) {
                $this->seoJob->drafts()->create([
                    'product_id' => $this->product->id,
                    'original_data' => [
                        'name' => $this->product->name,
                        'description' => $this->product->description,
                        'sku' => $this->product->sku,
                        'attributes' => $this->product->attributes,
                    ],
                    'generated_draft' => $result['generated_draft'],
                    'audit_flags' => $result['audit']['potential_hallucinations'] ?? [],
                    'confidence_score' => $result['audit']['confidence_score'] ?? 0.0,
                    'status' => ($result['audit']['is_safe'] ?? false) && ($result['audit']['confidence_score'] ?? 0) > 0.9
                        ? 'APPROVED'
                        : 'PENDING_REVIEW',
                ]);

                $this->seoJob->increment('processed_products');
            });

            Log::info('Successfully processed SEO for product', [
                'job_id' => $this->seoJob->id,
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'status' => $result['audit']['is_safe'] && $result['audit']['confidence_score'] > 0.9 ? 'APPROVED' : 'PENDING_REVIEW',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process SEO for product', [
                'job_id' => $this->seoJob->id,
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // If we've exhausted all retries, create a failed draft record
            if ($this->attempts() >= $this->tries) {
                $this->createFailedDraft($e);
            }

            // Re-throw to mark job as failed (will retry or go to failed_jobs table)
            throw $e;
        }
    }

    /**
     * Create a draft record marking the failure for visibility.
     */
    protected function createFailedDraft(Exception $e): void
    {
        try {
            DB::transaction(function () use ($e) {
                $this->seoJob->drafts()->create([
                    'product_id' => $this->product->id,
                    'original_data' => [
                        'name' => $this->product->name,
                        'sku' => $this->product->sku,
                    ],
                    'generated_draft' => [
                        'meta_title' => 'FAILED - '.$e->getMessage(),
                        'meta_description' => 'Generation failed after '.$this->tries.' attempts',
                        'meta_keywords' => 'error, failed',
                    ],
                    'audit_flags' => [
                        [
                            'type' => 'processing_error',
                            'message' => $e->getMessage(),
                            'attempts' => $this->attempts(),
                        ],
                    ],
                    'confidence_score' => 0.0,
                    'status' => 'REJECTED',
                ]);

                $this->seoJob->increment('processed_products');
            });

            Log::warning('Created failed draft record', [
                'job_id' => $this->seoJob->id,
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
            ]);
        } catch (Exception $dbException) {
            // Log but don't throw - we don't want to hide the original exception
            Log::error('Failed to create failed draft record', [
                'job_id' => $this->seoJob->id,
                'product_id' => $this->product->id,
                'original_error' => $e->getMessage(),
                'db_error' => $dbException->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Job permanently failed', [
            'job_id' => $this->seoJob->id,
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'error' => $exception->getMessage(),
        ]);

        // Optionally: Update SeoJob status to FAILED if all products have failed
        // This could trigger a notification to the user
    }
}
