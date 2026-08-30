{{-- Trafic.ro — logger + badge după consimțământ marketing (cookie-consent). O dată pe pagină. --}}
@once('trafic-ro')
<div class="dc-trafic-ro {{ $class ?? '' }}" data-dc-trafic-badge hidden>
    <a href="https://trafic.ro/statistici/factura.dateconta.ro" target="_blank" rel="noopener noreferrer">
        <img src="https://trafic.ro/images/trafic-ro-logo.png" title="Trafic.ro - Clasamente si Statistici" alt="Trafic.ro" style="height:25px" width="auto" height="25" loading="lazy" decoding="async">
    </a>
</div>
<style>
.dc-trafic-ro{margin:.75rem auto;padding:0 1rem;text-align:center;line-height:0}
.dc-trafic-ro[hidden]{display:none!important}
.dc-trafic-ro a{display:inline-block;opacity:.85;transition:opacity .15s ease}
.dc-trafic-ro a:hover{opacity:1}
.dc-trafic-ro img{height:25px;width:auto;vertical-align:middle}
.dc-trafic-ro--app{margin:.35rem 0;padding:0;text-align:left;opacity:.9}
.dc-trafic-ro--app a{opacity:.7}
.dc-trafic-ro--guest{margin:.5rem 0 0;padding:0;text-align:center}
</style>
@endonce
