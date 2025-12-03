<?php

namespace App\Filament\Resources\LlmLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class LlmLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Overview
                TextEntry::make('agent_type')
                    ->label('Agent Type')
                    ->badge()
                    ->color(fn ($state) => $state === 'writer' ? 'primary' : 'success')
                    ->weight(FontWeight::Bold),

                TextEntry::make('model')
                    ->label('Model')
                    ->badge()
                    ->color('gray'),

                TextEntry::make('success')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Success' : 'Failed')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->weight(FontWeight::Bold),

                TextEntry::make('response_status')
                    ->label('HTTP Status')
                    ->badge()
                    ->color(fn ($state) => $state >= 200 && $state < 300 ? 'success' : 'danger'),

                TextEntry::make('execution_time_ms')
                    ->label('Execution Time')
                    ->formatStateUsing(fn ($state) => $state ? ($state < 1000 ? $state.'ms' : round($state / 1000, 2).'s') : 'N/A'),

                TextEntry::make('created_at')
                    ->label('Timestamp')
                    ->dateTime(),

                // Context
                TextEntry::make('product.sku')
                    ->label('Product SKU')
                    ->copyable(),

                TextEntry::make('product.name')
                    ->label('Product Name')
                    ->columnSpanFull(),

                TextEntry::make('seoDraft.id')
                    ->label('SEO Draft ID')
                    ->url(fn ($record) => $record->seo_draft_id ? route('filament.admin.resources.seo-drafts.edit', $record->seo_draft_id) : null)
                    ->default('N/A'),

                TextEntry::make('seoJob.id')
                    ->label('SEO Job ID')
                    ->url(fn ($record) => $record->seo_job_id ? route('filament.admin.resources.seo-jobs.edit', $record->seo_job_id) : null)
                    ->default('N/A'),

                TextEntry::make('llmConfiguration.name')
                    ->label('LLM Configuration')
                    ->default('Default Prompts')
                    ->columnSpanFull(),

                // Token Usage
                TextEntry::make('prompt_tokens')
                    ->label('Prompt Tokens')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'N/A'),

                TextEntry::make('completion_tokens')
                    ->label('Completion Tokens')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'N/A'),

                TextEntry::make('total_tokens')
                    ->label('Total Tokens')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'N/A')
                    ->weight(FontWeight::Bold),

                // API Details
                TextEntry::make('api_url')
                    ->label('API URL')
                    ->copyable()
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 100).'...' : 'N/A'),

                // System Prompt
                TextEntry::make('system_prompt')
                    ->label('System Prompt')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto">'
                            .htmlspecialchars($state)
                            .'</div>';
                    }),

                // User Prompt
                TextEntry::make('user_prompt')
                    ->label('User Prompt')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto">'
                            .htmlspecialchars($state)
                            .'</div>';
                    }),

                // Product Data
                TextEntry::make('product_data')
                    ->label('Product Data (Sent to LLM)')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto">'
                            .htmlspecialchars($json)
                            .'</div>';
                    }),

                // Request Body
                TextEntry::make('request_body')
                    ->label('API Request Body')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        // Try to pretty print if it's JSON
                        $decoded = json_decode($state);
                        if ($decoded) {
                            $state = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto">'
                            .htmlspecialchars($state)
                            .'</div>';
                    }),

                // Response Body
                TextEntry::make('response_body')
                    ->label('API Response Body')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        // Try to pretty print if it's JSON
                        $decoded = json_decode($state);
                        if ($decoded) {
                            $state = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto">'
                            .htmlspecialchars($state)
                            .'</div>';
                    }),

                // Parsed Output
                TextEntry::make('parsed_output')
                    ->label('Parsed Output')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
                        }

                        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        return '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 font-mono text-xs whitespace-pre-wrap">'
                            .htmlspecialchars($json)
                            .'</div>';
                    }),

                // Error Message (if any)
                TextEntry::make('error_message')
                    ->label('Error Details')
                    ->columnSpanFull()
                    ->html()
                    ->visible(fn ($record) => !empty($record->error_message))
                    ->formatStateUsing(function ($state) {
                        return '<div class="bg-red-50 p-4 rounded-lg border border-red-200 text-red-900 font-mono text-xs whitespace-pre-wrap">'
                            .htmlspecialchars($state)
                            .'</div>';
                    }),
            ]);
    }
}
