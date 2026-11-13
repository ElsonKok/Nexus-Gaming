<script setup>
import { ref, computed, watch } from 'vue';
import Paginate from 'vuejs-paginate-next';

const genres = [
    { label: 'All', value: '' },
    { label: 'Action', value: 'action' },
    { label: 'Sci-Fi', value: 'sci-fi' },
    { label: 'Racing', value: 'racing' },
    { label: 'Strategy', value: 'strategy' },
    { label: 'Adventure', value: 'adventure' }
];

const platforms = [
    { label: 'PC', value: 'pc' },
    { label: 'PlayStation', value: 'playstation' },
    { label: 'Xbox', value: 'xbox' },
    { label: 'Nintendo Switch', value: 'switch' }
];

const games = ref([
    { id: 1, title: 'Chromatic Drift', genre: 'racing', platforms: ['pc', 'playstation', 'xbox', 'switch'], rating: 4.5, price: 19.99, releaseDate: '2025-02-10', img: '', liked: false },
    { id: 2, title: 'Aether Protocol', genre: 'sci-fi', platforms: ['pc', 'playstation'], rating: 4.9, price: 49.99, releaseDate: '2025-04-18', img: '', liked: true },
    { id: 3, title: 'Iron Veil', genre: 'strategy', platforms: ['pc'], rating: 4.7, price: 29.99, releaseDate: '2024-11-05', img: '', liked: false },
    { id: 4, title: 'Neon Ronin', genre: 'action', platforms: ['pc', 'playstation', 'xbox'], rating: 4.8, price: 39.99, releaseDate: '2025-01-22', img: '', liked: false },
    { id: 5, title: 'Solstice Echo', genre: 'adventure', platforms: ['pc', 'switch'], rating: 4.6, price: 24.99, releaseDate: '2024-09-14', img: '', liked: false },
    { id: 6, title: 'Void Runner', genre: 'sci-fi', platforms: ['pc', 'xbox'], rating: 4.4, price: 14.99, releaseDate: '2024-06-30', img: '', liked: false },
    { id: 7, title: 'Apex Circuit', genre: 'racing', platforms: ['playstation', 'xbox'], rating: 4.3, price: 59.99, releaseDate: '2025-03-01', img: '', liked: false }
]);

const search = ref('');
const selectedGenre = ref('');
const selectedPlatforms = ref([]);
const maxPrice = ref(100);
const sortBy = ref('rating');
const currentPage = ref(1);
const pageSize = 6;

const filteredGames = computed(() => {
    const query = search.value.trim().toLowerCase();

    return games.value
        .filter((game) => {
            const matchesSearch = !query || game.title.toLowerCase().includes(query);
            const matchesGenre = !selectedGenre.value || game.genre === selectedGenre.value;
            const matchesPlatform =
                selectedPlatforms.value.length === 0 ||
                selectedPlatforms.value.every((p) => game.platforms.includes(p));
            const matchesPrice = game.price <= maxPrice.value;

            return matchesSearch && matchesGenre && matchesPlatform && matchesPrice;
        })
        .sort((a, b) => {
            if (sortBy.value === 'price-low') return a.price - b.price;
            if (sortBy.value === 'date') return new Date(b.releaseDate) - new Date(a.releaseDate);
            return b.rating - a.rating;
        });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredGames.value.length / pageSize)));

const paginatedGames = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredGames.value.slice(start, start + pageSize);
});

watch([search, selectedGenre, selectedPlatforms, maxPrice, sortBy], () => {
    currentPage.value = 1;
});

function formatPlatforms(list) {
    return list.length === 4 ? 'All platforms' : list.map((p) => p.toUpperCase()).join(' · ');
}
</script>

