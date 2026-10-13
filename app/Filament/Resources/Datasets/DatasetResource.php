<?php

namespace App\Filament\Resources\Datasets;

use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\Datasets\Pages\ListDatasets;
use App\Filament\Resources\Datasets\Pages\ViewDataset;
use App\Filament\Resources\Datasets\Schemas\DatasetForm;
use App\Filament\Resources\Datasets\Schemas\DatasetInfolist;
use App\Filament\Resources\Datasets\Tables\DatasetsTable;
use App\Models\Dataset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DatasetResource extends Resource
{
    protected static ?string $model = Dataset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'CORE PLATFORM';

    protected static ?string $navigationLabel = 'Datasets Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'category'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Dataset $record */
        return [
            'Category' => $record->category,
            'Rows' => number_format($record->record_count),
            'Quality' => "{$record->health_score}%",
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return DatasetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DatasetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DatasetsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDatasets::route('/'),
            'create' => CreateDataset::route('/create'),
            'view' => ViewDataset::route('/{record}'),
            'edit' => EditDataset::route('/{record}/edit'),
        ];
    }
}
