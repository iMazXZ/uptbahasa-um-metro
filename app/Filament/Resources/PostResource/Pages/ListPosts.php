<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\NewsCategoryResource;
use App\Filament\Resources\PostResource;
use App\Models\Post;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    public function getTabs(): array
    {
        $postCountsByType = Post::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $totalPosts = (int) $postCountsByType->sum();

        $tabStyles = [
            'all' => [
                'icon' => 'heroicon-o-megaphone',
                'badgeColor' => 'gray',
            ],
            'news' => [
                'icon' => 'heroicon-o-document-text',
                'badgeColor' => 'info',
            ],
            'career' => [
                'icon' => 'heroicon-o-briefcase',
                'badgeColor' => 'primary',
            ],
            'schedule' => [
                'icon' => 'heroicon-o-calendar-days',
                'badgeColor' => 'warning',
            ],
            'scores' => [
                'icon' => 'heroicon-o-chart-bar',
                'badgeColor' => 'success',
            ],
            'service' => [
                'icon' => 'heroicon-o-briefcase',
                'badgeColor' => 'primary',
            ],
        ];

        $tabs = [
            'all' => Tab::make('Semua')
                ->icon($tabStyles['all']['icon'])
                ->badge($totalPosts)
                ->badgeColor($tabStyles['all']['badgeColor']),
        ];

        foreach (Post::TYPES as $type => $label) {
            $style = $tabStyles[$type] ?? ['icon' => 'heroicon-o-megaphone', 'badgeColor' => 'gray'];

            $tabs[$type] = Tab::make($label)
                ->icon($style['icon'])
                ->badge((int) ($postCountsByType[$type] ?? 0))
                ->badgeColor($style['badgeColor'])
                ->query(fn (Builder $query): Builder => $query->where('type', $type));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'schedule';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_news_categories')
                ->label('Kelola Kategori Berita')
                ->icon('heroicon-o-tag')
                ->color('gray')
                ->url(fn (): string => NewsCategoryResource::getUrl())
                ->visible(fn (): bool => NewsCategoryResource::canViewAny()),
            Actions\CreateAction::make(),
        ];
    }

    protected function configureCreateAction(CreateAction | Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        $action
            ->slideOver()
            ->url(null)
            ->modalHeading('Buat Posting Informasi')
            ->modalSubmitActionLabel('Simpan Postingan')
            ->modalWidth('7xl');
    }
}
