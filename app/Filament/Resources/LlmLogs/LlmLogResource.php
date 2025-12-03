<?php

namespace App\Filament\Resources\LlmLogs;

use App\Filament\Resources\LlmLogs\Pages;
use App\Filament\Resources\LlmLogs\Schemas\LlmLogInfolist;
use App\Filament\Resources\LlmLogs\Tables\LlmLogsTable;
use App\Models\LlmLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LlmLogResource extends Resource
{
    protected static ?string $model = LlmLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'LLM API Logs';

    protected static ?string $modelLabel = 'LLM Log';

    protected static ?string $pluralModelLabel = 'LLM Logs';

    protected static ?int $navigationSort = 30;

    protected static bool $shouldSkipAuthorization = true;

    public static function table(Table $table): Table
    {
        return LlmLogsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LlmLogInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLlmLogs::route('/'),
            'view' => Pages\ViewLlmLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Logs are read-only
    }

    public static function canEdit($record): bool
    {
        return false; // Logs are read-only
    }

    public static function canDelete($record): bool
    {
        return false; // Logs are read-only (but could be allowed for cleanup)
    }
}
