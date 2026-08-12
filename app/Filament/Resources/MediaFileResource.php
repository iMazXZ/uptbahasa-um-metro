<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaFileResource\Pages;
use App\Models\MediaFile;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaFileResource extends BaseResource
{
    protected static ?string $model = MediaFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Media';

    public static function form(Form $form): Form
    {
        // Form tidak dipakai (semua dikelola via aksi tabel)
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_modified_at', 'desc')
            ->filters([
                SelectFilter::make('directory')
                    ->label('Folder')
                    ->multiple()
                    ->options(fn () => MediaFile::query()
                        ->select('directory')
                        ->selectRaw('COUNT(*) as total')
                        ->whereNotNull('directory')
                        ->groupBy('directory')
                        ->orderBy('directory')
                        ->get()
                        ->mapWithKeys(fn ($row) => [$row->directory => "{$row->directory} ({$row->total})"])
                        ->toArray())
                    ->query(function ($query, $state) {
                        $dirs = array_filter((array) ($state['value'] ?? $state));
                        if (empty($dirs)) {
                            return $query;
                        }

                        return $query->whereIn('directory', $dirs);
                    }),
                SelectFilter::make('extension')
                    ->label('Ekstensi')
                    ->multiple()
                    ->options([
                        'webp' => 'webp',
                        'jpg' => 'jpg/jpeg',
                        'png' => 'png',
                        'gif' => 'gif',
                        'svg' => 'svg',
                    ])
                    ->query(function ($query, $state) {
                        $exts = array_filter((array) ($state['value'] ?? $state));
                        if (empty($exts)) {

                            return $query;
                        }

                        return $query->where(function ($q) use ($exts) {
                            foreach ($exts as $ext) {
                                $ext = (string) $ext;
                                if ($ext === 'jpg') {
                                    $q->orWhere(function ($qq) {
                                        $qq->where('filename', 'like', '%.jpg')
                                           ->orWhere('filename', 'like', '%.jpeg');
                                    });
                                } else {
                                    $q->orWhere('filename', 'like', "%.{$ext}");
                                }
                            }
                        });
                    }),
                Filter::make('recent')
                    ->label('30 hari terakhir')
                    ->query(fn ($query) => $query->where('last_modified_at', '>=', now()->subDays(30))),
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                Panel::make([
                    Tables\Columns\ImageColumn::make('path')
                        ->label('Preview')
                        ->height(180)
                        ->extraImgAttributes([
                            'class' => 'w-full h-44 object-cover',
                            'loading' => 'lazy',
                        ])
                        ->getStateUsing(fn (MediaFile $record): string => Storage::disk($record->disk)->url($record->path))
                        ->columnSpanFull(),

                    Stack::make([
                        Tables\Columns\TextColumn::make('filename')
                            ->label('Nama File')
                            ->searchable()
                            ->limit(28)
                            ->tooltip(fn (MediaFile $record) => $record->filename)
                            ->extraAttributes([
                                'class' => 'text-[13px] font-semibold text-gray-800 leading-5',
                            ]),

                        \Filament\Tables\Columns\Layout\Grid::make(2)
                            ->schema([
                                Tables\Columns\TextColumn::make('directory')
                                    ->label('Folder')
                                    ->default('/')
                                    ->badge()
                                    ->color(fn (MediaFile $record): string => static::folderColor($record->directory))
                                    ->extraAttributes([
                                        'class' => 'min-w-0 [&>div]:truncate [&>div]:max-w-full',
                                    ]),
                                Tables\Columns\TextColumn::make('size')
                                    ->label('Ukuran')
                                    ->formatStateUsing(fn (?int $state) => $state ? static::humanBytes($state) : '-')
                                    ->extraAttributes([
                                        'class' => 'text-[11px] text-gray-500 text-right whitespace-nowrap',
                                    ]),
                            ])
                            ->extraAttributes(['class' => 'flex items-center justify-between gap-2']),

                        Tables\Columns\TextColumn::make('last_modified_at')
                            ->label('Diubah')
                            ->dateTime('d M Y H:i')
                            ->extraAttributes([
                                'class' => 'text-[11px] text-gray-400',
                            ]),
                    ])->space(2),
                ])
                    ->extraAttributes([
                        'class' => 'rounded-xl overflow-hidden ring-1 ring-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Buka')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (MediaFile $record) => Storage::disk($record->disk)->url($record->path))
                    ->openUrlInNewTab()
                    ->tooltip('Buka di tab baru')
                    ->iconButton(),
                Tables\Actions\Action::make('rename')
                    ->label('Ganti Nama')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        TextInput::make('new_name')
                            ->label('Nama file (tanpa folder)')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (MediaFile $record, array $data) {
                        $disk = Storage::disk($record->disk);

                        $directory = $record->directory ? trim($record->directory, '/') . '/' : '';

                        $ext = pathinfo($record->filename, PATHINFO_EXTENSION);
                        $base = pathinfo($data['new_name'], PATHINFO_FILENAME);
                        $base = Str::slug($base ?: $record->filename, '_');
                        $newFilename = $ext ? "{$base}.{$ext}" : $base;
                        $newPath = $directory . $newFilename;

                        if ($newPath === $record->path) {
                            return;
                        }

                        if ($disk->exists($newPath)) {
                            Notification::make()
                                ->title('Nama sudah dipakai di folder ini.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $disk->move($record->path, $newPath);

                        $record->path = $newPath;
                        $record->filename = $newFilename;
                        $record->last_modified_at = now();
                        $record->save();
                    })
                    ->tooltip('Ganti nama file')
                    ->iconButton(),
                Tables\Actions\Action::make('delete_file')
                    ->label('Hapus')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->action(function (MediaFile $record) {
                        $disk = Storage::disk($record->disk);
                        if ($disk->exists($record->path)) {
                            $disk->delete($record->path);
                        }

                        $record->delete();
                    })
                    ->tooltip('Hapus file')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('delete_selected')
                    ->label('Hapus Terpilih')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function (MediaFile $record) {
                            $disk = Storage::disk($record->disk);
                            if ($disk->exists($record->path)) {
                                $disk->delete($record->path);
                            }
                            $record->delete();
                        });
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync')
                    ->label('Sync Storage')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        [$added, $updated, $removed] = static::syncFromStorage();

                        Notification::make()
                            ->title('Sinkronisasi selesai')
                            ->body("Ditambahkan {$added}, diperbarui {$updated}, dihapus {$removed}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaFiles::route('/'),
        ];
    }

    public static function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    protected static function folderColor(?string $directory): string
    {
        $colors = ['gray', 'primary', 'success', 'warning', 'info', 'danger'];

        if (! $directory) {
            return 'gray';
        }

        $hash = crc32($directory) % count($colors);

        return $colors[$hash];
    }

    /**
        * @return array{int,int,int} [added, updated, removed]
        */
    public static function syncFromStorage(): array
    {
        $diskName = 'public';
        $disk = Storage::disk($diskName);
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'];

        $files = collect($disk->allFiles())
            ->filter(function (string $path) use ($extensions) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                return in_array($ext, $extensions, true);
            });

        $existingPaths = MediaFile::where('disk', $diskName)->pluck('id', 'path');

        $added = 0;
        $updated = 0;

        foreach ($files as $path) {
            $full = $disk->path($path);
            if (!is_file($full)) {
                continue;
            }

            $size = $disk->size($path);
            $mime = $disk->mimeType($path);
            $modifiedAt = $disk->lastModified($path);

            [$width, $height] = [null, null];
            if (str_ends_with(strtolower($mime ?? ''), 'svg')) {
                [$width, $height] = [null, null];
            } else {
                try {
                    $dimensions = @getimagesize($full);
                    if ($dimensions) {
                        [$width, $height] = [$dimensions[0] ?? null, $dimensions[1] ?? null];
                    }
                } catch (\Throwable $e) {
                    [$width, $height] = [null, null];
                }
            }

            $payload = [
                'disk' => $diskName,
                'path' => $path,
                'filename' => basename($path),
                'directory' => trim(dirname($path), '.'),
                'mime_type' => $mime,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'last_modified_at' => Carbon::createFromTimestamp($modifiedAt),
            ];

            if (isset($existingPaths[$path])) {
                $record = MediaFile::find($existingPaths[$path]);
                if ($record) {
                    $record->update($payload);
                    $updated++;
                }
            } else {
                MediaFile::create($payload);
                $added++;
            }
        }

        $removed = 0;
        $storedPaths = MediaFile::where('disk', $diskName)->pluck('path');
        foreach ($storedPaths as $storedPath) {
            if (! $disk->exists($storedPath)) {
                MediaFile::where('disk', $diskName)->where('path', $storedPath)->delete();
                $removed++;
            }
        }

        return [$added, $updated, $removed];
    }
}
