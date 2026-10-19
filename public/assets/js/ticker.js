/*
 * Live crypto ticker on the dashboard: two rows of coin cards that scroll in opposite directions.
 * Prices: CoinGecko public API (top 20 by market cap), cached for 60 s in localStorage and refreshed every 60 s.
 * Cards are built with textContent only (API text is never inserted as HTML).
 */
(() => {
    'use strict';

    const root = document.querySelector('[data-ticker]');
    if (!root) {
        return;
    }
    const tracks = [...root.querySelectorAll('[data-ticker-track]')];
    const status = root.querySelector('[data-ticker-status]');
    const API = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc'
        + '&per_page=20&page=1&sparkline=false&price_change_percentage=24h';
    const CACHE_KEY = 'glow.ticker.v1';
    const REFRESH_MS = 60000;
    const PX_PER_SECOND = 45;

    // Shown instantly (no prices) until the first live answer arrives.
    const FALLBACK = [
        ['bitcoin', 'Bitcoin', 'BTC'], ['ethereum', 'Ethereum', 'ETH'], ['tether', 'Tether', 'USDT'],
        ['binancecoin', 'BNB', 'BNB'], ['ripple', 'XRP', 'XRP'], ['solana', 'Solana', 'SOL'],
        ['usd-coin', 'USDC', 'USDC'], ['tron', 'TRON', 'TRX'], ['dogecoin', 'Dogecoin', 'DOGE'],
        ['cardano', 'Cardano', 'ADA'], ['chainlink', 'Chainlink', 'LINK'], ['avalanche-2', 'Avalanche', 'AVAX'],
        ['stellar', 'Stellar', 'XLM'], ['litecoin', 'Litecoin', 'LTC'], ['polkadot', 'Polkadot', 'DOT'],
        ['shiba-inu', 'Shiba Inu', 'SHIB'], ['bitcoin-cash', 'Bitcoin Cash', 'BCH'],
        ['the-open-network', 'Toncoin', 'TON'], ['sui', 'Sui', 'SUI'], ['monero', 'Monero', 'XMR'],
    ].map(([id, name, symbol]) => ({ id, name, symbol, price: null, change: null, image: '' }));

    let signature = '';

    const money = (n) => (n === null || Number.isNaN(n)
        ? '—'
        : '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: n < 1 ? 6 : 2 }));

    const percent = (n) => (n === null || Number.isNaN(n) ? '' : (n >= 0 ? '▲ ' : '▼ ') + Math.abs(n).toFixed(2) + '%');

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    };

    const logo = (coin) => {
        const badge = () => el('span', 'coin__logo coin__badge', coin.symbol.slice(0, 1));
        if (!/^https:\/\//.test(coin.image)) {
            return badge();
        }
        const img = el('img', 'coin__logo');
        img.src = coin.image;
        img.alt = '';
        img.loading = 'lazy';
        img.referrerPolicy = 'no-referrer';
        img.addEventListener('error', () => img.replaceWith(badge()));
        return img;
    };

    const card = (coin) => {
        const item = el('div', 'coin');
        item.dataset.coin = coin.id;
        const info = el('div', 'coin__info');
        info.append(el('p', 'coin__name', coin.name), el('p', 'coin__sym', coin.symbol + '/USDT'));
        const quote = el('div', 'coin__quote');
        quote.append(el('p', 'coin__price', money(coin.price)), el('p', 'coin__chg', percent(coin.change)));
        item.append(logo(coin), info, quote);
        paint(item, coin);
        return item;
    };

    const paint = (item, coin) => {
        item.querySelector('.coin__price').textContent = money(coin.price);
        const chg = item.querySelector('.coin__chg');
        chg.textContent = percent(coin.change);
        chg.classList.toggle('is-green', coin.change !== null && coin.change >= 0);
        chg.classList.toggle('is-red', coin.change !== null && coin.change < 0);
    };

    /** Build the rows (every card twice, so the loop is seamless). */
    const build = (coins) => {
        tracks.forEach((track, row) => {
            track.replaceChildren();
            const mine = coins.filter((_, i) => i % tracks.length === row);
            mine.forEach((coin) => track.append(card(coin)));
            mine.forEach((coin) => {
                const copy = card(coin);
                copy.setAttribute('aria-hidden', 'true');
                track.append(copy);
            });
            const half = track.scrollWidth / 2;
            track.style.setProperty('--ticker-time', Math.max(30, Math.round(half / PX_PER_SECOND)) + 's');
        });
    };

    const render = (coins) => {
        const next = coins.map((c) => c.id).join(',');
        if (next !== signature) {
            signature = next;
            build(coins);
            return;
        }
        coins.forEach((coin) => {
            root.querySelectorAll('.coin').forEach((item) => {
                if (item.dataset.coin === coin.id) {
                    paint(item, coin);
                }
            });
        });
    };

    const clean = (rows) => rows.filter((r) => r && typeof r.id === 'string').map((r) => ({
        id: r.id,
        name: String(r.name ?? r.id),
        symbol: String(r.symbol ?? '').toUpperCase(),
        price: typeof r.current_price === 'number' ? r.current_price : (typeof r.price === 'number' ? r.price : null),
        change: typeof r.price_change_percentage_24h === 'number' ? r.price_change_percentage_24h
            : (typeof r.change === 'number' ? r.change : null),
        image: typeof r.image === 'string' ? r.image : '',
    }));

    const setStatus = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    const load = async () => {
        try {
            const response = await fetch(API, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const coins = clean(await response.json());
            if (!coins.length) {
                throw new Error('empty');
            }
            render(coins);
            setStatus('Live · updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
            try {
                localStorage.setItem(CACHE_KEY, JSON.stringify({ at: Date.now(), coins }));
            } catch (e) { /* storage full or blocked: ignore */ }
        } catch (e) {
            setStatus(signature && signature !== FALLBACK.map((c) => c.id).join(',')
                ? 'Live prices paused · retrying'
                : 'Live prices unavailable');
        }
    };

    // 1) show something at once: cached prices if we have them, else the plain coin list
    let cached = null;
    try {
        cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
    } catch (e) { /* ignore */ }
    const fresh = cached && Array.isArray(cached.coins) && Date.now() - cached.at < REFRESH_MS;
    render(cached && Array.isArray(cached.coins) ? clean(cached.coins) : FALLBACK);
    setStatus(fresh ? 'Live · cached' : 'Loading…');

    // 2) then go live and keep refreshing
    if (!fresh) {
        load();
    }
    setInterval(load, REFRESH_MS);
})();