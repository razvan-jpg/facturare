/**
 * Keeps landing promo stickers on the left, stacked, with a smooth spring follow.
 */
export function initPromiseSticky() {
    initStickyCard('.mkt-promise-hero--tilt', {
        rotate: -16,
        // Slightly above mid-viewport so the referral card fits below.
        offsetRatio: -0.14,
    });
    initStickyCard('.mkt-referral-hero--tilt', {
        rotate: -12,
        // Below the promise sticker.
        offsetRatio: 0.2,
    });
}

function initStickyCard(selector, { rotate = -16, offsetRatio = 0 } = {}) {
    const el = document.querySelector(selector);
    if (! el) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 1024px)');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const transform = `translate3d(0, -50%, 0) rotate(${rotate}deg)`;

    let current = targetY();
    let lastScroll = window.scrollY;
    let rafId = 0;
    let active = false;

    function targetY() {
        return window.innerHeight / 2 + window.innerHeight * offsetRatio;
    }

    const setStaticCenter = () => {
        current = targetY();
        el.style.top = `${current}px`;
        el.style.transform = transform;
    };

    const tick = () => {
        if (! active) {
            return;
        }

        const center = targetY();
        const scrollDelta = window.scrollY - lastScroll;
        lastScroll = window.scrollY;

        current += scrollDelta * 0.45;
        current += (center - current) * (reduceMotion.matches ? 1 : 0.1);

        const min = window.innerHeight * 0.16;
        const max = window.innerHeight * 0.88;
        if (current < min) current = min + (current - min) * 0.2;
        if (current > max) current = max + (current - max) * 0.2;

        el.style.top = `${current}px`;
        el.style.transform = transform;

        rafId = window.requestAnimationFrame(tick);
    };

    const enable = () => {
        if (active || ! desktop.matches) {
            return;
        }
        active = true;
        el.classList.add('is-sticky-follow');
        lastScroll = window.scrollY;
        current = targetY();
        setStaticCenter();
        rafId = window.requestAnimationFrame(tick);
    };

    const disable = () => {
        active = false;
        if (rafId) {
            window.cancelAnimationFrame(rafId);
            rafId = 0;
        }
        el.classList.remove('is-sticky-follow');
        el.style.top = '';
        el.style.transform = '';
    };

    const sync = () => {
        if (desktop.matches) {
            enable();
        } else {
            disable();
        }
    };

    sync();
    desktop.addEventListener('change', sync);
    window.addEventListener('resize', () => {
        if (active) {
            current = targetY();
        }
    }, { passive: true });
}
