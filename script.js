function toggleDarkMode() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}

const SUPABASE_URL = 'https://twnpxipxtmgdohjckbjn.supabase.co';
const SUPABASE_KEY = 'sb_publishable_lu1sQo0v_aKaXvNQCh6SOw_ENj5JTPu';

const GAMES = {
    pokemon:  { tcgId: '21899972-d4ce-41e6-aeac-70d7a198d9ed', badge: 'Pokémon',   color: '#eab308' },
    yugioh:   { tcgId: '14d81db0-0e72-40da-a764-633d63b9a008', badge: 'Yu-Gi-Oh!', color: '#a855f7' },
    magic:    { tcgId: 'b292bb55-9b18-4438-a134-1cc049e19867', badge: 'Magic',     color: '#ef4444' },
    onepiece: { tcgId: '3e7c1156-bfdd-4583-98fa-6564a8ea5f35', badge: 'One Piece', color: '#f97316' }
};

const STATE = {
    pokemon:  { offset: 0, loading: false, done: false },
    yugioh:   { offset: 0, loading: false, done: false },
    magic:    { offset: 0, loading: false, done: false },
    onepiece: { offset: 0, loading: false, done: false }
};

const BACKUPS = {
    pokemon: 'img/pokemon.png',
    yugioh: 'img/yugioh.png',
    magic: 'img/magic.png',
    onepiece: 'img/onepiece.png'
};

const PAGE_SIZE = 12;

function createCardHTML(data) {
    const div = document.createElement('div');
    div.className = 'tcg-item';

    let backup = BACKUPS.pokemon;
    if (data.badge === 'Yu-Gi-Oh!') backup = BACKUPS.yugioh;
    if (data.badge === 'Magic') backup = BACKUPS.magic;
    if (data.badge === 'One Piece') backup = BACKUPS.onepiece;

    div.innerHTML = `
        <div class="tcg-bg">
            <img src="${data.img}" alt="${data.name}" onerror="this.onerror=null;this.src='${backup}'" loading="lazy">
        </div>
        <div class="scan-line"></div>
        <div class="tcg-info">
            <span class="card-badge" style="background-color: ${data.color}">${data.badge}</span>
            <div class="card-name">${data.name}</div>
            <div class="card-price">${data.price ? '$' + data.price : 'Sin stock'}</div>
        </div>
    `;

    div.addEventListener('click', () => openModal(data));
    return div;
}

function openModal(data) {
    document.getElementById('modal-img').src = data.img;
    document.getElementById('modal-title').textContent = data.name;
    document.getElementById('modal-badge').textContent = data.badge;
    document.getElementById('modal-badge').style.backgroundColor = data.color;
    document.getElementById('modal-price').textContent = data.price ? '$' + data.price : 'Sin stock';

    const list = document.getElementById('market-list');
    list.innerHTML = '';

    let basePrice = parseFloat(data.price) || 25.00;

    const sellers = [
        { user: "TitanCards_Pro", cond: "Mint", tag: "cond-mint", mult: 1.15 },
        { user: "LootMaster99", cond: "Near Mint", tag: "cond-near", mult: 1.0 },
        { user: "BargainTCG", cond: "Played", tag: "cond-played", mult: 0.75 }
    ];

    sellers.forEach(s => {
        const finalPrice = (basePrice * s.mult).toFixed(2);
        list.innerHTML += `
            <tr>
                <td>👤 ${s.user}</td>
                <td><span class="tag-condition ${s.tag}">${s.cond}</span></td>
                <td style="color: #16a34a;">$${finalPrice}</td>
                <td><button class="btn-buy-small">Comprar</button></td>
            </tr>
        `;
    });

    document.getElementById('card-modal').style.display = 'flex';
    setTimeout(() => { document.getElementById('card-modal').classList.add('open'); }, 10);
}

function closeModal() {
    document.getElementById('card-modal').classList.remove('open');
    setTimeout(() => { document.getElementById('card-modal').style.display = 'none'; }, 300);
}

async function loadCards(game, gridId) {
    const st = STATE[game];
    if (st.loading || st.done) return;
    st.loading = true;
    const grid = document.getElementById(gridId);
    const cfg = GAMES[game];

    try {
        const url = `${SUPABASE_URL}/rest/v1/cards`
            + `?tcg_id=eq.${cfg.tcgId}`
            + `&select=id,name,image_large,image_small`
            + `&order=name.asc`
            + `&limit=${PAGE_SIZE}&offset=${st.offset}`;

        const res = await fetch(url, {
            headers: {
                'apikey': SUPABASE_KEY,
                'Authorization': `Bearer ${SUPABASE_KEY}`
            }
        });
        if (!res.ok) throw new Error(`Supabase ${res.status}`);
        const cards = await res.json();

        if (cards.length === 0) {
            st.done = true;
            const btn = document.getElementById('mercado-load-more');
            if (btn) btn.style.display = 'none';
            return;
        }

        cards.forEach(card => {
            grid.appendChild(createCardHTML({
                badge: cfg.badge,
                color: cfg.color,
                name: card.name,
                img: card.image_large || card.image_small,
                price: null
            }));
        });
        st.offset += PAGE_SIZE;
        if (cards.length < PAGE_SIZE) st.done = true;
    } catch (err) {
        console.error(`[${game}] error cargando cartas:`, err);
    } finally {
        st.loading = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const gameParam = urlParams.get('game');

    if (document.getElementById('mercado-grid')) {
        const loadBtn = document.getElementById('mercado-load-more');
        const title = document.getElementById('mercado-title');

        const titles = {
            pokemon: 'Mercado: Pokémon TCG',
            yugioh: 'Mercado: Yu-Gi-Oh!',
            magic: 'Mercado: Magic The Gathering',
            onepiece: 'Mercado: One Piece'
        };

        if (GAMES[gameParam]) {
            title.innerText = titles[gameParam];
            loadCards(gameParam, 'mercado-grid');
            if (loadBtn) loadBtn.onclick = () => loadCards(gameParam, 'mercado-grid');
        }
    }
    else if (document.getElementById('main-content')) {
        loadCards('pokemon', 'pokemon-grid');
        loadCards('yugioh', 'yugioh-grid');
        loadCards('magic', 'magic-grid');
        loadCards('onepiece', 'onepiece-grid');
    }

    const modal = document.getElementById('card-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    }

    const closeBtn = document.getElementById('close-modal-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', () => {
            const query = filterSearch.value.toLowerCase().trim();
            document.querySelectorAll('#mercado-grid .tcg-item').forEach(card => {
                const name = card.querySelector('.card-name')?.textContent.toLowerCase() || '';
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
});
