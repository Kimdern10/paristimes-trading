/*
 * Small scripts for the public website: the mobile menu, scroll animations
 * and the rate calculator. No framework, so pages stay light.
 */

document.documentElement.classList.add('js');

/**
 * Open and close the menu on phones.
 */
function setUpMenu() {
    const header = document.querySelector('[data-header]');
    const toggle = document.querySelector('[data-menu-toggle]');

    if (!header || !toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = header.classList.toggle('menu-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && header.classList.contains('menu-open')) {
            toggle.click();
            toggle.focus();
        }
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

    if (!form) {
        return;
    }

    const rates = JSON.parse(form.dataset.rates || '[]');
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
        const currency = rates.find((rate) => rate.code === currencySelect.value);
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

    update();
}

setUpMenu();
setUpReveal();
setUpCalculator();
