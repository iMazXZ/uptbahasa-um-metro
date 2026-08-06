@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-8">

        {{-- MOBILE (Prev / Next) --}}
        <div class="flex justify-center sm:hidden">
            <span class="inline-flex items-center gap-2">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="px-4 py-2 rounded-md bg-gray-200 text-gray-500 select-none">&laquo; Prev</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="px-4 py-2 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                        &laquo; Prev
                    </a>
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="px-4 py-2 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Next &raquo;
                    </a>
                @else
                    <span class="px-4 py-2 rounded-md bg-gray-200 text-gray-500 select-none">Next &raquo;</span>
                @endif
            </span>
        </div>

        {{-- DESKTOP (Numbers) --}}
        <div class="hidden sm:flex sm:items-center sm:justify-end">
            <div>
                <span class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">

                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex items-center px-2 py-2 text-gray-400 cursor-default select-none rounded-l-md">&laquo;</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            &laquo;
                        </a>
                    @endif

                    {{-- Numbers --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="relative inline-flex items-center px-4 py-2 border border-indigo-600 bg-indigo-600 text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            &raquo;
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-2 py-2 text-gray-400 cursor-default select-none rounded-r-md">&raquo;</span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
