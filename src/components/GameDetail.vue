<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { gamesData, toSlug } from '../assets/games.js';

const router = useRouter();
const route = useRoute();

const mode = ref('creator')

const game = computed(() => {
    const found = gamesData.find((g) => toSlug(g.title) === route.params.slug) || gamesData[0];

    return {
        ...found,
        platformsText: found.platforms.map((p) => p.toUpperCase()).join(' · '),
        screenshots: [
            { id: 1, label: 'Midtown Sector 7', img: '' },
            { id: 2, label: 'Chrono-Market', img: '' },
            { id: 3, label: 'The Spire Roof', img: '' }
        ],
        specs: [
            { label: 'OS', value: 'Windows 11 64-bit' },
            { label: 'Processor', value: 'Intel Core i7-12700K / Ryzen 7 5800X' },
            { label: 'Memory', value: '16 GB RAM' },
            { label: 'Graphics', value: 'NVIDIA RTX 3070 / RX 6800' }
        ]
    };
});

const activeShot = ref(0);
const isFavorited = ref(false);
const addedToCart = ref(false);

const reviews = ref([
    {
        id: 1,
        initials: 'RW',
        author: 'RivenArc',
        text: 'Every system feeds the fantasy. The city feels alive, the combat is precise, and I’m still finding new routes through old missions.'
    }
]);

const newReview = ref('');

function addToCart() {
    addedToCart.value = true;
    setTimeout(() => router.push('/cart'), 400);
}

function submitReview() {
    const text = newReview.value.trim();
    if (!text) return;

    reviews.value.unshift({
        id: Date.now(),
        initials: 'PX',
        author: 'PlayerOne',
        text
    });
    newReview.value = '';
}
</script>

