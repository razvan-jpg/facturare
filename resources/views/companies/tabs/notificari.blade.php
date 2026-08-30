<div class="space-y-6">
<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="notificari">
    <h2 class="text-lg font-semibold">Notificări restanțe</h2>
    <p class="text-sm text-slate-600">Trimite automat email clienților cu facturi scadente neachitate.</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <label class="flex items-center gap-2 sm:col-span-2">
            <input type="checkbox" name="overdue_reminders_enabled" value="1" @checked(old('overdue_reminders_enabled', $company->overdue_reminders_enabled)) class="rounded border-slate-300">
            <span class="text-sm font-medium">Activează notificările de restanțe</span>
        </label>
        <div>
            <label class="dc-label">{{ __('Frecvență') }}</label>
            <select name="overdue_reminder_frequency_days" class="dc-input">
                @foreach(\App\Models\Company::OVERDUE_REMINDER_FREQUENCIES as $days => $label)
                    <option value="{{ $days }}" @selected((int) old('overdue_reminder_frequency_days', $company->overdue_reminder_frequency_days ?: 7) === (int) $days)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dc-label">Conținut notificare</label>
            <select name="overdue_reminder_scope" class="dc-input">
                @foreach(\App\Models\Company::OVERDUE_REMINDER_SCOPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('overdue_reminder_scope', $company->overdue_reminder_scope ?: 'both') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dc-label">Zile grație după scadență</label>
            <input type="number" min="0" max="90" name="overdue_reminder_grace_days" value="{{ old('overdue_reminder_grace_days', $company->overdue_reminder_grace_days ?? 0) }}" class="dc-input">
        </div>
        <label class="flex items-center gap-2 pt-6">
            <input type="checkbox" name="overdue_reminder_include_statement" value="1" @checked(old('overdue_reminder_include_statement', $company->overdue_reminder_include_statement ?? true)) class="rounded border-slate-300">
            <span class="text-sm">Atașează fișa de client (PDF)</span>
        </label>
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>

@if($company->overdue_reminders_enabled)
<div class="dc-card p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
            <h3 class="font-semibold">Rulează notificările acum</h3>
            <p class="text-sm text-slate-600">Trimite imediat clienților eligibili.</p>
        </div>
        <form method="POST" action="{{ route('companies.reminders.overdue', $company) }}">
            @csrf
            <button class="dc-btn-secondary">Trimite acum</button>
        </form>
    </div>
    @if(($recentReminders ?? collect())->isNotEmpty())
        <ul class="space-y-2 text-sm text-slate-600 border-t border-slate-100 pt-3">
            @foreach($recentReminders as $log)
                <li>
                    {{ dc_datetime($log->sent_at) }} · {{ $log->client?->name ?: 'Client' }}
                    · {{ $log->invoice_count }} fact.
                    · {{ number_format($log->balance_total, 2, ',', '.') }} RON
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endif
</div>
