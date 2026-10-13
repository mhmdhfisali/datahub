<?php

namespace App\Filament\Resources\Datasets\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DatasetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi & Metadata Dataset')
                    ->icon('heroicon-o-document-text')
                    ->description('Masukkan judul dan kategori dataset yang ingin diolah.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Dataset')
                            ->placeholder('Contoh: Data Laskar Angkatan 4')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category')
                            ->label('Kategori')
                            ->placeholder('Contoh: Laskar / Karang Taruna')
                            ->required()
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Section::make('Impor Data Spreadsheet / JSON')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->description('Unggah file Excel/CSV atau tempel teks JSON secara manual.')
                    ->schema([
                        Forms\Components\FileUpload::make('excel_file')
                            ->label('Upload File Excel / CSV (.xlsx, .xls, .csv)')
                            ->disk('local')
                            ->directory('excel-imports')
                            ->rules(['mimes:xlsx,xls,csv,txt'])
                            ->preserveFilenames()
                            ->maxSize(25600) // 25MB
                            ->helperText('Mendukung format Microsoft Excel (.xlsx, .xls) dan CSV (.csv) dengan deteksi kolom dan tipe data otomatis.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('payload')
                            ->label('Atau Paste Kode JSON Manual (Opsional)')
                            ->rows(7)
                            ->placeholder("[\n  {\n    \"nama\": \"Budi Santoso\",\n    \"ukuran_baju\": \"L\",\n    \"kota\": \"Jakarta\"\n  }\n]")
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) && ! empty($state) ? json_decode($state, true) : $state)
                            ->helperText('Jika kamu sudah mengunggah file Excel/CSV, kolom ini boleh dikosongkan. Jika diisi, sistem akan otomatis melakukan normalisasi nama atribut.')
                            ->extraInputAttributes(['class' => 'font-mono text-xs leading-relaxed tracking-tight bg-gray-50/50 dark:bg-gray-900/60'])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('mask_pii')
                            ->label('Aktifkan Proteksi PII (Masking Otomatis NIK, Email & Nomor HP)')
                            ->helperText('Jika diaktifkan, data sensitif akan disamarkan secara otomatis demi privasi dan kepatuhan GDPR / UU PDP.')
                            ->default(false)
                            ->dehydrated(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
