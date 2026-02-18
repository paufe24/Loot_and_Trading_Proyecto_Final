const STATE = {
    pokemon:  { page: 0, loading: false },
    yugioh:   { page: 0, loading: false },
    magic:    { page: 0, loading: false },
    onepiece: { loaded: false }
};

const BACKUPS = {
    pokemon:  'https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg',
    yugioh:   'https://upload.wikimedia.org/wikipedia/en/2/2b/Yugioh_Card_Back.jpg',
    magic:    'https://upload.wikimedia.org/wikipedia/en/a/aa/Magic_the_gathering-card_back.jpg',
    onepiece: 'https://asia-en.onepiece-cardgame.com/images/common/back.jpg'
};

const POKEMON_DB = [
    { name: 'Charizard VMAX',          img: 'https://images.pokemontcg.io/swsh3/20_hires.png',       price: '$45.00' },
    { name: 'Pikachu V',               img: 'https://images.pokemontcg.io/swsh4/43_hires.png',       price: '$12.00' },
    { name: 'Mewtwo GX',               img: 'https://images.pokemontcg.io/sm12/78_hires.png',        price: '$18.00' },
    { name: 'Rayquaza VMAX',           img: 'https://images.pokemontcg.io/swsh7/111_hires.png',      price: '$30.00' },
    { name: 'Umbreon VMAX',            img: 'https://images.pokemontcg.io/swsh7/95_hires.png',       price: '$55.00' },
    { name: 'Sylveon VMAX',            img: 'https://images.pokemontcg.io/swsh7/74_hires.png',       price: '$22.00' },
    { name: 'Gengar VMAX',             img: 'https://images.pokemontcg.io/swsh6/157_hires.png',      price: '$20.00' },
    { name: 'Blastoise V',             img: 'https://images.pokemontcg.io/swsh4/22_hires.png',       price: '$14.00' },
    { name: 'Venusaur VMAX',           img: 'https://images.pokemontcg.io/swsh1/4_hires.png',        price: '$16.00' },
    { name: 'Charizard V',             img: 'https://images.pokemontcg.io/swsh3/19_hires.png',       price: '$25.00' },
    { name: 'Eternatus VMAX',          img: 'https://images.pokemontcg.io/swsh4/117_hires.png',      price: '$35.00' },
    { name: 'Dragapult VMAX',          img: 'https://images.pokemontcg.io/swsh5/93_hires.png',       price: '$18.00' },
    { name: 'Pikachu VMAX',            img: 'https://images.pokemontcg.io/swsh4/188_hires.png',      price: '$60.00' },
    { name: 'Mew VMAX',                img: 'https://images.pokemontcg.io/swsh12pt5/114_hires.png',  price: '$40.00' },
    { name: 'Lugia VSTAR',             img: 'https://images.pokemontcg.io/swsh11/139_hires.png',     price: '$28.00' },
    { name: 'Arceus VSTAR',            img: 'https://images.pokemontcg.io/swsh9/123_hires.png',      price: '$22.00' },
    { name: 'Giratina VSTAR',          img: 'https://images.pokemontcg.io/swsh11/131_hires.png',     price: '$32.00' },
    { name: 'Mewtwo VSTAR',            img: 'https://images.pokemontcg.io/swsh12pt5/164_hires.png',  price: '$50.00' },
    { name: 'Celebi VMAX',             img: 'https://images.pokemontcg.io/swsh7/7_hires.png',        price: '$15.00' },
    { name: 'Espeon VMAX',             img: 'https://images.pokemontcg.io/swsh7/65_hires.png',       price: '$20.00' }
];

