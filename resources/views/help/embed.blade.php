{{-- Fragment HTML pentru API mobil (fără meniu / cuprins web). --}}
<article class="help-article">
    @yield('help')
</article>
<style>
.help-article { padding: 0; }
.help-article h2 { font-family: Georgia, 'Times New Roman', serif; font-size: 1.45rem; color: #102a43; margin: 0 0 .35rem; }
.help-article .help-lead { color: #486581; margin-bottom: 1.25rem; font-size: .95rem; }
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
.help-article .help-warn {
    border-left: 3px solid #f59e0b; background: #fffbeb; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #92400e;
}
.help-steps { counter-reset: step; list-style: none; margin-left: 0; padding: 0; }
.help-steps > li {
    counter-increment: step; position: relative; padding: .65rem .75rem .65rem 2.6rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .55rem; margin-bottom: .55rem;
}
.help-steps > li::before {
    content: counter(step); position: absolute; left: .65rem; top: .65rem;
    width: 1.45rem; height: 1.45rem; border-radius: 999px; background: #0f766e; color: #fff;
    font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.help-figure {
    margin: 1rem 0 1.35rem; border: 1px solid #d9e2ec; border-radius: .75rem; overflow: hidden;
    background: #f8fafc;
}
.help-figure img { display: block; width: 100%; height: auto; }
.help-figure-placeholder {
    min-height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .35rem; color: #829ab1; font-size: 13px; padding: 1.5rem;
}
.help-figure figcaption {
    padding: .65rem .9rem; font-size: 12px; color: #486581; border-top: 1px solid #e2e8f0; background: #fff;
}
.help-pager { display: none; }
.help-kbd {
    display: inline-block; padding: .05rem .35rem; border: 1px solid #cbd5e1; border-bottom-width: 2px;
    border-radius: .3rem; font-size: 11px; background: #fff; color: #334e68; font-family: ui-monospace, monospace;
}
.help-grid-cards {
    display: grid; grid-template-columns: 1fr; gap: .65rem; margin: 1rem 0 1.25rem;
}
.help-grid-cards a {
    display: block; padding: .85rem; border: 1px solid #e2e8f0; border-radius: .65rem;
    text-decoration: none; color: inherit; background: #fff;
}
.help-grid-cards strong { display: block; color: #0f766e; margin-bottom: .25rem; font-size: .9rem; }
.help-grid-cards span { font-size: .8rem; color: #627d98; line-height: 1.4; }
</style>
