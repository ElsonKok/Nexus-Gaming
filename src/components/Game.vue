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
                        <strong class="text-white">RM {{ maxPrice }}</strong>
                    </span>
                    <input v-model.number="maxPrice" type="range" min="0" max="200" step="1" />
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

                <p v-if="loading" class="empty-state">Loading games...</p>
                <p v-else-if="err" class="empty-state text-danger">Error: {{ err }}</p>

                <div v-else-if="paginatedGames.length" class="row g-4">
                    <div
                        v-for="game in paginatedGames"
                        :key="game.game_id"
                        class="col-12 col-md-6 col-xl-4"
                    >
                        <article class="game-card h-100">
                            <router-link :to="`/game/${toSlug(game.title)}`" class="card-image">
                                <img v-if="game.image" :src="game.image" :alt="game.title" class="w-100" />
                                <div v-else class="img-fallback">{{ game.title[0] }}</div>
                                <span class="card-platform">{{ formatPlatforms(game.platformList) }}</span>
                            </router-link>

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
                                <router-link :to="`/game/${toSlug(game.title)}`">
                                    <h3>{{ game.title }}</h3>
                                </router-link>
                                <div class="card-meta d-flex justify-content-between">
                                    <span class="rating">★ {{ game.rating.toFixed(1) }}</span>
                                    <strong class="text-white">
                                        {{ game.price === 0 ? 'Free' : `RM ${game.price.toFixed(2)}` }}
                                    </strong>
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
                        :click-handler="clickCallback"
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

<script>
import Paginate from 'vuejs-paginate-next';

export default {
    name: 'GameView',
    components: {
        Paginate
    },
    data() {
        return {
            genres: [
                { label: 'All', value: '' },
                { label: 'Battle Royale', value: 'battle-royale' },
                { label: 'Sports', value: 'sports' },
                { label: 'Shooter', value: 'shooter' },
                { label: 'Action RPG', value: 'action-rpg' },
                { label: 'MMORPG', value: 'mmorpg' },
                { label: 'Adventure', value: 'adventure' },
                { label: 'Racing', value: 'racing' },
                { label: 'Strategy', value: 'strategy' },
            ],
            platforms: [
                { label: 'PC', value: 'pc' },
                { label: 'PlayStation', value: 'playstation' },
                { label: 'Xbox', value: 'xbox' },
                { label: 'Nintendo Switch', value: 'switch' }
            ],
            games: [],
            loading: false,
            err: '',
            search: '',
            selectedGenre: '',
            selectedPlatforms: [],
            maxPrice: 200,
            sortBy: 'rating',
            currentPage: 1,
            pageSize: 6
        };
    },
    mounted() {
        this.readData();
    },
    computed: {
        filteredGames() {
            const query = this.search.trim().toLowerCase();

            return this.games
                .filter((game) => {
                    const matchesSearch = !query || game.title.toLowerCase().includes(query);
                    const matchesGenre = !this.selectedGenre || game.genre.toLowerCase() === this.selectedGenre;
                    const matchesPlatform =
                        this.selectedPlatforms.length === 0 ||
                        this.selectedPlatforms.every((p) => game.platformKeys.includes(p));
                    const matchesPrice = game.price <= this.maxPrice;

                    return matchesSearch && matchesGenre && matchesPlatform && matchesPrice;
                })
                .sort((a, b) => {
                    if (this.sortBy === 'price-low') return a.price - b.price;
                    if (this.sortBy === 'date') return new Date(b.release_date) - new Date(a.release_date);
                    return b.rating - a.rating;
                });
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.filteredGames.length / this.pageSize));
        },
        paginatedGames() {
            const start = (this.currentPage - 1) * this.pageSize;
            return this.filteredGames.slice(start, start + this.pageSize);
        }
    },
    watch: {
        search() {
            this.currentPage = 1;
        },
        selectedGenre() {
            this.currentPage = 1;
        },
        selectedPlatforms: {
            handler() {
                this.currentPage = 1;
            },
            deep: true
        },
        maxPrice() {
            this.currentPage = 1;
        },
        sortBy() {
            this.currentPage = 1;
        }
    },
    methods: {
        toSlug(title) {
            return String(title)
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        },
        parsePlatforms(raw) {
            if (!raw) return [];
            return String(raw)
                .split(',')
                .map((p) => p.trim())
                .filter(Boolean);
        },
        toPlatformKey(label) {
            const lower = label.toLowerCase();
            if (lower.includes('switch')) return 'switch';
            if (lower.includes('playstation') || lower === 'ps5' || lower === 'ps4') return 'playstation';
            if (lower.includes('xbox')) return 'xbox';
            if (lower.includes('pc')) return 'pc';
            return lower;
        },
        readData() {
            this.loading = true;
            this.err = '';

            fetch(`/resources/apis.php/`)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP error: ${response.status}`);
                    }
                    return response.json();
                })
                .then((data) => {
                    this.games = data.map((row) => {
                        const platformList = this.parsePlatforms(row.platform);
                        return {
                            game_id: row.game_id,
                            title: row.title,
                            genre: row.genre,
                            platform: row.platform,
                            platformList,
                            platformKeys: platformList.map((p) => this.toPlatformKey(p)),
                            developer: row.developer,
                            release_date: row.release_date,
                            description: row.description,
                            image: row.image || '',
                            url: row.url,
                            price: Number(row.price) || 0,
                            rating: Number(row.rating) || 0,
                            liked: false
                        };
                    });
                })
                .catch((error) => {
                    this.err = error.message;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        formatPlatforms(list) {
            return list.length >= 4 ? 'All platforms' : list.join(' · ');
        },
        clickCallback(pageNum) {
            this.currentPage = Number(pageNum);
        }
    }
};
</script>

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