const MAGIC_DB = [
    { name: 'Black Lotus',              img: 'https://cards.scryfall.io/normal/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg', price: '$10,000.00' },
    { name: 'Sol Ring',                 img: 'https://cards.scryfall.io/normal/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg', price: '$2.00' },
    { name: 'Lightning Bolt',           img: 'https://cards.scryfall.io/normal/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg', price: '$1.50' },
    { name: 'Counterspell',             img: 'https://cards.scryfall.io/normal/front/8/c/8c87b6ea-dc48-4096-a6b2-82b5c05fbe29.jpg', price: '$1.00' },
    { name: 'Force of Will',            img: 'https://cards.scryfall.io/normal/front/f/d/fd18dc8a-f89b-4947-9726-b6d45f230c9d.jpg', price: '$100.00' },
    { name: 'Jace, the Mind Sculptor',  img: 'https://cards.scryfall.io/normal/front/5/7/57f09b91-f1df-4370-a7c9-bdebc68e7f60.jpg', price: '$60.00' },
    { name: 'Liliana of the Veil',      img: 'https://cards.scryfall.io/normal/front/2/0/20e0ee54-ffd2-4e6d-9282-8e9c7ee0dcad.jpg', price: '$30.00' },
    { name: 'Tarmogoyf',                img: 'https://cards.scryfall.io/normal/front/e/0/e05f6c38-3548-477f-8f2b-d72ca1ca6cdf.jpg', price: '$25.00' },
    { name: 'Path to Exile',            img: 'https://cards.scryfall.io/normal/front/f/e/feb0c19e-4e3e-4d51-a6af-d78c7d544ed6.jpg', price: '$3.00' },
    { name: 'Cryptic Command',          img: 'https://cards.scryfall.io/normal/front/a/3/a3867bce-d2c3-4357-85fc-e15917161d2d.jpg', price: '$10.00' },
    { name: 'Noble Hierarch',           img: 'https://cards.scryfall.io/normal/front/8/e/8e0c8264-0891-48a6-900b-8c4f7a4cfdae.jpg', price: '$12.00' },
    { name: 'Avacyn, Angel of Hope',    img: 'https://cards.scryfall.io/normal/front/f/d/fd2b340a-9c47-4379-9f7a-e5cb618e218f.jpg', price: '$18.00' },
    { name: 'Snapcaster Mage',          img: 'https://cards.scryfall.io/normal/front/2/f/2ffc5f41-f14a-4ddf-ba29-94f72e8b4791.jpg', price: '$15.00' },
    { name: 'Birds of Paradise',        img: 'https://cards.scryfall.io/normal/front/9/a/9ab5cbae-4e21-4b2a-a9c1-f1d5cb05cdef.jpg', price: '$6.00' },
    { name: 'Emrakul, the Aeons Torn',  img: 'https://cards.scryfall.io/normal/front/4/5/451e3c38-b1de-4976-ba9f-68ba2bab7c7a.jpg', price: '$40.00' },
    { name: 'Thoughtseize',             img: 'https://cards.scryfall.io/normal/front/b/d/b de5c5f3a-0a0e-4e13-9ea3-f14893c41c97.jpg', price: '$8.00' },
    { name: 'Chord of Calling',         img: 'https://cards.scryfall.io/normal/front/4/2/42232ea6-9f9f-4d06-8d98-5e5d0e0b1aed.jpg', price: '$5.00' },
    { name: 'Dark Confidant',           img: 'https://cards.scryfall.io/normal/front/7/5/75e4e830-2547-44fa-9a41-bd4e6f00cd56.jpg', price: '$20.00' },
    { name: 'Mox Sapphire',             img: 'https://cards.scryfall.io/normal/front/b/a/ba0e9be4-c988-4fac-9c93-1e49ff5e3e8f.jpg', price: '$4,000.00' },
    { name: 'Ancestral Recall',         img: 'https://cards.scryfall.io/normal/front/2/4/2398892b-c0d2-4226-8d82-87df83a0a7a5.jpg', price: '$5,000.00' }
];

