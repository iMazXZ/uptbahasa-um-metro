<span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none rounded-full
    @if($color === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
    @elseif($color === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
    @endif
">
    {{ $grade }}
</span>
