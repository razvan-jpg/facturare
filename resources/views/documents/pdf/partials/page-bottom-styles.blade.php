{{--
  Machetă pe tot A4: min-height + footer absolute.
  Fără padding-bottom / @page size — generau pagină goală.
--}}
@page { margin: 10mm 12mm; }
.pdf-sheet {
    min-height: 264mm;
    position: relative;
}
table.layout {
    width: 100%;
    min-height: 264mm;
    border-collapse: collapse;
}
.pdf-bottom {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    margin: 0;
}
.pdf-bottom-table {
    width: 100%;
    border-collapse: collapse;
}
.pdf-bottom-table td {
    border: none;
    vertical-align: bottom;
    padding: 0;
}
.pdf-bottom-left { width: 58%; text-align: left; }
.pdf-bottom-right { width: 42%; text-align: right; }
.sign-table { width: 100%; margin-top: 6px; border-collapse: collapse; }
.sign-table td { border: none; vertical-align: bottom; width: 100%; padding: 0; }
.sign-stack { width: 100%; }
.sign-block { margin: 0 0 10px; text-align: left; }
.sign-block:last-child { margin-bottom: 0; }
.sign-label { font-size: 9px; text-transform: uppercase; color: #627d98; margin: 0 0 4px; text-align: left; }
.sign-label-under-stamp { margin: 0; }
.sign-stamp-over { text-align: center; }
.sign-media { text-align: center; }
.sign-text-lines {
    text-align: left;
    font-style: italic;
    font-size: 10px;
    line-height: 1.45;
    color: #243b53;
    max-width: 290px;
}
.sign-text-lines .sign-line-row { display: block; margin: 0; padding: 0; }
.sign-line {
    width: 100%;
    border-bottom: 1px solid #bcccdc;
    height: 8px;
    margin: 4px auto 0;
}
.totals { margin: 0; text-align: right; }
.totals .grand { margin-top: 4px; }
.notes { margin: 0 0 8px; color: #627d98; }
.footer-meta {
    margin: 0 0 8px;
    font-size: 10px;
    color: #486581;
    line-height: 1.45;
}
.footer-meta strong { color: #334e68; }
.footer { margin: 8px 0 0; font-size: 9px; color: #9fb3c8; }
.footer a.footer-brand-link,
.footer-brand-link { color: inherit; text-decoration: underline; }
