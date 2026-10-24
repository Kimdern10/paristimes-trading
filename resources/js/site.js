/*
 * Scripts for the public website: the mobile menu, scroll animations,
 * count-up numbers, the rate calculator, the FAQ and the contact map.
 *
 * Pages change with Livewire's wire:navigate, which swaps the <body>
 * instead of reloading. So everything that touches page elements runs
 * in initPage() after every page change, and listeners on window or
 * document are added only once.
 */

document.documentElement.classList.add('js');

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Open and close the menu on phones.
 */
function setUpMenu() {
    const header = document.querySelector('[data-header]');
    const toggle = document.querySelector('[data-menu-toggle]');

    toggle?.addEventListener('click', () => {
        const isOpen = header.classList.toggle('menu-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });
}

/**
 * Close the phone menu with the Escape key (added once for all pages).
 */
document.addEventListener('keydown', (event) => {
    const header = document.querySelector('[data-header]');
    const toggle = document.querySelector('[data-menu-toggle]');

    if (event.key === 'Escape' && header?.classList.contains('menu-open')) {
        toggle.click();
        toggle.focus();
    }
});

/**
 * Fill the thin gold bar at the top as the page scrolls, and give the
 * header a shadow once it leaves the top.
 */
function updateScrollEffects() {
    const bar = document.querySelector('[data-scroll-progress]');
    const header = document.querySelector('[data-header]');
    const max = document.documentElement.scrollHeight - window.innerHeight;

    bar?.style.setProperty('--progress', String(max > 0 ? window.scrollY / max : 0));
    header?.classList.toggle('is-scrolled', window.scrollY > 10);
}

let scrollTicking = false;

window.addEventListener(
    'scroll',
    () => {
        if (!scrollTicking) {
            scrollTicking = true;
            window.requestAnimationFrame(() => {
                updateScrollEffects();
                scrollTicking = false;
            });
        }
    },
    { passive: true },
);

/**
 * Let the cards in each grid fade in one after another.
 */
function setUpStagger() {
    document.querySelectorAll('.cards, .steps, .trust-grid, .check-list, .contact-grid, .gallery, .deals, .hscroll-track, .reviews, .trend-grid').forEach((grid) => {
        grid.classList.remove('reveal');
        grid.classList.add('reveal-stagger');

        [...grid.children].forEach((child, index) => {
            child.classList.add('reveal');
            child.style.setProperty('--i', String(Math.min(index, 8)));
        });
    });
}

/**
 * Fade sections in as they scroll into view.
 */
function setUpReveal() {
    const items = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    items.forEach((item) => observer.observe(item));
}

/**
 * Wrap each word of the home headline so the words can slide in one by one.
 */
function splitHeadline() {
    const heading = document.querySelector('.hero h1');

    if (!heading || reduceMotion) {
        return;
    }

    const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let index = 0;

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    textNodes.forEach((node) => {
        const fragment = document.createDocumentFragment();

        node.textContent.split(/(\s+)/).forEach((part) => {
            if (part.trim() === '') {
                fragment.append(part);

                return;
            }

            const word = document.createElement('span');
            word.className = 'word';
            word.style.setProperty('--i', String(index++));
            word.textContent = part;
            fragment.append(word);
        });

        node.replaceWith(fragment);
    });
}

/**
 * Count numbers up from zero when they scroll into view, e.g. rates and totals.
 */
function setUpCountUp() {
    if (reduceMotion || !('IntersectionObserver' in window)) {
        return;
    }

    const format = (value, decimals, prefix) =>
        prefix + value.toLocaleString('en-NG', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

    const run = (element) => {
        const target = parseFloat(element.dataset.count);
        const decimals = Number.isInteger(target) ? 0 : 2;
        const prefix = element.dataset.prefix || '';
        const duration = 1200;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);

            element.textContent = format(target * eased, progress < 1 ? 0 : decimals, prefix);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    run(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.4 },
    );

    document.querySelectorAll('[data-count]').forEach((item) => {
        if (!Number.isNaN(parseFloat(item.dataset.count))) {
            observer.observe(item);
        }
    });
}

/**
 * Tilt the rates card towards the mouse on computers.
 */
function setUpTilt() {
    if (reduceMotion || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const box = card.getBoundingClientRect();
            const x = (event.clientX - box.left) / box.width - 0.5;
            const y = (event.clientY - box.top) / box.height - 0.5;

            card.style.setProperty('--ry', `${x * 8}deg`);
            card.style.setProperty('--rx', `${y * -8}deg`);
        });

        card.addEventListener('pointerleave', () => {
            card.style.setProperty('--ry', '0deg');
            card.style.setProperty('--rx', '0deg');
        });
    });
}

/**
 * Airport-style flip tiles for rates. Each character gets its own tile;
 * digits shuffle through random numbers before landing on the real one.
 */
const DIGITS = '0123456789';

/** The value each flip element currently shows (kept in memory, not in the DOM, so Livewire leaves it alone). */
const shownValues = new WeakMap();

