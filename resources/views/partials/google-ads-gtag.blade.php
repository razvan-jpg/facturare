{{-- Google Ads gtag + Consent Mode v2 (SEE/EEA).
     Defaults denied until user accepts marketing cookies (partials.cookie-consent).
     Conversion events later: gtag('event','conversion',{send_to:'AW-18392647584/LABEL'}) --}}
@once('google-ads-gtag')
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  // Consent Mode v2 — deny until explicit accept (România = SEE)
  gtag('consent', 'default', {
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    analytics_storage: 'denied',
    wait_for_update: 500
  });
  gtag('set', 'ads_data_redaction', true);
  gtag('set', 'url_passthrough', true);
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-18392647584"></script>
<script>
  gtag('js', new Date());
  gtag('config', 'AW-18392647584');
  // Re-apply saved choice as soon as gtag is ready
  (function () {
    try {
      var c = localStorage.getItem('dc_consent_v1');
      if (c === 'all' && typeof gtag === 'function') {
        gtag('consent', 'update', {
          ad_storage: 'granted',
          ad_user_data: 'granted',
          ad_personalization: 'granted',
          analytics_storage: 'granted'
        });
      }
    } catch (e) {}
  })();
</script>
@endonce
