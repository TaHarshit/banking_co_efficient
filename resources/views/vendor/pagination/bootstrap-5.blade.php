@if ($paginator->hasPages())
    <nav class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100 py-3 mt-2" aria-label="Page navigation">
        {{-- Results Counter --}}
        <div class="pagination-info text-muted small">
            @if (method_exists($paginator, 'firstItem') && $paginator->firstItem())
                @if (app()->getLocale() == 'fr')
                    <span>Affichage de</span>
                    <span class="fw-bold text-dark">{{ $paginator->firstItem() }}</span>
                    <span>à</span>
                    <span class="fw-bold text-dark">{{ $paginator->lastItem() }}</span>
                    <span>sur</span>
                    <span class="fw-bold text-dark">{{ $paginator->total() }}</span>
                    <span>résultats</span>
                @else
                    <span>Showing</span>
                    <span class="fw-bold text-dark">{{ $paginator->firstItem() }}</span>
                    <span>to</span>
                    <span class="fw-bold text-dark">{{ $paginator->lastItem() }}</span>
                    <span>of</span>
                    <span class="fw-bold text-dark">{{ $paginator->total() }}</span>
                    <span>results</span>
                @endif
            @endif
        </div>

        {{-- Page Buttons --}}
        <ul class="pagination pagination-sm mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @if (isset($elements))
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
