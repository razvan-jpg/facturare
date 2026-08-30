@if(($paginator ?? null) && $paginator->hasPages())
    <div class="{{ $class ?? 'mb-4' }}">{{ $paginator->links() }}</div>
@endif