const ONEPIECE_DB = [
    { name: 'Monkey D. Luffy (Leader)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png', price: '$150.00' },
    { name: 'Roronoa Zoro (SR)',        img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png', price: '$45.00' },
    { name: 'Nami (Alt Art)',           img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png', price: '$250.00' },
    { name: 'Shanks (SEC)',             img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png', price: '$1,200.00' },
    { name: 'Yamato (SEC)',             img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png', price: '$300.00' },
    { name: 'Trafalgar Law',            img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-002.png', price: '$150.00' },
    { name: 'Boa Hancock',              img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-078.png', price: '$400.00' },
    { name: 'Sanji',                    img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-013.png', price: '$180.00' }
];

// ── CREAR CARTA ──────────────────────────────────────────────────────
function createCardHTML(data) {
    const div = document.createElement('div');
    div.className = 'tcg-item';

    let backup = BACKUPS.pokemon;
    if (data.badge === 'Yu-Gi-Oh!') backup = BACKUPS.yugioh;
    if (data.badge === 'Magic')     backup = BACKUPS.magic;
    if (data.badge === 'One Piece') backup = BACKUPS.onepiece;

    div.innerHTML = `
        <div class="card-badge">${data.badge}</div>
        <img src="${data.img}" alt="${data.name}"
             onerror="this.onerror=null;this.src='${backup}'"
             loading="lazy">
        <div class="card-info">
            <div class="card-name">${data.name}</div>
            <div class="card-price">${data.price || 'N/A'}</div>
        </div>
    `;
    div.addEventListener('click', () => openModal(data));
    return div;
}

// ── MODAL ────────────────────────────────────────────────────────────
function openModal(data) {
    document.getElementById('modal-img').src           = data.img;
    document.getElementById('modal-title').textContent = data.name;
    document.getElementById('modal-badge').textContent = data.badge;
    document.getElementById('modal-price').textContent = data.price || 'N/A';
    document.getElementById('card-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('card-modal').style.display = 'none';
}

// ── POKÉMON ──────────────────────────────────────────────────────────
function loadPokemonCards() {
    const grid  = document.getElementById('pokemon-grid');
    const start = STATE.pokemon.page * 8;
    const items = POKEMON_DB.slice(start, start + 8);
    if (items.length === 0) return;
    items.forEach(card => grid.appendChild(createCardHTML({ badge: 'Pokémon', ...card })));
    STATE.pokemon.page++;
}

// ── YU-GI-OH ─────────────────────────────────────────────────────────
async function loadYugiohCards() {
    if (STATE.yugioh.loading) return;
    STATE.yugioh.loading = true;

    const grid   = document.getElementById('yugioh-grid');
    const offset = STATE.yugioh.page * 20;

    try {
        const res  = await fetch(`api_proxy.php?game=yugioh&offset=${offset}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();

        if (!json.data || json.data.length === 0) { STATE.yugioh.loading = false; return; }

        json.data.forEach(card => {
            const rawPrice = card.card_prices?.[0]?.tcgplayer_price;
            grid.appendChild(createCardHTML({
                badge: 'Yu-Gi-Oh!',
                name:  card.name,
                img:   card.card_images?.[0]?.image_url || BACKUPS.yugioh,
                price: rawPrice ? `$${parseFloat(rawPrice).toFixed(2)}` : 'N/A'
            }));
        });

        STATE.yugioh.page++;
    } catch (err) {
        console.error('Error Yu-Gi-Oh!:', err);
        grid.insertAdjacentHTML('beforeend', '<p style="color:red;padding:1rem;">Error al cargar Yu-Gi-Oh!.</p>');
    }

    STATE.yugioh.loading = false;
}

// ── MAGIC ────────────────────────────────────────────────────────────
function loadMagicCards() {
    const grid  = document.getElementById('magic-grid');
    const start = STATE.magic.page * 8;
    const items = MAGIC_DB.slice(start, start + 8);
    if (items.length === 0) return;
    items.forEach(card => grid.appendChild(createCardHTML({ badge: 'Magic', ...card })));
    STATE.magic.page++;
}

// ── ONE PIECE ────────────────────────────────────────────────────────
function loadOnePieceCards() {
    if (STATE.onepiece.loaded) return;
    const grid = document.getElementById('onepiece-grid');
    ONEPIECE_DB.forEach(card => grid.appendChild(createCardHTML({ badge: 'One Piece', ...card })));
    STATE.onepiece.loaded = true;
}

// ── CAMBIAR SECCIÓN (MENÚ) ───────────────────────────────────────────
function switchGame(game) {
    // Ocultar todas las secciones
    document.querySelectorAll('.game-section').forEach(s => {
        s.classList.remove('active');
    });

    // Quitar activo de todos los nav-item
    document.querySelectorAll('.nav-item').forEach(n => {
        n.classList.remove('active');
    });

    // Mostrar sección seleccionada
    const section = document.getElementById('section-' + game);
    if (section) section.classList.add('active');

    // Marcar nav-item activo
    document.querySelectorAll('.nav-item').forEach(n => {
        const oc = n.getAttribute('onclick') || '';
        if (oc.includes("'" + game + "'")) n.classList.add('active');
    });

    // Cargar si grid vacío
    const grid = document.getElementById(game + '-grid');
    if (grid && grid.children.length === 0) {
        if (game === 'pokemon')  loadPokemonCards();
        if (game === 'yugioh')   loadYugiohCards();
        if (game === 'magic')    loadMagicCards();
        if (game === 'onepiece') loadOnePieceCards();
    }
}

// ── BOTONES CARGAR MÁS ───────────────────────────────────────────────
function loadMorePokemonCards() { loadPokemonCards(); }
function loadMoreYugiohCards()  { loadYugiohCards(); }
function loadMoreMagicCards()   { loadMagicCards(); }

// ── INICIO ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadPokemonCards();

    document.getElementById('card-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});