<template>
    <header
        class="detail-hero d-flex flex-column justify-content-between"
        :style="game.heroImg ? { backgroundImage: `linear-gradient(to top, #05060a 12%, rgba(5,6,10,0.35)), url(${game.heroImg})` } : {}"
    >
        <div>
            <router-link to="/game" class="back text-decoration-none">← Back to library</router-link>
        </div>

        <div class="detail-title">
            <span class="badge">{{ game.genre }}</span>
            <h1>{{ game.title }}</h1>
            <div class="d-flex flex-wrap gap-3">
                <span class="rating">★ {{ game.rating }}</span>
                <span>{{ game.year }}</span>
                <span>{{ game.platforms }}</span>
            </div>
        </div>
    </header>

    <main class="detail-layout m-auto">
        <div class="row g-5">
            <div class="col-lg-8">
                <!-- Screenshot Gallery -->
                <div class="gallery mb-4">
                    <div class="gallery-main">
                        <img
                            v-if="game.screenshots[activeShot].img"
                            :src="game.screenshots[activeShot].img"
                            :alt="game.screenshots[activeShot].label"
                        />
                        <div v-else class="shot-placeholder">
                            <span>PREVIEW 0{{ activeShot + 1 }} — {{ game.screenshots[activeShot].label }}</span>
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div v-for="(shot, i) in game.screenshots" :key="shot.id" class="col-4">
                            <button
                                type="button"
                                class="thumb-btn w-100"
                                :class="{ active: activeShot === i }"
                                @click="activeShot = i"
                            >
                                0{{ i + 1 }} · {{ shot.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <section class="prose">
                    <span class="eyebrow">ABOUT THIS GAME</span>
                    <h2>Your choices outlive you.</h2>
                    <p>
                        Cross the electric divide in a city where every memory is currency. Build your crew, bend time, and decide who controls tomorrow. Explore a meticulously built world filled with meaningful encounters, layered systems, and stories that react to every decision.
                    </p>
                    <p class="mb-0">
                        Go alone or connect with your crew to uncover hidden districts and evolve your play style.
                    </p>
                </section>

                <!-- System Requirements -->
                <section class="mb-5">
                    <h2 class="section-heading">System Requirements</h2>
                    <div class="specs row g-0">
                        <div v-for="spec in game.specs" :key="spec.label" class="col-sm-6 spec-cell">
                            <span>{{ spec.label }}</span>
                            <strong>{{ spec.value }}</strong>
                        </div>
                    </div>
                </section>

                <!-- Player Reviews -->
                <section class="reviews">
                    <h2 class="section-heading">Player Signal</h2>

                    <form class="review-form d-flex gap-2 mb-4" @submit.prevent="submitReview">
                        <input
                            v-model="newReview"
                            type="text"
                            placeholder="Share your signal on this world..."
                            required
                        />
                        <button type="submit" class="btn btn-primary">Post</button>
                    </form>

                    <div class="d-grid gap-3">
                        <article v-for="review in reviews" :key="review.id">
                            <div class="review-head d-flex align-items-center gap-3 mb-3">
                                <div class="avatar">{{ review.initials }}</div>
                                <strong>{{ review.author }}</strong>
                            </div>
                            <p class="mb-0">{{ review.text }}</p>
                        </article>
                    </div>
                </section>
            </div>

            <!-- Sidebar Buy Card -->
            <aside class="col-lg-4">
                <div class="buy-card">
                    <div class="price">
                        <strong>RM {{ game.price.toFixed(2) }}</strong>
                    </div>

                    <button type="button" class="btn btn-primary w-100" @click="addToCart">
                        <img src="/cart.svg" alt="" width="18" height="18" />
                        {{ addedToCart ? 'Added to Cart' : 'Add to Cart' }}
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary w-100"
                        :class="{ favorited: isFavorited }"
                        @click="isFavorited = !isFavorited"
                    >
                        <span>{{ isFavorited ? '♥' : '♡' }}</span>
                        {{ isFavorited ? 'Saved in Favorites' : 'Add to Favourite' }}
                    </button>

                    <div class="buy-info">
                        <span>● Instant digital key delivery</span>
                        <span>● Secure checkout</span>
                    </div>

                    <div v-if="mode === 'creator'">
                        <hr class="my-1" />
                        <span class="badge badge-violet align-self-start w-100">CREATOR ACCESS</span>
                        <router-link to="/game/edit" class="btn btn-ghost w-100 text-decoration-none mt-2">
                            Edit Game
                        </router-link>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</template>

<style scoped>
.detail-hero {
    background:
        radial-gradient(circle at 75% 30%, rgba(0, 242, 254, 0.16), transparent 45%),
        radial-gradient(circle at 20% 80%, rgba(138, 108, 255, 0.2), transparent 50%),
        #080a0f;
    background-position: center;
    background-size: cover;
    min-height: 460px;
    padding: 40px max(5vw, 35px);
    border-bottom: 1px solid var(--line);
}

.back {
    color: #d5d7df;
    font-size: 12px;
}

.back:hover {
    color: var(--cyan);
}

.badge {
    letter-spacing: 0.13em;
    color: var(--cyan);
    background: rgba(0, 242, 254, 0.08);
    border: 1px solid currentColor;
    padding: 6px 9px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
}

.badge-violet {
    color: #a58fff;
    background: rgba(138, 108, 255, 0.08);
}

.detail-title h1 {
    letter-spacing: -0.07em;
    margin: 12px 0;
    font-size: clamp(52px, 8vw, 112px);
    line-height: 0.92;
}

.detail-title > div {
    color: #c4c7d0;
    font-size: 13px;
}

.rating {
    color: var(--gold);
}

.detail-layout {
    max-width: 1250px;
    padding: 50px max(4vw, 28px) 100px;
}

/* Gallery */
.gallery-main {
    aspect-ratio: 16 / 9;
    border: 1px solid var(--line);
    background: var(--panel);
    overflow: hidden;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shot-placeholder {
    height: 100%;
    display: grid;
    place-items: center;
    color: var(--muted);
    font: 600 12px 'Space Grotesk', sans-serif;
    letter-spacing: 0.15em;
    background: radial-gradient(circle, rgba(0, 242, 254, 0.06), transparent 70%);
}

.thumb-btn {
    background: var(--panel);
    border: 1px solid var(--line);
    color: var(--muted);
    padding: 10px 12px;
    font-size: 11px;
    text-align: left;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.thumb-btn.active,
.thumb-btn:hover {
    border-color: var(--cyan);
    color: var(--cyan);
}

/* Prose */
.prose {
    max-width: 750px;
    padding: 32px 0 56px;
}

.eyebrow {
    color: var(--cyan);
    letter-spacing: 0.2em;
    font-size: 11px;
    font-weight: 700;
    display: block;
}

.prose h2 {
    letter-spacing: -0.04em;
    margin: 8px 0 18px;
    font-size: clamp(26px, 3.5vw, 38px);
}

.prose p {
    color: #b4b8c3;
    line-height: 1.75;
}

/* Specs */
.section-heading {
    letter-spacing: -0.03em;
    font-size: 24px;
    margin-bottom: 20px;
}

.specs {
    border-top: 1px solid var(--line);
    border-left: 1px solid var(--line);
}

.spec-cell {
    border-right: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
    background: var(--panel);
    padding: 18px;
    display: grid;
    gap: 6px;
}

.spec-cell span {
    color: var(--muted);
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.spec-cell strong {
    font-size: 13px;
}

/* Reviews */
.review-form input {
    flex: 1;
    border: 1px solid var(--line);
    background: #0c0e13;
    color: #fff;
    padding: 12px 14px;
    outline: 0;
    font-size: 13px;
}

.review-form input:focus {
    border-color: var(--cyan);
}

.reviews article {
    background: var(--panel);
    border: 1px solid var(--line);
    padding: 22px;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid var(--line);
    background: linear-gradient(145deg, #28213d, #14171f);
    color: var(--cyan);
    font-size: 12px;
    font-weight: 700;
    display: grid;
    place-items: center;
}

.reviews p {
    color: #b4b8c3;
    line-height: 1.7;
    font-size: 14px;
}

/* Buy Card */
.buy-card {
    background: var(--panel2);
    border: 1px solid var(--line);
    gap: 10px;
    padding: 24px;
    display: grid;
    position: sticky;
    top: 100px;
}

.price {
    margin-bottom: 6px;
}

.price strong {
    font: 700 32px 'Space Grotesk', sans-serif;
}

.buy-info {
    color: var(--muted);
    gap: 8px;
    padding: 10px 0;
    font-size: 12px;
    display: grid;
}

.buy-card hr {
    border: 0;
    border-top: 1px solid var(--line);
    width: 100%;
}

.btn {
    border-radius: 0;
    border: 1px solid transparent;
    justify-content: center;
    align-items: center;
    gap: 9px;
    min-height: 44px;
    padding: 0 18px;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.2s;
    display: inline-flex;
}

.btn-primary {
    color: #061014;
    background: var(--cyan);
    border-color: var(--cyan);
    box-shadow: 0 0 22px #00f2fe2e;
}

.btn-primary:hover {
    background: var(--cyan);
    border-color: var(--cyan);
    color: #061014;
    filter: brightness(1.08);
}

.btn-secondary {
    color: var(--text);
    background: rgba(255, 255, 255, 0.06);
    border-color: var(--line);
}

.btn-secondary:hover,
.btn-secondary.favorited {
    border-color: var(--cyan);
    color: var(--cyan);
}

.btn-ghost {
    color: var(--text);
    border-color: var(--line);
    background: none;
}

.btn-ghost:hover {
    border-color: #a58fff;
    color: #a58fff;
}
</style>