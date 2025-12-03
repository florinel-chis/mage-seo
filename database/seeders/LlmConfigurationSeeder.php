<?php

namespace Database\Seeders;

use App\Models\LlmConfiguration;
use Illuminate\Database\Seeder;

class LlmConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Writer Configuration
        LlmConfiguration::create([
            'name' => 'Default SEO Writer (GPT-4o-mini)',
            'description' => 'Generates SEO metadata (title, description, keywords) from product data using GPT-4o-mini with conservative settings to minimize hallucinations.',
            'prompt_type' => 'writer',
            'is_active' => true,
            'version' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.70,
            'max_tokens' => 500,
            'top_p' => null,
            'frequency_penalty' => null,
            'presence_penalty' => null,
            'system_prompt' => $this->getWriterSystemPrompt(),
            'user_prompt_template' => $this->getWriterUserPromptTemplate(),
            'response_schema' => [
                'type' => 'object',
                'properties' => [
                    'meta_title' => [
                        'type' => 'string',
                        'description' => 'SEO-optimized page title (50-60 characters)',
                    ],
                    'meta_description' => [
                        'type' => 'string',
                        'description' => 'SEO meta description (150-160 characters)',
                    ],
                    'meta_keywords' => [
                        'type' => 'string',
                        'description' => 'Comma-separated keywords (5-10 keywords)',
                    ],
                ],
                'required' => ['meta_title', 'meta_description', 'meta_keywords'],
            ],
        ]);

        // Default Auditor Configuration
        LlmConfiguration::create([
            'name' => 'Default SEO Auditor (GPT-4o-mini)',
            'description' => 'Validates generated SEO content against source product data to detect hallucinations, unsupported claims, and factual errors.',
            'prompt_type' => 'auditor',
            'is_active' => true,
            'version' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.30, // Lower temperature for more consistent validation
            'max_tokens' => 800,
            'top_p' => null,
            'frequency_penalty' => null,
            'presence_penalty' => null,
            'system_prompt' => $this->getAuditorSystemPrompt(),
            'user_prompt_template' => $this->getAuditorUserPromptTemplate(),
            'response_schema' => [
                'type' => 'object',
                'properties' => [
                    'is_safe' => [
                        'type' => 'boolean',
                        'description' => 'True if content has no hallucinations or unsupported claims',
                    ],
                    'confidence_score' => [
                        'type' => 'number',
                        'description' => 'Confidence score between 0.0 and 1.0',
                    ],
                    'potential_hallucinations' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'field' => ['type' => 'string'],
                                'claim' => ['type' => 'string'],
                                'issue' => ['type' => 'string'],
                                'severity' => ['type' => 'string'],
                                'reason' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'required' => ['is_safe', 'confidence_score', 'potential_hallucinations'],
            ],
        ]);
    }

    /**
     * Get the Writer system prompt.
     */
    private function getWriterSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert SEO copywriter specializing in e-commerce product metadata.

Your task is to generate compelling, accurate SEO metadata (meta title, meta description, and meta keywords) for product pages.

CRITICAL RULES YOU MUST FOLLOW:

1. **ONLY USE PROVIDED DATA**: You must ONLY use information explicitly present in the product data provided. DO NOT invent features, specifications, benefits, or claims.

2. **BE SPECIFIC**: Focus on THIS exact product variant. Avoid generic descriptions that could apply to any similar product.

3. **NO ASSUMPTIONS**: Do not make assumptions about compatibility, use cases, or benefits not explicitly stated in the product data.

4. **NO MARKETING FLUFF**: Avoid subjective claims like "premium," "best," "revolutionary" unless these terms appear in the source description.

5. **FACTUAL ACCURACY**: Every claim in your output must be verifiable from the source product data.

6. **SEO BEST PRACTICES**:
   - Meta Title: 50-60 characters, include product name and 1-2 key features
   - Meta Description: 150-160 characters, compelling but factual
   - Meta Keywords: 5-10 comma-separated keywords from actual product attributes

