{{-- Fragment HTML pentru API mobil (fără meniu legal web). --}}
<article class="help-article">
    @yield('legal')
</article>
<style>
.help-article { padding: 0; }
.help-article h2 { font-family: Georgia, 'Times New Roman', serif; font-size: 1.45rem; color: #102a43; margin: 0 0 .35rem; }
.help-article .help-lead { color: #486581; margin-bottom: 1rem; font-size: .95rem; }
.help-article .help-meta-line { color: #829ab1; font-size: .8rem; margin-bottom: 1.25rem; }
.help-article h3 { font-size: 1.05rem; font-weight: 700; color: #243b53; margin: 1.4rem 0 .5rem; }
.help-article h4 { font-size: .95rem; font-weight: 700; color: #334e68; margin: 1rem 0 .35rem; }
.help-article p, .help-article li { font-size: .92rem; line-height: 1.65; color: #334e68; }
.help-article p { margin: 0 0 .75rem; }
.help-article ul, .help-article ol { margin: 0 0 1rem 1.15rem; }
.help-article li { margin-bottom: .35rem; }
.help-article .help-note {
    border-left: 3px solid #14b8a6; background: #f0fdfa; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #115e59;
}
.help-pager { display: none; }
</style>