<template>
    <div class="page">
        <div class="page-heading">
            <span class="eyebrow">THE NEXUS ARCHIVE</span>
            <h1>Game Library</h1>
            <p>Explore hand-picked worlds from visionary studios.</p>
        </div>

        <div class="catalog-layout d-grid">
            <aside class="filters">
                <h3 class="d-flex align-items-center gap-3">
                    <img src="/filter.svg" alt="Filters" width="18" height="18" />
                    Filters
                </h3>

                <label class="d-grid gap-2">
                    Search
                    <input v-model="search" class="w-100" type="text" placeholder="Game title..." />
                </label>

                <fieldset>
                    <legend>Genre</legend>
                    <label
                        v-for="g in genres"
                        :key="g.value"
                        class="check d-flex align-items-center gap-2"
                    >
                        <input v-model="selectedGenre" type="radio" name="genre" :value="g.value" />
                        <span>{{ g.label }}</span>
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Platform</legend>
                    <label
                        v-for="p in platforms"
                        :key="p.value"
                        class="check d-flex align-items-center gap-2"
                    >
                        <input v-model="selectedPlatforms" type="checkbox" :value="p.value" />
                        <span>{{ p.label }}</span>
                    </label>
                </fieldset>

                <label class="d-grid gap-2">
                    <span class="d-flex justify-content-between">
                        <span>Maximum Price</span>
                        <strong class="text-white">${{ maxPrice }}</strong>
                    </span>
                    <input v-model.number="maxPrice" type="range" min="0" max="100" step="1" />
                </label>
            </aside>

            <div class="catalog-content">
                <div class="result-bar d-flex align-items-center justify-content-between">
                    <span><strong class="text-white">{{ filteredGames.length }}</strong> games found</span>
                    <select v-model="sortBy">
                        <option value="rating">Highest Rating</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="date">Release Date</option>
                    </select>
                </div>

                <div v-if="paginatedGames.length" class="row g-4">
                    <div
                        v-for="game in paginatedGames"
                        :key="game.id"
                        class="col-12 col-md-6 col-xl-4"
                    >
                        <article class="game-card h-100">
                            <a href="#" class="card-image">
                                <img v-if="game.img" :src="game.img" :alt="game.title" class="w-100" />
                                <div v-else class="img-fallback">{{ game.title[0] }}</div>
                                <span class="card-platform">{{ formatPlatforms(game.platforms) }}</span>
                            </a>

                            <button
                                type="button"
                                class="heart"
                                :class="{ active: game.liked }"
                                @click="game.liked = !game.liked"
                            >
                                {{ game.liked ? '♥' : '♡' }}
                            </button>

                            <div class="card-body">
                                <span>{{ game.genre }}</span>
                                <a href="#">
                                    <h3>{{ game.title }}</h3>
                                </a>
                                <div class="card-meta d-flex justify-content-between">
                                    <span class="rating">★ {{ game.rating.toFixed(1) }}</span>
                                    <strong class="text-white">${{ game.price.toFixed(2) }}</strong>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <p v-else class="empty-state">No games match your current filters.</p>

                <div v-if="totalPages > 1" class="d-flex justify-content-center mt-5">
                    <Paginate
                        v-model="currentPage"
                        :page-count="totalPages"
                        :page-range="5"
                        :margin-pages="1"
                        prev-text="Prev"
                        next-text="Next"
                        container-class="pagination"
                        page-class="page-item"
                        page-link-class="page-link"
                        prev-class="page-item"
                        prev-link-class="page-link"
                        next-class="page-item"
                        next-link-class="page-link"
                        active-class="active"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page {
    padding: 60px max(4vw, 28px);
}

.page-heading {
    margin-bottom: 42px;
}

.eyebrow {
    color: var(--cyan);
    letter-spacing: 0.2em;
    font-size: 11px;
    font-weight: 700;
    display: block;
}

.page-heading h1 {
    letter-spacing: -0.055em;
    margin: 10px 0;
    font-size: clamp(42px, 6vw, 78px);
}

.page-heading p {
    color: var(--muted);
    font-size: 17px;
}

