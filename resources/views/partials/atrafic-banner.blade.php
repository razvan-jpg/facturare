{{-- Atrafic — pe toate paginile; se încarcă doar după consimțământ marketing (cookie-consent). O dată pe pagină. --}}
@once('atrafic-banner')
<div class="dc-ad-slot {{ $class ?? 'dc-ad-slot--default' }}" role="complementary" aria-label="{{ __('Reclamă') }}" data-dc-atrafic hidden>
    <p class="dc-ad-slot-label">{{ __('Reclamă') }}</p>
    <div class="dc-ad-slot-inner"></div>
</div>
<style>
.dc-ad-slot{margin:1.25rem auto;max-width:46rem;padding:0 1rem;text-align:center}
.dc-ad-slot[hidden]{display:none!important}
.dc-ad-slot-label{margin:0 0 .4rem;font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8}
.dc-ad-slot-inner{display:inline-block;max-width:100%;min-height:1.5rem;overflow:hidden;line-height:0}
.dc-ad-slot-inner>*{max-width:100%;height:auto}
.dc-ad-slot--app{margin:.75rem 0;padding:0;max-width:none;text-align:left}
.dc-ad-slot--guest{margin:.75rem 0 0;padding:0}
.dc-ad-slot--login{margin-top:1.25rem}
</style>
@endonce
