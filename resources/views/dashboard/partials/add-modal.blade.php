{{-- Modal Adaugă element nou (catalog widget-uri dashboard) --}}
@php
    $catalogJson = collect($widgetCatalog)->map(fn ($w) => [
        'key' => $w['key'],
        'category' => $w['category'],
        'title' => $w['title'],
        'description' => $w['description'],
        'thumb' => $w['thumb'],
        'added' => $w['added'],
    ])->values();
@endphp
<div class="dc-dash-modal-root"
     x-show="open"
     x-cloak
     @keydown.escape.window="open = false"
     style="display: none;">
    <div class="dc-dash-modal-backdrop" @click="open = false"></div>
    <div class="dc-dash-modal" role="dialog" aria-modal="true" aria-labelledby="dc-dash-add-title"
         @click.stop
         x-data="{
            tab: 'all',
            selected: @js($catalogJson[0]['key'] ?? 'activity'),
            catalog: @js($catalogJson),
            counts: @js($widgetCategoryCounts),
            labels: @js($widgetCategories),
            slotsUsed: {{ (int) $dashboardSlotsUsed }},
            slotsMax: {{ (int) $dashboardSlotsMax }},
            filtered() {
                if (this.tab === 'all') return this.catalog;
                return this.catalog.filter(w => w.category === this.tab);
            },
            current() {
                return this.catalog.find(w => w.key === this.selected) || this.filtered()[0] || null;
            },
            select(key) { this.selected = key; },
            setTab(t) {
                this.tab = t;
                const list = this.filtered();
                if (list.length && !list.find(w => w.key === this.selected)) {
                    this.selected = list[0].key;
                }
            }
         }">
        <div class="dc-dash-modal__head">
            <h2 id="dc-dash-add-title">{{ __('Adaugă element nou') }}</h2>
            <button type="button" class="dc-dash-modal__close" @click="open = false" aria-label="{{ __('Închide') }}">×</button>
        </div>

        <div class="dc-dash-modal__tabs" role="tablist">
            <template x-for="(label, key) in labels" :key="key">
                <button type="button"
                        role="tab"
                        class="dc-dash-modal__tab"
                        :class="tab === key ? 'is-active' : ''"
                        :aria-selected="(tab === key).toString()"
                        @click="setTab(key)">
                    <span x-text="label"></span>
                    <span class="dc-dash-modal__tab-count" x-text="'(' + (counts[key] || 0) + ')'"></span>
                </button>
            </template>
        </div>

        <div class="dc-dash-modal__body">
            <div class="dc-dash-modal__list" role="listbox">
                <template x-for="w in filtered()" :key="w.key">
                    <button type="button"
                            class="dc-dash-modal__item"
                            :class="selected === w.key ? 'is-selected' : ''"
                            role="option"
                            :aria-selected="(selected === w.key).toString()"
                            @click="select(w.key)">
                        <div class="dc-dash-modal__thumb" :data-thumb="w.thumb" aria-hidden="true"></div>
                        <div class="dc-dash-modal__item-text">
                            <div class="dc-dash-modal__item-title" x-text="w.title"></div>
                            <div class="dc-dash-modal__item-desc" x-text="w.description"></div>
                            <div class="dc-dash-modal__item-status" x-show="w.added">✓ {{ __('Deja adăugat') }}</div>
                        </div>
                    </button>
                </template>
                <div class="dc-dash-empty" x-show="filtered().length === 0">{{ __('Niciun widget în această categorie.') }}</div>
            </div>

            <div class="dc-dash-modal__preview">
                <div class="dc-dash-modal__preview-label">{{ __('Previzualizare') }}</div>
                <template x-if="current()">
                    <div>
                        <div class="dc-dash-modal__preview-card">
                            <div class="dc-dash-modal__preview-title" x-text="current().title"></div>
                            <p class="dc-dash-modal__preview-desc" x-text="current().description"></p>
                            <div class="dc-dash-modal__preview-fake" :data-thumb="current().thumb" aria-hidden="true"></div>
                        </div>
                        <form method="POST" action="{{ route('dashboard.widgets.store') }}" class="dc-dash-modal__add-form">
                            @csrf
                            <input type="hidden" name="widget" :value="current().key">
                            <button type="submit"
                                    class="dc-dash-modal__add"
                                    :disabled="current().added || slotsUsed >= slotsMax"
                                    x-text="current().added ? '{{ __('Deja adăugat') }}' : '{{ __('Adaugă') }}'">
                            </button>
                        </form>
                        <div class="dc-dash-modal__item-status" x-show="current().added" style="text-align:center;margin-top:.35rem;">✓ {{ __('Deja adăugat') }}</div>
                    </div>
                </template>
            </div>
        </div>

        <div class="dc-dash-modal__foot">
            <form method="POST" action="{{ route('dashboard.widgets.reset') }}" onsubmit="return confirm(@js(__('Resetezi dashboard-ul la layout-ul implicit?')))">
                @csrf
                <button type="submit" class="dc-dash-modal__reset">{{ __('Resetează layout') }}</button>
            </form>
            <div class="dc-dash-modal__slots">
                {{ __('Spații ocupate în Dashboard') }}:
                <strong x-text="slotsUsed + ' / ' + slotsMax"></strong>
            </div>
        </div>
    </div>
</div>
