@php
    $supportCompanyId = session('admin_support_company_id');
    $supportUserId = session('admin_support_user_id');
    $ctx = app(\App\Services\CompanyContext::class);
    $showSupport = auth()->user()?->is_admin
        && $supportCompanyId
        && $ctx->isAdminSupportMode()
        && (int) ($currentCompany->id ?? 0) === (int) $supportCompanyId;
@endphp
@if($showSupport)
    @php
        $supportUser = \App\Models\User::query()->find($supportUserId);
    @endphp
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-950 px-4 py-3 text-sm flex flex-wrap items-center justify-between gap-2">
        <div>
            <span class="font-semibold">Mod suport</span>
            · lucrezi în <strong>{{ $currentCompany->name ?? 'firmă' }}</strong>
            @if($supportUser)
                (utilizator <a href="{{ route('admin.users.show', $supportUser) }}" class="underline hover:no-underline">{{ $supportUser->email }}</a>)
            @endif
            — rămâi autentificat ca admin.
        </div>
        @if($supportUser)
            <a href="{{ route('admin.users.show', $supportUser) }}" class="text-xs font-semibold underline shrink-0">Înapoi la utilizator</a>
        @endif
    </div>
@endif
