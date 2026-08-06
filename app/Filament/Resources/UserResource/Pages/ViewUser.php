<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // ========== PROFIL UTAMA ==========
            Section::make('Profil')
                ->schema([
                    ImageEntry::make('image')
                        ->label('Foto')
                        ->circular()
                        ->grow(false),

                    Grid::make(2)->schema([
                        TextEntry::make('name')
                            ->label('Nama Lengkap')
                            ->weight('bold'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->copyMessage('Email disalin'),

                        TextEntry::make('whatsapp')
                            ->label('WhatsApp')
                            ->copyable()
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return null;
                                $verified = $record->whatsapp_verified_at ? ' ✓' : '';
                                return $state . $verified;
                            })
                            ->color(fn ($record) => $record->whatsapp_verified_at ? 'success' : 'gray')
                            ->placeholder('—'),

                        TextEntry::make('srn')
                            ->label('SRN / NPM')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('prody.name')
                            ->label('Program Studi')
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('year')
                            ->label('Angkatan')
                            ->placeholder('—'),

                        TextEntry::make('nomor_grup_bl')
                            ->label('No. Grup BL')
                            ->formatStateUsing(fn (mixed $state) => $state ? ('Grup ' . $state) : '—')
                            ->placeholder('—'),
                    ])->columnSpanFull(),

                    // Roles sebagai satu baris, aman & ringan
                    TextEntry::make('roles_list')
                        ->label('Roles')
                        ->state(fn ($record) => $record->roles?->pluck('name')->implode(', ') ?: '—')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->compact(),

            // ========== TIMESTAMP ==========
            Section::make('Timestamp')
                ->columns(3)
                ->schema([
                    TextEntry::make('email_verified_at')
                        ->label('Verifikasi Email')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Belum diverifikasi'),

                    TextEntry::make('created_at')
                        ->label('Dibuat')
                        ->dateTime('d M Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Diubah')
                        ->dateTime('d M Y H:i'),
                ]),

            // ========== ID & METADATA (ADMIN SAJA) ==========
            Section::make('ID & Metadata Teknis')
                ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false)
                ->columns(4)
                ->schema([
                    TextEntry::make('id')
                        ->label('ID User')
                        ->formatStateUsing(fn (mixed $state) => $state ? ('#' . $state) : '—')
                        ->copyable(),

                    TextEntry::make('prody_id')
                        ->label('Prody ID')
                        ->copyable(),

                    TextEntry::make('year')
                        ->label('Angkatan')
                        ->placeholder('—'),

                    TextEntry::make('nilaibasiclistening')
                        ->label('Nilai BL (legacy)')
                        ->placeholder('—'),
                ]),

            // ========== NILAI AKADEMIK ==========
            Section::make('Nilai Akademik')
                ->description('Nilai Basic Listening, Interactive Class, atau Interactive Bahasa Arab')
                ->columns(3)
                ->schema([
                    TextEntry::make('nilaibasiclistening')
                        ->label('Nilai Basic Listening')
                        ->placeholder('—')
                        ->visible(fn ($record) => 
                            $record->prody?->name !== 'Pendidikan Bahasa Inggris'
                        ),

                    // Interactive Class (6 semester) - Pendidikan Bahasa Inggris
                    Grid::make(6)
                        ->schema([
                            TextEntry::make('interactive_class_1')
                                ->label('IC Sem 1')
                                ->placeholder('—'),
                            TextEntry::make('interactive_class_2')
                                ->label('IC Sem 2')
                                ->placeholder('—'),
                            TextEntry::make('interactive_class_3')
                                ->label('IC Sem 3')
                                ->placeholder('—'),
                            TextEntry::make('interactive_class_4')
                                ->label('IC Sem 4')
                                ->placeholder('—'),
                            TextEntry::make('interactive_class_5')
                                ->label('IC Sem 5')
                                ->placeholder('—'),
                            TextEntry::make('interactive_class_6')
                                ->label('IC Sem 6')
                                ->placeholder('—'),
                        ])
                        ->columnSpanFull()
                        ->visible(fn ($record) => 
                            $record->prody?->name === 'Pendidikan Bahasa Inggris'
                        ),

                    // Interactive Bahasa Arab (2 field) - 3 Prodi Islam
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('interactive_bahasa_arab_1')
                                ->label('Bahasa Arab 1')
                                ->placeholder('—'),
                            TextEntry::make('interactive_bahasa_arab_2')
                                ->label('Bahasa Arab 2')
                                ->placeholder('—'),
                        ])
                        ->columnSpanFull()
                        ->visible(fn ($record) => 
                            in_array($record->prody?->name, [
                                'Komunikasi dan Penyiaran Islam',
                                'Pendidikan Agama Islam',
                                'Pendidikan Islam Anak Usia Dini',
                            ])
                        ),
                ])
                ->visible(fn ($record) => 
                    $record->year && (int) $record->year <= 2024
                ),
        ]);
    }
}