OUTPUT FORMAT:
Your response must be valid JSON with these exact fields:
{
  "meta_title": "string (50-60 chars)",
  "meta_description": "string (150-160 chars)",
  "meta_keywords": "string (comma-separated)"
}
PROMPT;
    }

    /**
     * Get the Writer user prompt template.
     */
    private function getWriterUserPromptTemplate(): string
    {
        return <<<'PROMPT'
Generate SEO metadata for the following product.

**IMPORTANT**: Use ONLY the information provided below. Do not invent or assume any features, specifications, or benefits not explicitly stated.

Product Data:
```json
{{product_json}}
```

Generate SEO metadata following these requirements:

1. **Meta Title** (50-60 characters):
   - Include the product name
   - Include 1-2 key distinguishing features from the provided data
   - Make it click-worthy but factual

2. **Meta Description** (150-160 characters):
   - Summarize what this product IS (from provided data only)
   - Include 2-3 key features/specifications from the data
   - Write in a compelling but factual tone

3. **Meta Keywords** (5-10 keywords):
   - Extract keywords from actual product attributes
   - Include: product type, brand/model, key features, material, color, size
   - Comma-separated, lowercase

Respond with valid JSON only.
PROMPT;
    }

    /**
     * Get the Auditor system prompt.
     */
    private function getAuditorSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a strict fact-checker validating SEO content against source product data.

Your job is to identify ANY discrepancies, hallucinations, or unsupported claims in the generated SEO metadata.

VALIDATION RULES:

1. **Compare Every Claim**: Check that EVERY claim in the generated content is explicitly supported by the source product data.

2. **Flag Unsupported Claims**: If the generated content mentions a feature, specification, or benefit NOT present in the source, flag it as "unsupported_claim".

3. **Check Numbers**: Verify that all numerical values (dimensions, quantities, etc.) exactly match the source data. Flag mismatches as "numerical_mismatch".

4. **Check Specifications**: Ensure material, color, size, and other specs match exactly. Flag contradictions as "spec_mismatch".

5. **Identify Vague Claims**: Flag generic or vague claims that cannot be verified from the source as "ambiguous_claim".

6. **Severity Levels**:
   - **high**: Factual errors, wrong specifications, invented features
   - **medium**: Unsupported but plausible claims, minor exaggerations
   - **low**: Vague language, minor optimization opportunities

7. **Be Conservative**: When in doubt, flag it. Better to have a human review than to publish inaccurate content.

OUTPUT FORMAT:
{
  "is_safe": boolean (true only if NO issues found),
  "confidence_score": number (0.0-1.0, where 1.0 = perfect match),
  "potential_hallucinations": [
    {
      "field": "meta_title|meta_description|meta_keywords",
      "claim": "the specific text that's problematic",
      "issue": "unsupported_claim|numerical_mismatch|spec_mismatch|ambiguous_claim",
      "severity": "high|medium|low",
      "reason": "explanation of why this is flagged"
    }
  ]
}
PROMPT;
    }

    /**
     * Get the Auditor user prompt template.
     */
    private function getAuditorUserPromptTemplate(): string
    {
        return <<<'PROMPT'
Validate the following generated SEO content against the original product data.

**Original Product Data:**
```json
{{product_json}}
```

**Generated SEO Content:**
```json
{{generated_content}}
```

Perform a strict fact-check:

1. Compare the generated meta_title, meta_description, and meta_keywords against the source product data.

2. Flag ANY claim that is:
   - Not explicitly stated in the source data
   - A numerical mismatch (wrong dimensions, counts, etc.)
   - A specification contradiction (wrong material, color, size, etc.)
   - Vague or generic without clear source support

3. Assign confidence score:
   - 1.0 = Perfect, all claims fully supported
   - 0.9-0.99 = Excellent, minor wording differences
   - 0.75-0.89 = Good, some interpretive claims but reasonable
   - 0.5-0.74 = Questionable, multiple unsupported claims
   - <0.5 = Poor, significant hallucinations or errors

4. Set is_safe to true ONLY if confidence >= 0.9 AND no high-severity issues.

Respond with valid JSON following the specified schema.
PROMPT;
    }
}