function makeTile(char) {
    const tile = document.createElement('span');
    const face = document.createElement('span');

    tile.className = DIGITS.includes(char) ? 'flip-char' : 'flip-char is-sep';
    face.className = 'flip-face';
    face.textContent = char;
    tile.append(face);

    return tile;
}

function setTile(tile, char) {
    const face = tile.firstElementChild;

    face.textContent = char;
    face.classList.remove('is-flipping');
    void face.offsetWidth;
    face.classList.add('is-flipping');
}

/**
 * Show a value on a flip element, flipping only the characters that changed
 * (or every digit when shuffle is true, for the first load).
 */
function flipTo(element, value, shuffle = false) {
    const cells = element.querySelector('.flip-cells');

    if (!cells) {
        return;
    }

    shownValues.set(element, value);

    if (reduceMotion) {
        cells.textContent = value;

        return;
    }

    let tiles = [...cells.querySelectorAll('.flip-char')];

    if (tiles.length !== value.length) {
        cells.replaceChildren(...[...value].map((char) => makeTile(DIGITS.includes(char) ? '0' : char)));
        tiles = [...cells.querySelectorAll('.flip-char')];
        shuffle = true;
    }

    [...value].forEach((char, index) => {
        const tile = tiles[index];
        const current = tile.firstElementChild.textContent;

        tile.className = DIGITS.includes(char) ? 'flip-char' : 'flip-char is-sep';

        if (!DIGITS.includes(char)) {
            if (current !== char) {
                setTile(tile, char);
            }

            return;
        }

        if (!shuffle && current === char) {
            return;
        }

        const spins = (shuffle ? 4 : 2) + index;
        let count = 0;

        clearInterval(tile.flipTimer);
        tile.flipTimer = setInterval(() => {
            count++;

            if (count >= spins) {
                clearInterval(tile.flipTimer);
                setTile(tile, char);

                return;
            }

            setTile(tile, DIGITS[Math.floor(Math.random() * 10)]);
        }, 70);
    });
}

/**
 * Turn every flip element on the page into tiles, and flip any whose value
 * Livewire has changed since it was last shown.
 */
function syncFlips() {
    document.querySelectorAll('.flip').forEach((element) => {
        const value = element.dataset.flip || '';

        if (!shownValues.has(element)) {
            flipTo(element, value, true);
        } else if (shownValues.get(element) !== value) {
            flipTo(element, value);
        }
    });
}

/**
 * Read the latest rates that the live rates component prints on the page.
 */
function liveRates() {
    try {
        return JSON.parse(document.getElementById('live-rates-data')?.textContent || '[]');
    } catch {
        return [];
    }
}

let heroTimer = null;
let heroPausedUntil = 0;

/**
 * The big rate in the hero: shows one currency at a time, moves to the next
 * every few seconds, and lets visitors pick one with the tabs.
 */
function showHeroRate(code) {
    const hero = document.querySelector('[data-hero-rate]');
    const rate = liveRates().find((item) => item.code === code && item.sell !== null);

    if (!hero || !rate) {
        return;
    }

    hero.dataset.current = code;
    hero.querySelector('[data-hero-symbol]').textContent = rate.symbol;
    hero.querySelector('[data-hero-code]').textContent = rate.code;
    hero.querySelector('[data-hero-buy]').textContent = rate.buyText;
    hero.querySelectorAll('[role="tab"]').forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.code === code)));

    const flip = hero.querySelector('[data-hero-flip]');
    flip.dataset.flip = rate.sellText;
    flipTo(flip, rate.sellText);
}

function setUpHeroRate() {
    const hero = document.querySelector('[data-hero-rate]');

    clearInterval(heroTimer);

    if (!hero) {
        return;
    }

    const tabs = [...hero.querySelectorAll('[role="tab"]')];
    hero.dataset.current = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true')?.dataset.code || '';

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            heroPausedUntil = Date.now() + 20000;
            showHeroRate(tab.dataset.code);
        });
    });

    if (reduceMotion || tabs.length < 2) {
        return;
    }

    heroTimer = setInterval(() => {
        if (Date.now() < heroPausedUntil || document.hidden) {
            return;
        }

        const codes = tabs.map((tab) => tab.dataset.code);
        const next = codes[(codes.indexOf(hero.dataset.current) + 1) % codes.length];
        showHeroRate(next);
    }, 5000);
}

let updateCalculator = null;

/**
 * After Livewire brings new rates: flip the changed numbers, refresh the big
 * hero rate and recalculate the calculator.
 */
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', () => {
        syncFlips();

        const hero = document.querySelector('[data-hero-rate]');

        if (hero?.dataset.current) {
            showHeroRate(hero.dataset.current);
        }

        updateCalculator?.();
    });
});

/**
 * Format a number of naira, e.g. 790000 -> "₦790,000".
 */
function naira(amount) {
    const decimals = Number.isInteger(amount) ? 0 : 2;

    return '₦' + amount.toLocaleString('en-NG', { minimumFractionDigits: decimals, maximumFractionDigits: 2 });
}

