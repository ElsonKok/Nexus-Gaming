<template>
    <div v-if="loading" class="text-center py-5 text-white">Loading game details...</div>
    <div v-else-if="err" class="text-center py-5 text-danger">{{ err }}</div>

    <template v-else-if="game">
        <header
            class="detail-hero d-flex flex-column justify-content-between"
            :style="game.image ? { backgroundImage: `linear-gradient(to top, #05060a 12%, rgba(5,6,10,0.35)), url(${game.image})` } : {}"
        >
            <div>
                <router-link to="/game" class="back text-decoration-none">← Back to library</router-link>
            </div>

            <div class="detail-title">
                <span class="badge">{{ game.genre }}</span>
                <h1>{{ game.title }}</h1>
                <div class="d-flex flex-wrap gap-3">
                    <span class="rating">★ {{ game.rating.toFixed(1) }}</span>
                    <span>{{ releaseYear }}</span>
                    <span>{{ platformsText }}</span>
                </div>
            </div>
        </header>

        <main class="detail-layout m-auto">
            <div class="row g-5">
                <div class="col-lg-8">
                    <!-- Main Preview -->
                    <div class="gallery mb-4">
                        <div class="gallery-main">
                            <img
                                v-if="game.image"
                                :src="game.image"
                                :alt="game.title"
                            />
                            <div v-else class="shot-placeholder">
                                <span>MAIN PREVIEW — {{ game.title }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- About -->
                    <section class="prose">
                        <span class="eyebrow">ABOUT THIS GAME</span>
                        <h2>{{ game.title }}</h2>
                        <p class="mb-0">
                            {{ game.description }}
                        </p>
                    </section>

                    <!-- System Requirements -->
                    <section class="mb-5">
                        <h2 class="section-heading">System Requirements (Mininum)</h2>
                        <div class="specs row g-0">
                            <div v-for="spec in specs" :key="spec.label" class="col-sm-6 spec-cell">
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
                        <div class="price d-flex align-items-center justify-content-between">
                            <strong>{{ isFree ? 'Free' : `RM ${game.price.toFixed(2)}` }}</strong>
                            <span v-if="isPurchased" class="owned-badge">● IN LIBRARY</span>
                            <span v-else-if="isFree" class="free-badge">FREE TO PLAY</span>
                        </div>

                        <!-- Already Owned State -->
                        <router-link
                            v-if="isPurchased"
                            to="/profile"
                            class="btn btn-owned w-100 text-decoration-none"
                        >
                            ✓ Owned · View in Library →
                        </router-link>

                        <!-- Free or Paid Add to Cart State -->
                        <button
                            v-else
                            type="button"
                            class="btn btn-primary w-100"
                            :disabled="addingToCart"
                            @click="addToCart"
                        >
                            <img v-if="!isFree" :src="`${baseUrl}cart.svg`" alt="" width="18" height="18" />
                            {{
                                addingToCart
                                    ? 'Adding...'
                                    : addedToCart
                                        ? 'Added to Cart'
                                        : isFree
                                            ? '+ Claim Free Game'
                                            : 'Add to Cart'
                            }}
                        </button>

                        <p v-if="cartError" class="text-danger small mb-0">{{ cartError }}</p>

                        <button
                            type="button"
                            class="btn btn-secondary w-100"
                            :class="{ favorited: isFavorited }"
                            :disabled="togglingFav"
                            @click="toggleFavourite"
                        >
                            <span>{{ isFavorited ? '♥' : '♡' }}</span>
                            {{ isFavorited ? 'Saved in Favorites' : 'Add to Favourite' }}
                        </button>

                        <a
                            v-if="game.url"
                            :href="game.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-ghost w-100 text-decoration-none"
                        >
                            Official Website ↗
                        </a>

                        <div class="buy-info">
                            <span>● Developer: {{ developerName || game.developer }}</span>
                            <span>● Release Date: {{ game.release_date }}</span>
                            <span>
                                ● {{
                                    isPurchased
                                        ? 'Unlocked on your Nexus account'
                                        : isFree
                                            ? 'Free instant library unlock'
                                            : 'Instant digital key delivery'
                                }}
                            </span>
                        </div>

                        <div v-if="isCreator">
                            <hr class="my-1" />
                            <span class="badge badge-violet align-self-start w-100">CREATOR ACCESS</span>
                            <router-link :to="`/games/edit/${game.game_id}`" class="btn btn-ghost w-100 text-decoration-none mt-2">
                                Edit Game
                            </router-link>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </template>
</template>

<script>
export default {
    name: 'GameDetail',
    data() {
        return {
            baseUrl: import.meta.env.BASE_URL,
            currentUser: null,
            game: null,
            developerName: '',
            loading: false,
            err: '',
            isFavorited: false,
            favId: null,
            togglingFav: false,
            isPurchased: false,
            addingToCart: false,
            addedToCart: false,
            cartError: '',
            specs: [
                { label: 'OS', value: 'Windows 10/11 64-bit' },
                { label: 'Processor', value: 'Intel Core i7-12700K / Ryzen 7 5800X' },
                { label: 'Memory', value: '16 GB RAM' },
                { label: 'Graphics', value: 'NVIDIA RTX 3070 / RX 6800' }
            ],
            reviews: [
                {
                    id: 1,
                    initials: 'RW',
                    author: 'RivenArc',
                    text: 'Every system feeds the fantasy. The city feels alive, the combat is precise, and I’m still finding new routes through old missions.'
                }
            ],
            newReview: ''
        };
    },
    computed: {
        isFree() {
            return Number(this.game?.price) === 0;
        },
        isCreator() {
            const role = String(this.currentUser?.role || '').toLowerCase();
            return role === 'creator' || role === 'developer' || Boolean(this.currentUser?.developer_id);
        },
        activeAccountId() {
            return String(
                this.currentUser?.user_id ||
                this.currentUser?.developer_id ||
                this.currentUser?.id ||
                ''
            ).trim();
        },
        platformsText() {
            if (!this.game) return '';
            if (Array.isArray(this.game.platformList) && this.game.platformList.length) {
                return this.game.platformList.join(' · ');
            }
            return String(this.game.platform || '')
                .split(',')
                .map((p) => p.trim())
                .filter(Boolean)
                .join(' · ');
        },
        releaseYear() {
            if (!this.game || !this.game.release_date) return '';
            return String(this.game.release_date).split('-')[0];
        }
    },
    mounted() {
        this.loadUser();
        this.loadGame();
    },
    watch: {
        '$route.params.slug'() {
            this.loadGame();
        }
    },
    methods: {
        loadUser() {
            try {
                this.currentUser =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user'));
            } catch {
                this.currentUser = null;
            }
        },
        toSlug(title) {
            return String(title)
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        },
        normalizeRow(row) {
            const platformList = Array.isArray(row.platformList)
                ? row.platformList
                : String(row.platform || '')
                      .split(',')
                      .map((p) => p.trim())
                      .filter(Boolean);

            return {
                game_id: row.game_id,
                title: row.title,
                genre: row.genre,
                platform: row.platform,
                platformList,
                developer: row.developer,
                developer_id: row.developer_id || '',
                release_date: row.release_date,
                description: row.description,
                image: row.image || '',
                url: row.url,
                price: Number(row.price) || 0,
                rating: Number(row.rating) || 0,
                liked: Boolean(row.liked)
            };
        },
        resolveDeveloperName() {
            const rawDev = String(this.game?.developer_id || this.game?.developer || '').trim();
            this.developerName = String(this.game?.developer || '').trim();

            if (!rawDev) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            fetch(`${baseApi}/developer`)
                .then((res) => res.json())
                .then((devs) => {
                    if (!Array.isArray(devs)) return;
                    const matched = devs.find(
                        (d) =>
                            String(d.developer_id).trim().toLowerCase() === rawDev.toLowerCase() ||
                            String(d.username).trim().toLowerCase() === rawDev.toLowerCase()
                    );
                    if (matched && matched.username) {
                        this.developerName = matched.username;
                    }
                })
                .catch(() => {});
        },
        checkIfPurchased() {
            this.isPurchased = false;
            const uid = String(this.currentUser?.user_id || this.currentUser?.id || '').trim();
            const gid = String(this.game?.game_id || '').trim();

            if (!uid || !gid || this.isCreator) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            fetch(`${baseApi}/payment/user_id/${encodeURIComponent(uid)}`)
                .then((res) => res.json())
                .then((rows) => {
                    if (Array.isArray(rows)) {
                        this.isPurchased = rows.some(
                            (p) => String(p.game_id).trim() === gid
                        );
                    }
                })
                .catch(() => {
                    this.isPurchased = false;
                });
        },
        checkIfFavorited() {
            this.isFavorited = false;
            this.favId = null;

            const uid = this.activeAccountId;
            const gid = String(this.game?.game_id || '').trim();

            if (!uid || !gid) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            fetch(`${baseApi}/favourite/user_id/${encodeURIComponent(uid)}`)
                .then((res) => res.json())
                .then((rows) => {
                    if (Array.isArray(rows)) {
                        const match = rows.find((f) => String(f.game_id).trim() === gid);
                        if (match) {
                            this.isFavorited = true;
                            this.favId = match.fav_id;
                        }
                    }
                })
                .catch(() => {});
        },
        loadGame() {
            this.err = '';
            this.developerName = '';
            this.addedToCart = false;
            this.isPurchased = false;
            this.isFavorited = false;
            this.favId = null;
            this.cartError = '';

            const slug = this.$route.params.slug;
            const saved = sessionStorage.getItem('selectedGame');

            if (saved) {
                const parsed = JSON.parse(saved);
                if (this.toSlug(parsed.title) === slug) {
                    this.game = this.normalizeRow(parsed);
                    this.resolveDeveloperName();
                    this.checkIfPurchased();
                    this.checkIfFavorited();
                    return;
                }
            }

            // Fallback if opened directly or refreshed at /game/:slug
            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.loading = true;
            fetch(`${baseApi}/game`)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    const match = Array.isArray(data)
                        ? data.find((row) => this.toSlug(row.title) === slug)
                        : null;
                    if (match) {
                        this.game = this.normalizeRow(match);
                        this.resolveDeveloperName();
                        this.checkIfPurchased();
                        this.checkIfFavorited();
                    } else {
                        this.err = 'Game not found.';
                    }
                })
                .catch((error) => {
                    this.err = error.message;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        toggleFavourite() {
            this.loadUser();
            const uid = this.activeAccountId;

            if (!uid) {
                this.$router.push('/login');
                return;
            }

            const gid = String(this.game?.game_id || '').trim();
            if (!gid) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.togglingFav = true;

            if (this.isFavorited && this.favId) {
                fetch(`${baseApi}/favourite/fav_id/${encodeURIComponent(this.favId)}`, {
                    method: 'DELETE'
                })
                    .then((res) => {
                        if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                        this.isFavorited = false;
                        this.favId = null;
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: 'Removed from favourites', type: 'info' }
                            })
                        );
                    })
                    .catch((error) => {
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: 'Error: ' + error.message, type: 'error' }
                            })
                        );
                    })
                    .finally(() => {
                        this.togglingFav = false;
                    });
            } else {
                fetch(`${baseApi}/favourite`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: uid,
                        game_id: gid
                    })
                })
                    .then((res) => {
                        if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                        return res.json();
                    })
                    .then((data) => {
                        if (data.error) throw new Error(data.error);
                        this.isFavorited = true;
                        this.favId = data.fav_id || null;
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: `Saved ${this.game.title} to favourites`, type: 'success' }
                            })
                        );
                    })
                    .catch((error) => {
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: 'Error: ' + error.message, type: 'error' }
                            })
                        );
                    })
                    .finally(() => {
                        this.togglingFav = false;
                    });
            }
        },
        addToCart() {
            this.cartError = '';
            this.loadUser();

            if (this.isPurchased) {
                window.dispatchEvent(
                    new CustomEvent('show-toast', {
                        detail: { message: 'You already own this game', type: 'info' }
                    })
                );
                return;
            }

            const uid = String(this.currentUser?.user_id || this.currentUser?.id || '').trim();
            if (!uid) {
                this.$router.push('/login');
                return;
            }

            if (this.isCreator) {
                this.cartError = 'Creator accounts cannot purchase games.';
                return;
            }

            const gid = String(this.game?.game_id || '').trim();
            if (!gid) {
                this.cartError = 'Invalid game ID.';
                return;
            }

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.addingToCart = true;

            // Check if this game is already in the user's cart
            fetch(`${baseApi}/cart/user_id/${encodeURIComponent(uid)}`)
                .then((res) => res.json())
                .then((existingRows) => {
                    const items = Array.isArray(existingRows) ? existingRows : [];
                    const existingItem = items.find((row) => String(row.game_id) === gid);

                    if (existingItem && existingItem.cart_id) {
                        const nextQty = (Number(existingItem.quantity) || 1) + 1;
                        return fetch(`${baseApi}/cart/cart_id/${encodeURIComponent(existingItem.cart_id)}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ quantity: nextQty })
                        });
                    }

                    // Insert new cart row; apis.php auto-generates C0001
                    return fetch(`${baseApi}/cart`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: uid,
                            game_id: gid,
                            quantity: 1
                        })
                    });
                })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    if (data.error) {
                        this.cartError = data.error;
                        return;
                    }
                    this.addedToCart = true;
                    window.dispatchEvent(new Event('cart-changed'));
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: `${this.game.title} added to cart`, type: 'success' }
                        })
                    );
                })
                .catch((error) => {
                    this.cartError = 'Could not add to cart: ' + error.message;
                })
                .finally(() => {
                    this.addingToCart = false;
                });
        },
        submitReview() {
            const text = this.newReview.trim();
            if (!text) return;

            const authorName = this.currentUser?.username || 'PlayerOne';
            const initials = authorName
                .split(' ')
                .map((w) => w[0])
                .join('')
                .slice(0, 2)
                .toUpperCase();

            this.reviews.unshift({
                id: Date.now(),
                initials: initials || 'PX',
                author: authorName,
                text
            });
            this.newReview = '';
        }
    }
};
</script>

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

/* Main Preview */
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

.owned-badge {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    color: #55f5ae;
    background: rgba(85, 245, 174, 0.1);
    border: 1px solid rgba(85, 245, 174, 0.45);
    padding: 5px 9px;
}

.free-badge {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    color: var(--cyan);
    background: rgba(0, 242, 254, 0.1);
    border: 1px solid rgba(0, 242, 254, 0.45);
    padding: 5px 9px;
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

.btn-owned {
    color: #55f5ae;
    background: rgba(85, 245, 174, 0.1);
    border-color: #55f5ae;
}

.btn-owned:hover {
    background: rgba(85, 245, 174, 0.18);
    color: #55f5ae;
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