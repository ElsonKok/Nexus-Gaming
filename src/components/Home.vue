<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { getGames, toSlug } from '../api/games.js';

const router = useRouter();

/* ---------------- data ---------------- */
const games = ref([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');

async function loadGames() {
    loading.value = true;
    error.value = '';
    try {
        games.value = await getGames();
    } catch (err) {
        console.error('Error loading games:', err);
        error.value = `Unable to load games. ${err.message}`;
    } finally {
        loading.value = false;
    }
}

onMounted(loadGames);

/* ---------------- what each section shows ---------------- */
// 4 highest rated games
const byNewest = (a, b) => new Date(b.releaseDate || 0) - new Date(a.releaseDate || 0);

const popularGames = computed(() =>
    [...games.value].sort((a, b) => b.rating - a.rating || byNewest(a, b)).slice(0, 4)
);

// 4 newest games
const latestGames = computed(() =>
    [...games.value].sort(byNewest).slice(0, 4)
);

// the hero highlights the top rated game
const heroGame = computed(() => popularGames.value[0] || null);

// categories come from the genres in the database (up to 8, biggest first)
const categories = computed(() => {
    const counts = {};
    games.value.forEach((g) => {
        counts[g.genre] = (counts[g.genre] || 0) + 1;
    });
    return Object.entries(counts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .map(([value, count]) => ({ value, count, label: value }));
});

const genreIcons = {
    Shooter: '🎯',
    MMORPG: '🌍',
    'Action RPG': '⚔️',
    'Battle Royale': '👑',
    Racing: '🏎️',
    Strategy: '♟️',
    Sports: '⚽',
    Adventure: '🧭',
};
const genreIcon = (genre) => genreIcons[genre] || '🎮';
const categoryStyles = ['category-a', 'category-b', 'category-c', 'category-d'];

// real numbers for the hero text
const heroBlurb = computed(() =>
    games.value.length
        ? `Browse ${games.value.length} games across ${new Set(games.value.map((g) => g.genre)).size} genres, compare ratings and prices, and save your favourites before you buy.`
        : 'Browse our library, compare ratings and prices, and save your favourites before you buy.'
);

/* ---------------- helpers ---------------- */
// platforms come from the database as names, e.g. ['PC', 'Xbox']
const platformsText = (list = []) =>
    list.length >= 4 ? 'All platforms' : list.join(' · ');

const price = (value) => `RM ${Number(value).toFixed(2)}`;
const ratingText = (g) => (g.rating > 0 ? `★ ${g.rating.toFixed(1)}` : 'New');
const gameLink = (game) => `/game/${toSlug(game.title)}`;

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Date to be announced';

// games without an image get a coloured tile (the colour comes from the title)
const hue = (text) => [...text].reduce((h, c) => (h * 31 + c.charCodeAt(0)) % 360, 0);
const tileStyle = (game) => ({
    background: `linear-gradient(135deg, hsl(${hue(game.title)} 45% 24%), hsl(${(hue(game.title) + 40) % 360} 40% 9%))`,
});

/* ---------------- search ---------------- */
function searchGames() {
    const text = searchQuery.value.trim();
    router.push({ path: '/game', query: text ? { search: text } : {} });
}

/* ---------------- guest / logged-in content ----------------
   TODO: replace with the team's real login state
   (whatever Login.vue stores once the login API works). */
const isLoggedIn = computed(() => {
    try {
        return !!localStorage.getItem('user');
    } catch (e) {
        return false;
    }
});
</script>

<template>
    <main class="home-page">
        <!-- ============ Hero ============ -->
        <section class="hero-section">
            <div class="container hero-container">
                <div class="row align-items-center">
                    <div class="col-lg-7 hero-content">
                        <span class="hero-tag">YOUR NEXT ADVENTURE STARTS HERE</span>

                        <h1>
                            Discover Your Next
                            <span>Gaming Adventure.</span>
                        </h1>

                        <p>{{ heroBlurb }}</p>

                        <form class="hero-search" role="search" @submit.prevent="searchGames">
                            <input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search for your favourite games..."
                                aria-label="Search games"
                            />
                            <button type="submit">Search</button>
                        </form>

                        <div class="hero-actions">
                            <router-link to="/game" class="btn-primary-custom">Explore Games &rarr;</router-link>
                            <router-link to="/about" class="btn-secondary-custom">Learn More</router-link>
                        </div>
                    </div>

                    <div class="col-lg-5 hero-visual">
                        <div v-if="heroGame" class="hero-image-wrapper">
                            <router-link :to="gameLink(heroGame)" class="hero-art" :aria-label="`View ${heroGame.title}`">
                                <img v-if="heroGame.image" :src="heroGame.image" :alt="heroGame.title" class="hero-image" />
                                <div v-else class="hero-image hero-fallback" :style="tileStyle(heroGame)">{{ heroGame.title[0] }}</div>
                            </router-link>

                            <router-link :to="gameLink(heroGame)" class="hero-floating-card">
                                <span class="floating-icon">🎮</span>
                                <div>
                                    <strong>Top rated: {{ heroGame.title }}</strong>
                                    <small>{{ ratingText(heroGame) }} · {{ price(heroGame.price) }}</small>
                                </div>
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ Quick benefits ============ -->
        <section class="benefits-section">
            <div class="container">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="benefit-item">
                            <span class="benefit-icon">🎮</span>
                            <div>
                                <strong>Curated Collection</strong>
                                <small>Hand-picked games across every genre.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="benefit-item">
                            <span class="benefit-icon">⭐</span>
                            <div>
                                <strong>Ratings &amp; Reviews</strong>
                                <small>See what other players think.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="benefit-item">
                            <span class="benefit-icon">♥</span>
                            <div>
                                <strong>Your Favourite Games</strong>
                                <small>Save games to your favourite list.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ Popular games ============ -->
        <section class="content-section">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-subtitle">HANDPICKED FOR YOU</span>
                        <h2>Top Rated Games</h2>
                        <p>The community's highest rated worlds.</p>
                    </div>
                    <router-link :to="{ path: '/game', query: { sort: 'rating' } }" class="view-all">
                        View All Games &rarr;
                    </router-link>
                </div>

                <div v-if="loading" class="state-box">
                    <div class="spinner-border text-info" role="status"></div>
                    <p>Loading games...</p>
                </div>

                <div v-else-if="error" class="state-box" role="alert">
                    <p class="text-danger">{{ error }}</p>
                    <button type="button" class="btn-primary-custom" @click="loadGames">Try Again</button>
                </div>

                <p v-else-if="!popularGames.length" class="state-box">No games available yet.</p>

                <div v-else class="row g-4">
                    <div v-for="game in popularGames" :key="game.id" class="col-12 col-sm-6 col-lg-3">
                        <article class="game-card">
                            <router-link :to="gameLink(game)" class="card-image">
                                <img v-if="game.img" :src="game.img" :alt="game.title" loading="lazy" />
                                <div v-else class="img-fallback" :style="tileStyle(game)">{{ game.title[0] }}</div>
                            </router-link>

                            <div class="game-info">
                                <span class="game-category">{{ game.genre }}</span>
                                <h3>
                                    <router-link :to="gameLink(game)">{{ game.title }}</router-link>
                                </h3>

                                <p class="game-description">{{ game.description }}</p>

                                <div class="game-platform">{{ platformsText(game.platforms) }}</div>

                                <div class="game-meta">
                                    <span class="rating">{{ ratingText(game) }}</span>
                                    <strong>{{ price(game.price) }}</strong>
                                </div>

                                <router-link :to="gameLink(game)" class="game-link">View Details &rarr;</router-link>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ Categories ============ -->
        <section v-if="categories.length" class="category-section">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-subtitle">FIND YOUR PLAYSTYLE</span>
                        <h2>Browse Categories</h2>
                        <p>Find games that match your interests.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div v-for="(c, i) in categories" :key="c.value" class="col-6 col-md-3">
                        <router-link
                            class="category-card"
                            :class="categoryStyles[i % categoryStyles.length]"
                            :to="{ path: '/game', query: { category: c.value } }"
                        >
                            <span class="category-icon" aria-hidden="true">{{ genreIcon(c.value) }}</span>
                            <h3>{{ c.label }}</h3>
                            <span class="category-count">{{ c.count }} {{ c.count === 1 ? 'game' : 'games' }}</span>
                        </router-link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ Recently released ============ -->
        <section v-if="!loading && !error && latestGames.length" class="offers-section">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-subtitle">FRESH FROM THE GAME WORLD</span>
                        <h2>Recently Released</h2>
                        <p>The latest additions to our library.</p>
                    </div>
                    <router-link :to="{ path: '/game', query: { sort: 'date' } }" class="view-all">
                        Explore More &rarr;
                    </router-link>
                </div>

                <div class="row g-4">
                    <div v-for="game in latestGames" :key="game.id" class="col-12 col-md-6">
                        <article class="offer-card">
                            <router-link :to="gameLink(game)" class="offer-thumb">
                                <img v-if="game.img" :src="game.img" :alt="game.title" loading="lazy" />
                                <div v-else class="img-fallback" :style="tileStyle(game)">{{ game.title[0] }}</div>
                            </router-link>

                            <div class="offer-details">
                                <div>
                                    <span class="offer-label">NEW RELEASE</span>
                                    <h3>{{ game.title }}</h3>
                                    <span class="game-category">{{ game.genre }}</span>
                                </div>

                                <div class="release-date">Released {{ formatDate(game.releaseDate) }}</div>

                                <div class="offer-footer">
                                    <strong>{{ price(game.price) }}</strong>
                                    <router-link :to="gameLink(game)" class="offer-button">View Details</router-link>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ Call to action (different for guests / members) ============ -->
        <section class="cta-section">
            <div class="container">
                <div v-if="isLoggedIn" class="cta-content">
                    <span class="section-subtitle">WELCOME BACK</span>
                    <h2>Pick up where you <span>left off.</span></h2>
                    <p>Your favourites and cart are waiting for you.</p>
                    <div class="cta-actions">
                        <router-link to="/favourite" class="btn-primary-custom">My Favourites</router-link>
                        <router-link to="/cart" class="btn-secondary-custom">Go to Cart</router-link>
                    </div>
                </div>

                <div v-else class="cta-content">
                    <span class="section-subtitle">JOIN THE COMMUNITY</span>
                    <h2>Ready to Start Your <span>Next Adventure?</span></h2>
                    <p>Create an account to buy games, save favourites and share reviews.</p>
                    <div class="cta-actions">
                        <router-link to="/register" class="btn-primary-custom">Get Started &rarr;</router-link>
                        <router-link to="/login" class="btn-secondary-custom">Log in</router-link>
                    </div>
                </div>
            </div>
        </section>

    </main>
</template>

<style scoped>
.home-page {
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

.container {
    max-width: 1200px;
}

a {
    color: inherit;
    text-decoration: none;
}

/* ---------- shared bits ---------- */
.section-subtitle {
    color: var(--cyan);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2em;
}

.section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.section-heading h2 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.03em;
    margin: 8px 0;
}

.section-heading p {
    color: var(--muted);
    font-size: 14px;
    margin: 0;
}

.view-all {
    color: var(--cyan);
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.view-all:hover {
    color: #fff;
}

.btn-primary-custom,
.btn-secondary-custom {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 20px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    transition: filter 0.2s, border-color 0.2s, color 0.2s;
}

.btn-primary-custom {
    color: #061014;
    background: var(--cyan);
    border-color: var(--cyan);
    box-shadow: 0 0 22px #00f2fe2e;
}

.btn-primary-custom:hover {
    color: #061014;
    filter: brightness(1.08);
}

.btn-secondary-custom {
    color: var(--text);
    border-color: var(--line);
    background: #ffffff0a;
}

.btn-secondary-custom:hover {
    color: var(--cyan);
    border-color: var(--cyan);
}

.btn-primary-custom:focus-visible,
.btn-secondary-custom:focus-visible,
.game-card a:focus-visible,
.category-card:focus-visible,
.hero-search button:focus-visible,
.offer-button:focus-visible {
    outline: 2px solid var(--cyan);
    outline-offset: 2px;
}

.img-fallback {
    height: 100%;
    display: grid;
    place-items: center;
    font: 700 44px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: radial-gradient(circle, rgba(0, 242, 254, 0.08), transparent 70%), var(--panel2);
}

.state-box {
    text-align: center;
    padding: 50px 0;
    color: var(--muted);
}

.state-box p {
    margin: 12px 0;
}

/* ---------- hero ---------- */
.hero-section {
    padding: 75px 0;
    background:
        radial-gradient(ellipse at 80% 40%, rgba(0, 242, 254, 0.12) 0%, transparent 55%),
        linear-gradient(120deg, #10141e, var(--bg));
    overflow: hidden;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-tag {
    display: inline-block;
    color: var(--cyan);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.2em;
    margin-bottom: 20px;
}

.hero-content h1 {
    font-size: clamp(38px, 5vw, 64px);
    line-height: 1.05;
    font-weight: 900;
    letter-spacing: -0.05em;
    max-width: 650px;
    margin-bottom: 22px;
}

.hero-content h1 span,
.cta-content h2 span {
    color: var(--cyan);
}

.hero-content p {
    color: var(--muted);
    font-size: 15px;
    line-height: 1.8;
    max-width: 510px;
    margin-bottom: 28px;
}

.hero-search {
    display: flex;
    max-width: 510px;
    padding: 5px;
    background: var(--panel);
    border: 1px solid var(--line);
}

.hero-search:focus-within {
    border-color: var(--cyan);
}

.hero-search input {
    flex: 1;
    min-width: 0;
    background: transparent;
    border: none;
    outline: none;
    padding: 10px;
    color: #fff;
    font-size: 13px;
}

.hero-search input::placeholder {
    color: #747d8c;
}

.hero-search button {
    background: var(--cyan);
    color: #061014;
    border: none;
    padding: 10px 17px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
}

.hero-search button:hover {
    filter: brightness(1.08);
}

.hero-actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.hero-visual {
    margin-top: 30px;
}

.hero-image-wrapper {
    position: relative;
    max-width: 450px;
    margin: auto;
}

.hero-art {
    display: block;
}

.hero-image {
    width: 100%;
    height: 360px;
    object-fit: cover;
    display: block;
    border: 1px solid var(--line);
    box-shadow: 0 15px 50px #0008, 0 0 40px rgba(0, 242, 254, 0.12);
}

.hero-fallback {
    display: grid;
    place-items: center;
    font: 700 80px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: radial-gradient(circle, rgba(0, 242, 254, 0.1), transparent 70%), var(--panel);
}

.hero-floating-card {
    position: absolute;
    bottom: 20px;
    left: -25px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: var(--panel);
    border: 1px solid var(--line);
    box-shadow: 0 10px 30px #0008;
}

.hero-floating-card:hover {
    border-color: var(--cyan);
}

.floating-icon {
    font-size: 28px;
}

.hero-floating-card strong,
.hero-floating-card small {
    display: block;
}

.hero-floating-card strong {
    font-size: 13px;
}

.hero-floating-card small {
    color: var(--muted);
    font-size: 11px;
    margin-top: 4px;
}

/* ---------- benefits ---------- */
.benefits-section {
    padding: 25px 0;
    background: var(--panel);
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
}

.benefit-icon {
    color: var(--cyan);
    font-size: 26px;
    width: 34px;
    text-align: center;
}

.benefit-item strong,
.benefit-item small {
    display: block;
}

.benefit-item strong {
    font-size: 13px;
}

.benefit-item small {
    color: var(--muted);
    font-size: 11px;
    margin-top: 4px;
}

/* ---------- sections ---------- */
.content-section,
.category-section,
.offers-section {
    padding: 65px 0;
}

.category-section {
    background: var(--panel);
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

/* ---------- game card ---------- */
.game-card {
    height: 100%;
    overflow: hidden;
    background: var(--panel);
    border: 1px solid var(--line);
    transition: border-color 0.25s, transform 0.25s;
}

.game-card:hover {
    transform: translateY(-5px);
    border-color: var(--cyan);
}

.card-image {
    display: block;
    height: 180px;
    overflow: hidden;
    background: var(--panel2);
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s;
}

.game-card:hover .card-image img {
    transform: scale(1.04);
}

.game-info {
    padding: 17px;
}

.game-category {
    color: var(--cyan);
    font-size: 10px;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.game-info h3 {
    font-size: 17px;
    font-weight: 800;
    margin: 6px 0 8px;
}

.game-info h3 a:hover {
    color: var(--cyan);
}

.game-description {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
    min-height: 58px;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.game-platform {
    color: var(--muted);
    font-size: 11px;
    margin-top: 10px;
}

.game-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    font-size: 14px;
}

.rating {
    color: var(--gold);
}

.game-meta strong {
    color: #fff;
}

.game-link {
    display: block;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--line);
    color: #d7dce5;
    font-size: 12px;
    font-weight: 700;
}

.game-link:hover {
    color: var(--cyan);
}

/* ---------- categories ---------- */
.category-card {
    display: block;
    height: 130px;
    padding: 20px;
    border: 1px solid var(--line);
    color: #fff;
    transition: transform 0.25s, border-color 0.25s;
}

.category-card:hover {
    transform: translateY(-5px);
    border-color: var(--cyan);
    color: #fff;
}

.category-icon {
    font-size: 28px;
}

.category-card h3 {
    font-size: 16px;
    font-weight: 800;
    margin: 12px 0 5px;
}

.category-count {
    color: #b1b8c4;
    font-size: 11px;
}

.category-a { background: linear-gradient(135deg, #24213e, #171a29); }
.category-b { background: linear-gradient(135deg, #153d3b, #171a29); }
.category-c { background: linear-gradient(135deg, #402b25, #171a29); }
.category-d { background: linear-gradient(135deg, #243d53, #171a29); }

/* ---------- recently released ---------- */
.offer-card {
    display: flex;
    height: 100%;
    min-height: 200px;
    overflow: hidden;
    background: var(--panel);
    border: 1px solid var(--line);
    transition: border-color 0.25s;
}

.offer-card:hover {
    border-color: rgba(0, 242, 254, 0.5);
}

.offer-thumb {
    display: block;
    flex: 0 0 42%;
    background: var(--panel2);
}

.offer-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.offer-details {
    flex: 1;
    min-width: 0;
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
}

.offer-label {
    display: block;
    color: var(--cyan);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.1em;
}

.offer-details h3 {
    font-size: 17px;
    font-weight: 800;
    margin: 7px 0;
}

.release-date {
    color: var(--muted);
    font-size: 11px;
}

.offer-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.offer-footer strong {
    color: var(--cyan);
}

.offer-button {
    background: #222c39;
    color: #fff;
    padding: 8px 14px;
    font-size: 11px;
    font-weight: 800;
    transition: background 0.2s, color 0.2s;
}

.offer-button:hover {
    background: var(--cyan);
    color: #061014;
}

/* ---------- call to action ---------- */
.cta-section {
    padding: 65px 0;
    background: radial-gradient(ellipse at center, rgba(0, 242, 254, 0.14), transparent 70%), var(--panel);
    border-top: 1px solid var(--line);
    text-align: center;
}

.cta-content h2 {
    font-size: clamp(28px, 4vw, 40px);
    font-weight: 900;
    letter-spacing: -0.04em;
    margin: 15px 0;
}

.cta-content p {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 25px;
}

.cta-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}


/* ---------- small screens ---------- */
@media (max-width: 767px) {
    .hero-section {
        padding: 50px 0;
    }

    .hero-visual {
        margin-top: 45px;
    }

    .hero-image {
        height: 240px;
    }

    .hero-floating-card {
        left: 5px;
        bottom: 10px;
    }

    .section-heading {
        align-items: flex-start;
    }

    .section-heading h2 {
        font-size: 23px;
    }

    .view-all {
        font-size: 11px;
    }

    .content-section,
    .category-section,
    .offers-section {
        padding: 45px 0;
    }
}

@media (max-width: 400px) {
    .section-heading {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .offer-thumb {
        flex-basis: 38%;
    }

    .offer-details {
        padding: 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .game-card,
    .category-card,
    .card-image img {
        transition: none;
    }
}
</style>