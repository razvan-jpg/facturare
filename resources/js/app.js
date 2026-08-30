import './bootstrap';

import Alpine from 'alpinejs';
import { initPromiseSticky } from './promise-sticky';
import { initDateInputs } from './date-inputs';
import { initIbanBankBindings } from './iban-bank';
import { initFieldCase } from './field-case';

window.Alpine = Alpine;

Alpine.start();

function bootUi() {
    initPromiseSticky();
    initDateInputs();
    initIbanBankBindings();
    initFieldCase();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootUi, { once: true });
} else {
    bootUi();
}

document.addEventListener('contextmenu', (event) => {
    if (document.body?.dataset?.allowContextMenu === '1') {
        return;
    }

    event.preventDefault();
    window.alert('Nimic interesant aici!!! GO BACK!');
});
