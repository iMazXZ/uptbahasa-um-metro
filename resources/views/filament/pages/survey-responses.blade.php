<x-filament::page>
    {{-- Stats Cards (inline dengan flexbox) --}}
    @php($stats = $this->getStats())
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        {{-- Total Response --}}
        <div style="flex: 1; min-width: 180px; background: white; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="padding: 0.625rem; background: #eff6ff; border-radius: 0.5rem;">
                    <x-heroicon-o-document-check style="width: 1.25rem; height: 1.25rem; color: #2563eb;" />
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Total Response</p>
                    <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        {{-- Rata-rata Skor --}}
        <div style="flex: 1; min-width: 180px; background: white; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="padding: 0.625rem; background: #fef3c7; border-radius: 0.5rem;">
                    <x-heroicon-o-star style="width: 1.25rem; height: 1.25rem; color: #d97706;" />
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Rata-rata Skor</p>
                    <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ $stats['avg'] }}</p>
                </div>
            </div>
        </div>

        {{-- Hari Ini --}}
        <div style="flex: 1; min-width: 180px; background: white; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="padding: 0.625rem; background: #dcfce7; border-radius: 0.5rem;">
                    <x-heroicon-o-calendar style="width: 1.25rem; height: 1.25rem; color: #16a34a;" />
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Hari Ini</p>
                    <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($stats['today']) }}</p>
                </div>
            </div>
        </div>

        {{-- Pending/Draft (Clickable) --}}
        <button type="button" 
                wire:click="showPendingDetail"
                style="flex: 1; min-width: 180px; background: white; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); cursor: pointer; text-align: left; transition: box-shadow 0.2s;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" 
                onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="padding: 0.625rem; background: #fef3c7; border-radius: 0.5rem;">
                    <x-heroicon-o-clock style="width: 1.25rem; height: 1.25rem; color: #d97706;" />
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">Pending/Draft <span style="font-size: 0.625rem; color: #9ca3af;">(klik)</span></p>
                    <p style="font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>
        </button>
    </div>

    {{-- Modal Pending Detail --}}
    @if($this->showPendingModal)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;" wire:click="closePendingModal">
        <div style="background: white; border-radius: 1rem; padding: 1.5rem; max-width: 600px; width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;" wire:click.stop>
            {{-- Header --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0;">
                        Response Pending/Draft
                    </h3>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                        Response yang belum di-submit
                    </p>
                </div>
                <button type="button" wire:click="closePendingModal" style="padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem; border: none; cursor: pointer;">
                    <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem; color: #6b7280;" />
                </button>
            </div>
            
            {{-- Content --}}
            <div style="overflow-y: auto; flex: 1;">
                @if(count($this->pendingResponses) > 0)
                <p style="font-size: 0.75rem; color: #9ca3af; margin: 0 0 0.75rem 0;">
                    Menampilkan {{ count($this->pendingResponses) }} dari {{ $this->pendingTotal }} data
                </p>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">User</th>
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Survey</th>
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->pendingResponses as $item)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.75rem;">{{ $item['user_name'] }}</td>
                            <td style="padding: 0.75rem; color: #6b7280;">{{ $item['survey_title'] }}</td>
                            <td style="padding: 0.75rem; color: #9ca3af; font-size: 0.75rem;">{{ $item['created_at'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                {{-- Load More Button --}}
                @if(count($this->pendingResponses) < $this->pendingTotal)
                <div style="text-align: center; margin-top: 1rem;">
                    <button type="button" 
                            wire:click="loadMorePending"
                            style="padding: 0.5rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
                            onmouseover="this.style.background='#1d4ed8'" 
                            onmouseout="this.style.background='#2563eb'">
                        Muat Lebih Banyak ({{ $this->pendingTotal - count($this->pendingResponses) }} tersisa)
                    </button>
                </div>
                @endif
                @else
                <p style="text-align: center; color: #9ca3af; padding: 2rem;">Tidak ada response pending</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        {{ $this->table }}
    </div>
</x-filament::page>