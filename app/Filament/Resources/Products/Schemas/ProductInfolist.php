<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('sku')
                    ->label('SKU')
                    ->copyable()
                    ->weight(FontWeight::Bold),

                TextEntry::make('name')
                    ->label('Product Name')
                    ->weight(FontWeight::Bold)
                    ->columnSpanFull(),

                TextEntry::make('description')
                    ->label('Description')
                    ->html()
                    ->default('No description available')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => $state ?? 'No description available'),

                TextEntry::make('created_at')
                    ->label('Imported At')
                    ->dateTime('M j, Y g:i A')
                    ->since(),

                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->since(),

                TextEntry::make('attributes_display')
                    ->label('Magento Attributes (Raw Data)')
                    ->columnSpanFull()
                    ->html()
                    ->state(function ($record) {
                        if (!is_array($record->attributes)) {
                            return '<p class="text-gray-500">No attributes</p>';
                        }

                        $html = '<div class="space-y-1 text-sm font-mono">';

                        foreach ($record->attributes as $attr) {
                            if (!isset($attr['attribute_code']) || !isset($attr['value'])) {
                                continue;
                            }

                            $code = htmlspecialchars($attr['attribute_code']);
                            $value = $attr['value'];

                            // Handle array values
                            if (is_array($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                            }

                            // Escape and limit length
                            $value = htmlspecialchars((string) $value);
                            if (strlen($value) > 200) {
                                $value = substr($value, 0, 200) . '...';
                            }

                            $html .= '<div class="flex gap-2">';
                            $html .= '<span class="text-gray-600 min-w-[200px]">' . $code . ':</span>';
                            $html .= '<span class="text-gray-900 break-all">' . $value . '</span>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return $html;
                    }),
            ]);
    }
}
