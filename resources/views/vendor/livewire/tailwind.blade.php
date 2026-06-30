@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation">
            <span class="relative z-0 inline-flex rtl:flex-row-reverse gap-px">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true">
                        <span class="relative inline-flex items-center px-2 py-1.5 text-on-surface-variant bg-surface border border-outline-variant cursor-not-allowed rounded-l-lg opacity-40" aria-hidden="true">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    </span>
                @else
                    <button type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="relative inline-flex items-center px-2 py-1.5 text-on-surface-variant bg-surface border border-outline-variant rounded-l-lg hover:bg-surface-container-high hover:text-primary transition-colors"
                        aria-label="{{ __('pagination.previous') }}"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </button>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-3 py-1.5 text-body-sm text-on-surface-variant bg-surface border border-outline-variant cursor-default">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3 py-1.5 text-body-sm font-semibold text-white bg-primary border border-primary cursor-default">{{ $page }}</span>
                                    </span>
                                @else
                                    <button type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                        class="relative inline-flex items-center px-3 py-1.5 text-body-sm text-on-surface bg-surface border border-outline-variant hover:bg-surface-container-high hover:text-primary transition-colors"
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    >{{ $page }}</button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        class="relative inline-flex items-center px-2 py-1.5 text-on-surface-variant bg-surface border border-outline-variant rounded-r-lg hover:bg-surface-container-high hover:text-primary transition-colors"
                        aria-label="{{ __('pagination.next') }}"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </button>
                @else
                    <span aria-disabled="true">
                        <span class="relative inline-flex items-center px-2 py-1.5 text-on-surface-variant bg-surface border border-outline-variant cursor-not-allowed rounded-r-lg opacity-40" aria-hidden="true">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                    </span>
                @endif

            </span>
        </nav>
    @endif
</div>