/**
 * Work out how much naira a customer gets or pays, and prepare a WhatsApp
 * message with the details so they can confirm the rate in one tap.
 */
function setUpCalculator() {
    const form = document.querySelector('[data-calc]');

    updateCalculator = null;

    if (!form) {
        return;
    }

    const business = form.dataset.business || '';
    const whatsappUrl = form.dataset.whatsapp || '';

    const currencySelect = form.querySelector('[name="currency"]');
    const amountInput = form.querySelector('[name="amount"]');
    const label = form.querySelector('[data-calc-label]');
    const output = form.querySelector('[data-calc-amount]');
    const rateLine = form.querySelector('[data-calc-rate]');
    const button = form.querySelector('[data-calc-whatsapp]');

    const update = () => {
        const mode = form.querySelector('[name="mode"]:checked')?.value || 'sell';
        const currency = liveRates().find((rate) => rate.code === currencySelect.value);
        const amount = parseFloat(String(amountInput.value).replace(/,/g, ''));
        const rate = currency ? parseFloat(mode === 'sell' ? currency.buy : currency.sell) : NaN;
        const verb = mode === 'sell' ? 'sell' : 'buy';

        label.textContent = mode === 'sell' ? 'You get about' : 'You pay about';

        if (!currency || Number.isNaN(rate)) {
            output.textContent = 'Ask us';
            rateLine.textContent = `We will tell you today's ${verb} rate on WhatsApp.`;
            button.href = `${whatsappUrl}?text=${encodeURIComponent(`Hello ${business}, I want to ${verb} ${currencySelect.value}. What is your rate today?`)}`;

            return;
        }

        rateLine.textContent = `At ${naira(rate)} per 1 ${currency.code}`;

        if (Number.isNaN(amount) || amount <= 0) {
            output.textContent = naira(0);
            button.href = `${whatsappUrl}?text=${encodeURIComponent(`Hello ${business}, I want to ${verb} ${currency.code}. Is ${naira(rate)} per ${currency.code} still available?`)}`;

            return;
        }

        const total = Math.round(amount * rate * 100) / 100;
        const foreign = `${currency.symbol}${amount.toLocaleString('en-NG')} (${currency.code})`;

        output.textContent = naira(total);
        button.href = `${whatsappUrl}?text=${encodeURIComponent(
            `Hello ${business}, I want to ${verb} ${foreign}. Your website shows ${naira(rate)} per ${currency.code}, about ${naira(total)}. Is this rate still available?`,
        )}`;
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('submit', (event) => event.preventDefault());

    updateCalculator = update;
    update();
}

/**
 * Switch between currencies on the rate history chart.
 */
function setUpTrendTabs() {
    document.querySelectorAll('[data-trend]').forEach((trend) => {
        const tabs = trend.querySelectorAll('[role="tab"]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((other) => {
                    const selected = other === tab;
                    other.setAttribute('aria-selected', String(selected));
                    other.tabIndex = selected ? 0 : -1;
                    trend.querySelector(`#${other.getAttribute('aria-controls')}`).hidden = !selected;
                });
            });
        });
    });
}

/**
 * Load the Google map only when the visitor asks for it, so the contact
 * page stays fast and Google is not contacted until then.
 */
function setUpMap() {
    document.querySelectorAll('[data-map]').forEach((holder) => {
        holder.querySelector('button')?.addEventListener('click', () => {
            const frame = document.createElement('iframe');
            frame.src = holder.dataset.map;
            frame.title = holder.dataset.mapTitle || 'Map';
            frame.loading = 'lazy';
            frame.referrerPolicy = 'no-referrer-when-downgrade';
            holder.replaceChildren(frame);
        });
    });
}

/**
 * Open receipt pictures in a popup instead of leaving the page. One listener
 * on the document works for every page.
 */
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-lightbox]');
    const dialog = document.querySelector('[data-lightbox-dialog]');

    if (link && dialog?.showModal) {
        event.preventDefault();
        const caption = link.dataset.lightboxCaption || '';
        const image = dialog.querySelector('[data-lightbox-image]');

        image.src = link.href;
        image.alt = caption;
        dialog.querySelector('[data-lightbox-text]').textContent = caption;
        dialog.showModal();

        return;
    }

    if (dialog?.open && (event.target === dialog || event.target.closest('[data-lightbox-close]'))) {
        dialog.close();
    }
});

/**
 * Set up everything on the current page. Runs on the first load and after
 * every wire:navigate page change; the marker on <body> stops it running
 * twice for the same page.
 */
function initPage() {
    if (document.body.dataset.siteReady) {
        return;
    }

    document.body.dataset.siteReady = '1';

    setUpMenu();
    setUpStagger();
    splitHeadline();
    setUpReveal();
    setUpCountUp();
    setUpTilt();
    syncFlips();
    setUpHeroRate();
    setUpCalculator();
    setUpTrendTabs();
    setUpMap();
    updateScrollEffects();
}

document.addEventListener('livewire:navigated', initPage);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    initPage();
}
