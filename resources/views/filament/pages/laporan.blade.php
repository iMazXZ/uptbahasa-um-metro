<x-filament::page>
    
</x-filament::page>

@push('scripts')
<script>
    window.addEventListener('open-pdf-new-tab', event => {
        window.open(event.detail.url, '_blank');
    });
</script>
@endpush