<x-filament::page>
    <div class="p-6 bg-white rounded-lg shadow">        
        @if($this->record)
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div><strong>Survey:</strong> {{ $this->record->survey->title }}</div>
            <div><strong>Mahasiswa:</strong> {{ $this->record->user->name }}</div>
            <div><strong>Submitted:</strong> {{ $this->record->submitted_at->format('d M Y, H:i') }}</div>
            <div><strong>Total Jawaban:</strong> {{ $this->record->answers_count }}</div>
            <div><strong> </strong></div>
        </div>
        @endif

        {{ $this->table }}
    </div>
</x-filament::page>