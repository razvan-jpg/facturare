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

<div class="dc-card p-6 space-y-4">
    <div>
        <h3 class="font-semibold">Trimite unui client</h3>
        <p class="text-sm text-slate-600">Email cu soldul restant, către clientul ales. Poți atașa și fișa de cont (4111). Primești o copie în CC pe emailul contului tău. Nu ține cont de frecvență sau de zilele de grație.</p>
    </div>
    @php
        $mailClients = ($reminderClients ?? collect())->filter(fn ($c) => $c->emailAddresses() !== [])->values();
    @endphp
    @if($mailClients->isEmpty())
        <p class="text-sm text-slate-500">Niciun client cu adresă de email. Adaugă emailul pe fișa clientului ca să poți trimite notificarea.</p>
    @else
        <form method="POST" action="{{ route('companies.reminders.overdue.client', $company) }}" class="space-y-4"
              onsubmit="return confirm('Trimiți notificarea de sold restant către clientul selectat?');">
            @csrf
            <div>
                <label class="dc-label" for="reminder-client-filter">Client</label>
                <input type="search" id="reminder-client-filter" class="dc-input mb-2" placeholder="Caută după nume, CUI sau email" autocomplete="off">
                <select name="client_id" id="reminder-client" class="dc-input" required>
                    <option value="">Alege clientul</option>
                    @foreach($mailClients as $c)
                        <option value="{{ $c->id }}" @selected((string) old('client_id') === (string) $c->id)>{{ $c->name }}@if($c->cui) · {{ $c->cui }}@endif · {{ $c->email }}</option>
                    @endforeach
                </select>
                @error('client_id')
                    <p class="text-sm text-red-700 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="attach_account_statement" value="1" @checked(old('attach_account_statement')) class="rounded border-slate-300">
                <span class="text-sm">Atașează fișa de cont (PDF)</span>
            </label>
            <button class="dc-btn-primary">Trimite notificare</button>
        </form>
    @endif
</div>

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
</div>
@endif

@if(($recentReminders ?? collect())->isNotEmpty())
<div class="dc-card p-6">
    <h3 class="font-semibold mb-3">Trimiteri recente</h3>
    <ul class="space-y-2 text-sm text-slate-600">
        @foreach($recentReminders as $log)
            <li>
                {{ dc_datetime($log->sent_at) }} · {{ $log->client?->name ?: 'Client' }}
                · {{ $log->invoice_count }} fact.
                · {{ number_format($log->balance_total, 2, ',', '.') }} RON
                @if($log->included_statement)
                    · cu fișă
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif
</div>

<script>
(() => {
    const filter = document.getElementById('reminder-client-filter');
    const select = document.getElementById('reminder-client');
    if (!filter || !select) return;
    const all = [...select.options].map((option) => ({
        value: option.value,
        text: option.textContent,
    }));
    filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        const current = select.value;
        select.replaceChildren();
        for (const item of all) {
            if (item.value && q !== '' && !item.text.toLowerCase().includes(q)) continue;
            select.append(new Option(item.text, item.value, false, item.value === current));
        }
        if (select.value !== current) select.value = '';
    });
})();
</script>
