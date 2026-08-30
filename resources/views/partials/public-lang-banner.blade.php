@php
    $hintLocales = ['ro', 'en_US', 'de', 'fr', 'it'];
@endphp
<aside class="mkt-lang-promo" aria-label="{{ __('Noi vorbim pe limba ta!') }}">
    <div class="mkt-lang-promo-body">
        <p class="mkt-lang-promo-title">{{ __('Noi vorbim pe limba ta!') }}</p>
        <p class="mkt-lang-promo-text">{{ __('Schimbă steagul din dreapta sus — site-ul se traduce pentru oaspeți, în toate limbile din selector.') }}</p>
        <div class="mkt-lang-promo-row">
            <ul class="mkt-lang-promo-flags" aria-hidden="true">
                @foreach($hintLocales as $code)
                    @php $flag = \App\Support\UiLocales::flag($code); @endphp
                    @if($flag !== '')
                        <li><span class="mkt-lang-promo-flag">{{ $flag }}</span></li>
                    @endif
                @endforeach
            </ul>
            <button type="button"
                    class="mkt-lang-promo-cta"
                    onclick="(function(){var el=document.getElementById('public-ui-locale-light');if(!el)return;el.focus({preventScroll:false});el.scrollIntoView({block:'nearest',behavior:'smooth'});if(typeof el.showPicker==='function'){try{el.showPicker();}catch(e){}}})();">
                {{ __('Alege limba ta') }}
            </button>
        </div>
    </div>
</aside>
