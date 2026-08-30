{{-- Helper UI pentru #luna# / #an# — folosește clase .dc-tpl-field pe inputuri --}}
<div class="dc-tpl-vars" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button"
            class="dc-tpl-vars-toggle"
            @click.stop="open = !open"
            :aria-expanded="open ? 'true' : 'false'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5" aria-hidden="true"><path d="M10 2a1 1 0 0 1 .8.4l1.5 2a1 1 0 0 0 .7.4l2.4.3a1 1 0 0 1 .55 1.7l-1.8 1.7a1 1 0 0 0-.3.9l.4 2.4a1 1 0 0 1-1.45 1.05L10 12.3l-2.15 1.15a1 1 0 0 1-1.45-1.05l.4-2.4a1 1 0 0 0-.3-.9L4.7 7.4a1 1 0 0 1 .55-1.7l2.4-.3a1 1 0 0 0 .7-.4l1.5-2A1 1 0 0 1 10 2Z"/></svg>
        Variabile
    </button>

    <div class="dc-tpl-vars-panel"
         x-show="open"
         x-cloak
         @click.outside="open = false"
         x-transition>
        <div class="dc-tpl-vars-title">Variabile</div>
        <p class="dc-tpl-vars-text">
            Variabilele se actualizează automat la emiterea documentului, în funcție de data curentă.
            Pot fi folosite în denumire, descriere sau observații.
        </p>
        <div class="dc-tpl-vars-actions">
            <button type="button" class="dc-tpl-chip" @click="window.dcInsertTemplateVar('#luna#')">[Luna]</button>
            <button type="button" class="dc-tpl-chip" @click="window.dcInsertTemplateVar('#an#')">[An]</button>
        </div>
        <div class="dc-tpl-vars-sub">Exemple (dacă azi e august 2026)</div>
        <ul class="dc-tpl-vars-examples">
            <li><code>Abonament #luna# #an#</code> → <em>Abonament august 2026</em></li>
            <li><code>Servicii #luna-2# - #luna# #an#</code> → <em>Servicii iunie - august 2026</em></li>
            <li><code>Avans luna #luna+1#</code> → <em>Avans luna septembrie</em></li>
        </ul>
        <p class="dc-tpl-vars-hint">Poți scrie și <code>#luna+1#</code>, <code>#luna-2#</code>, <code>#an+1#</code>.</p>
    </div>
</div>