.catalog-layout {
    grid-template-columns: 240px 1fr;
    gap: 40px;
}

.filters {
    border-right: 1px solid var(--line);
    padding-right: 30px;
}

.filters label {
    color: #c9ccd5;
    margin: 22px 0;
    font-size: 12px;
}

.filters input:not([type="radio"]):not([type="checkbox"]):not([type="range"]) {
    border: 1px solid var(--line);
    color: #fff;
    background: #0c0e13;
    outline: 0;
    padding: 12px;
}

.filters fieldset {
    border: 0;
    border-bottom: 1px solid var(--line);
    padding: 18px 0;
    margin-bottom: 20px;
}

.filters legend {
    margin-bottom: 12px;
    font-size: 12px;
    font-weight: 700;
}

.filters .check {
    color: var(--muted);
    margin: 10px 0;
    cursor: pointer;
}

.result-bar {
    color: var(--muted);
    margin-bottom: 20px;
    font-size: 13px;
}

.result-bar select {
    background: var(--panel);
    border: 1px solid var(--line);
    color: #fff;
    padding: 10px 12px;
}

.game-card {
    background: var(--panel);
    border: 1px solid var(--line);
    transition: border-color 0.25s, transform 0.25s;
    position: relative;
    overflow: hidden;
}

.game-card:hover {
    border-color: var(--cyan);
    transform: translateY(-3px);
}

.card-image {
    height: 260px;
    display: block;
    position: relative;
    overflow: hidden;
    background: #0c0e13;
}

.card-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(5, 6, 10, 0.85), transparent 50%);
}

.card-image img {
    object-fit: cover;
    filter: saturate(0.85);
    height: 100%;
    transition: transform 0.4s, filter 0.4s;
}

.game-card:hover .card-image img {
    transform: scale(1.04);
    filter: saturate(1);
}

.img-fallback {
    height: 100%;
    display: grid;
    place-items: center;
    font: 700 44px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: radial-gradient(circle, rgba(0, 242, 254, 0.08), transparent 70%);
}

.card-platform {
    z-index: 2;
    color: #d3d6dd;
    font-size: 10px;
    letter-spacing: 0.08em;
    position: absolute;
    bottom: 12px;
    left: 14px;
}

.heart {
    z-index: 3;
    color: #fff;
    backdrop-filter: blur(6px);
    background: rgba(5, 6, 10, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.22);
    place-items: center;
    width: 36px;
    height: 36px;
    display: grid;
    position: absolute;
    top: 12px;
    right: 12px;
    cursor: pointer;
    transition: color 0.2s, border-color 0.2s;
}

.heart.active,
.heart:hover {
    color: var(--cyan);
    border-color: var(--cyan);
}

.card-body {
    padding: 16px;
}

.card-body > span {
    color: var(--cyan);
    text-transform: uppercase;
    letter-spacing: 0.13em;
    font-size: 10px;
}

.card-body h3 {
    margin: 6px 0 14px;
    font-size: 19px;
}

.card-meta {
    color: var(--muted);
    font-size: 12px;
}

.rating {
    color: var(--gold);
}

.empty-state {
    color: var(--muted);
    padding: 60px 0;
    text-align: center;
}

a {
    color: inherit;
    text-decoration: none;
}

:deep(.pagination) {
    gap: 8px;
}

:deep(.page-link) {
    background: var(--panel);
    border: 1px solid var(--line);
    color: #fff;
    padding: 8px 14px;
    font-size: 13px;
}

:deep(.page-item.active .page-link) {
    background: var(--cyan);
    border-color: var(--cyan);
    color: #05060a;
    font-weight: 700;
}

@media (max-width: 900px) {
    .catalog-layout {
        grid-template-columns: 1fr;
    }

    .filters {
        border-right: 0;
        border-bottom: 1px solid var(--line);
        padding-right: 0;
        padding-bottom: 24px;
    }
}
</style>