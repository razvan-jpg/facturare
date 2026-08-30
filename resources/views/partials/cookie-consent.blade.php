{{-- Banner consimțământ cookie-uri / Consent Mode (SEE). O dată pe pagină. --}}
@once('cookie-consent')
<div id="dc-cookie-banner" class="dc-cookie-banner" hidden role="dialog" aria-labelledby="dc-cookie-title" aria-describedby="dc-cookie-desc">
    <div class="dc-cookie-banner-inner">
        <div class="dc-cookie-banner-copy">
            <p id="dc-cookie-title" class="dc-cookie-banner-title">{{ __('Cookie-uri și măsurare') }}</p>
            <p id="dc-cookie-desc" class="dc-cookie-banner-text">
                {{ __('Folosim cookie-uri esențiale pentru autentificare și preferințe. Cu acordul tău, activăm și măsurarea/publicitatea (Google Ads) și contoare de trafic, conform') }}
                <a href="{{ route('legal.show', 'confidentialitate') }}" class="dc-cookie-banner-link">{{ __('Politicii de confidențialitate') }}</a>.
            </p>
        </div>
        <div class="dc-cookie-banner-actions">
            <button type="button" class="dc-cookie-btn dc-cookie-btn--ghost" id="dc-cookie-essential" data-consent="essential">
                {{ __('Doar esențiale') }}
            </button>
            <button type="button" class="dc-cookie-btn dc-cookie-btn--primary" id="dc-cookie-accept" data-consent="all">
                {{ __('Accept toate') }}
            </button>
        </div>
    </div>
</div>
<style>
.dc-cookie-banner{position:fixed;z-index:99999;left:0;right:0;bottom:0;padding:1rem;background:rgba(15,23,42,.55);backdrop-filter:blur(6px)}
.dc-cookie-banner[hidden]{display:none!important}
.dc-cookie-banner-inner{max-width:56rem;margin:0 auto;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;justify-content:space-between;padding:1rem 1.15rem;border-radius:1rem;background:#fff;box-shadow:0 12px 40px rgba(15,23,42,.18);border:1px solid #e2e8f0}
.dc-cookie-banner-copy{flex:1 1 16rem;min-width:0}
.dc-cookie-banner-title{margin:0 0 .35rem;font-size:.95rem;font-weight:700;color:#0f172a}
.dc-cookie-banner-text{margin:0;font-size:.8rem;line-height:1.45;color:#475569}
.dc-cookie-banner-link{color:#0f766e;text-decoration:underline}
.dc-cookie-banner-actions{display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end}
.dc-cookie-btn{appearance:none;border-radius:.65rem;padding:.55rem 1rem;font-size:.8rem;font-weight:600;cursor:pointer;border:1px solid transparent}
.dc-cookie-btn--ghost{background:#f8fafc;border-color:#cbd5e1;color:#334155}
.dc-cookie-btn--ghost:hover{background:#f1f5f9}
.dc-cookie-btn--primary{background:#0f766e;color:#fff}
.dc-cookie-btn--primary:hover{background:#0d9488}
@media (max-width:640px){
  .dc-cookie-banner-inner{padding:.9rem}
  .dc-cookie-banner-actions{width:100%}
  .dc-cookie-btn{flex:1 1 auto;text-align:center}
}
</style>
<script>
(function () {
  var KEY = 'dc_consent_v1';
  var COOKIE = 'dc_consent';

  function readConsent() {
    try {
      var v = localStorage.getItem(KEY);
      if (v === 'all' || v === 'essential') return v;
    } catch (e) {}
    var m = document.cookie.match(/(?:^|;\s*)dc_consent=(all|essential)/);
    return m ? m[1] : null;
  }

  function writeConsent(v) {
    try { localStorage.setItem(KEY, v); } catch (e) {}
    document.cookie = COOKIE + '=' + v + ';path=/;max-age=15552000;SameSite=Lax';
  }

  function updateGtag(granted) {
    if (typeof gtag !== 'function') return;
    var state = granted ? 'granted' : 'denied';
    gtag('consent', 'update', {
      ad_storage: state,
      ad_user_data: state,
      ad_personalization: state,
      analytics_storage: state
    });
  }

  function loadScript(src, attrs) {
    if (document.querySelector('script[src="' + src + '"]')) return;
    var s = document.createElement('script');
    s.src = src;
    if (attrs) Object.keys(attrs).forEach(function (k) { s.setAttribute(k, attrs[k]); });
    document.head.appendChild(s);
  }

  function enableMarketingTags() {
    updateGtag(true);
    // Trafic.ro logger
    loadScript('https://ts.trafic.ro/js/traficlogger.js', { defer: 'defer', type: 'text/javascript' });
    // Atrafic ad slot(s)
    document.querySelectorAll('[data-dc-atrafic]').forEach(function (slot) {
      if (slot.getAttribute('data-loaded') === '1') return;
      slot.setAttribute('data-loaded', '1');
      var inner = slot.querySelector('.dc-ad-slot-inner') || slot;
      // ads.php folosește adesea document.write — redirecționăm în slot
      var prevWrite = document.write;
      document.write = function (html) {
        try { inner.insertAdjacentHTML('beforeend', String(html)); } catch (e) {}
      };
      var s = document.createElement('script');
      s.src = 'https://atrafic.ro/ads.php?style=non_ssi&us=141938';
      s.onload = s.onerror = function () { document.write = prevWrite; };
      setTimeout(function () { document.write = prevWrite; }, 4000);
      inner.appendChild(s);
      slot.hidden = false;
    });
    document.querySelectorAll('[data-dc-trafic-badge]').forEach(function (el) {
      el.hidden = false;
    });
    document.dispatchEvent(new CustomEvent('dc-consent-marketing'));
  }

  function disableMarketingUi() {
    updateGtag(false);
    document.querySelectorAll('[data-dc-atrafic]').forEach(function (slot) {
      slot.hidden = true;
    });
    document.querySelectorAll('[data-dc-trafic-badge]').forEach(function (el) {
      el.hidden = true;
    });
  }

  function apply(choice) {
    writeConsent(choice);
    var banner = document.getElementById('dc-cookie-banner');
    if (banner) banner.hidden = true;
    if (choice === 'all') enableMarketingTags();
    else disableMarketingUi();
  }

  function clearConsent() {
    try { localStorage.removeItem(KEY); } catch (e) {}
    document.cookie = COOKIE + '=;path=/;max-age=0;SameSite=Lax';
  }

  function init() {
    // Reset: /?dc_reset_consent=1 (sau pe orice pagină)
    try {
      var params = new URLSearchParams(window.location.search);
      if (params.get('dc_reset_consent') === '1') {
        clearConsent();
        params.delete('dc_reset_consent');
        var q = params.toString();
        var clean = window.location.pathname + (q ? '?' + q : '') + window.location.hash;
        window.history.replaceState({}, '', clean);
      }
    } catch (e) {}

    var existing = readConsent();
    var banner = document.getElementById('dc-cookie-banner');
    if (existing === 'all') {
      enableMarketingTags();
      if (banner) banner.hidden = true;
      return;
    }
    if (existing === 'essential') {
      disableMarketingUi();
      if (banner) banner.hidden = true;
      return;
    }
    // No choice yet — show banner; keep marketing off
    disableMarketingUi();
    if (banner) banner.hidden = false;
  }

  document.getElementById('dc-cookie-accept')?.addEventListener('click', function () { apply('all'); });
  document.getElementById('dc-cookie-essential')?.addEventListener('click', function () { apply('essential'); });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
@endonce
