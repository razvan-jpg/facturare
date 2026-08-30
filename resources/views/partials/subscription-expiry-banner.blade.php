@php
    $expiryNotes = auth()->user()
        ?->unreadNotifications
        ->filter(fn ($n) => ($n->data['kind'] ?? null) === 'subscription_expiry')
        ->values() ?? collect();
@endphp
@foreach($expiryNotes as $note)
    @php($data = $note->data)
    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-sm font-bold text-amber-950">{{ $data['title'] ?? 'Abonament care expiră' }}</div>
                <p class="text-sm text-amber-900/90 mt-0.5">{{ $data['body'] ?? '' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if(! empty($data['order_url']))
                    <a href="{{ $data['order_url'] }}" class="dc-btn-primary text-xs px-3 py-1.5">{{ __('Comandă') }}</a>
                @endif
                <form method="POST" action="{{ route('notifications.read', $note->id) }}">
                    @csrf
                    <button type="submit" class="dc-btn-secondary text-xs px-3 py-1.5">Am înțeles</button>
                </form>
            </div>
        </div>
    </div>
@endforeach
