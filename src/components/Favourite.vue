<script>
export default {
    name: 'FavouriteView',
    data() {
        return {
            currentUser: null,
            favorites: [],
            purchasedGameIds: new Set(),
            loading: false,
            err: '',
            movingId: null
        };
    },
    computed: {
        userId() {
            return String(
                this.currentUser?.user_id ||
                this.currentUser?.developer_id ||
                this.currentUser?.id ||
                ''
            ).trim();
        },
        isCreator() {
            const role = String(this.currentUser?.role || '').toLowerCase();
            return role === 'creator' || role === 'developer' || Boolean(this.currentUser?.developer_id);
        }
    },
    mounted() {
        this.loadUser();
        if (this.userId) {
            this.fetchFavourites();
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
        gameLink(item) {
            return `/game/${this.toSlug(item.title)}`;
        },
        openGame(item) {
            if (item.rawGame) {
                sessionStorage.setItem('selectedGame', JSON.stringify(item.rawGame));
            }
        },
        fetchFavourites() {
            const baseApi = `/resources/apis.php`;
            this.loading = true;
            this.err = '';

            Promise.all([
                fetch(`${baseApi}/favourite/user_id/${encodeURIComponent(this.userId)}`).then((r) => r.json()),
                fetch(`${baseApi}/game`).then((r) => r.json()),
                fetch(`${baseApi}/payment/user_id/${encodeURIComponent(this.userId)}`).then((r) => r.json()).catch(() => [])
            ])
                .then(([favRows, gameRows, paymentRows]) => {
                    const favs = Array.isArray(favRows) ? favRows : [];
                    const games = Array.isArray(gameRows) ? gameRows : [];
                    const payments = Array.isArray(paymentRows) ? paymentRows : [];

                    this.purchasedGameIds = new Set(
                        payments.map((p) => String(p.game_id).trim())
                    );

                    const gameMap = new Map(
                        games.map((g) => [String(g.game_id).trim(), g])
                    );

                    this.favorites = favs.map((row) => {
                        const gid = String(row.game_id).trim();
                        const game = gameMap.get(gid) || {};
                        const platforms = String(game.platform || '')
                            .split(',')
                            .map((p) => p.trim())
                            .filter(Boolean)
                            .join(' · ');

                        const metaParts = [game.genre, platforms].filter(Boolean);

                        return {
                            fav_id: row.fav_id,
                            user_id: row.user_id,
                            game_id: gid,
                            title: game.title || `Game (${gid})`,
                            meta: metaParts.length ? metaParts.join(' · ') : 'Nexus Title',
                            price: Number(game.price) || 0,
                            img: game.image || '',
                            isOwned: this.purchasedGameIds.has(gid),
                            rawGame: game.game_id ? game : null
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
        removeFavorite(item, silent = false) {
            const baseApi = `/resources/apis.php`;
            const previous = [...this.favorites];

            this.favorites = this.favorites.filter((f) => f.fav_id !== item.fav_id);

            return fetch(`${baseApi}/favourite/fav_id/${encodeURIComponent(item.fav_id)}`, {
                method: 'DELETE'
            })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    if (!silent) {
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: `Removed ${item.title} from favourites`, type: 'info' }
                            })
                        );
                    }
                })
                .catch((error) => {
                    this.favorites = previous;
                    if (!silent) {
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: 'Failed to remove: ' + error.message, type: 'error' }
                            })
                        );
                    }
                });
        },
        moveToCart(item) {
            if (!this.userId) {
                this.$router.push('/login');
                return;
            }

            if (this.isCreator) {
                window.dispatchEvent(
                    new CustomEvent('show-toast', {
                        detail: { message: 'Creator accounts cannot purchase games', type: 'error' }
                    })
                );
                return;
            }

            if (item.isOwned) {
                this.$router.push('/profile');
                return;
            }

            const baseApi = `/resources/apis.php`;
            this.movingId = item.fav_id;

            fetch(`${baseApi}/cart/user_id/${encodeURIComponent(this.userId)}`)
                .then((r) => r.json())
                .then((existingRows) => {
                    const carts = Array.isArray(existingRows) ? existingRows : [];
                    const existing = carts.find((c) => String(c.game_id).trim() === item.game_id);

                    if (existing && existing.cart_id) {
                        const nextQty = (Number(existing.quantity) || 1) + 1;
                        return fetch(`${baseApi}/cart/cart_id/${encodeURIComponent(existing.cart_id)}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ quantity: nextQty })
                        });
                    }

                    return fetch(`${baseApi}/cart`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: this.userId,
                            game_id: item.game_id,
                            quantity: 1
                        })
                    });
                })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return this.removeFavorite(item, true);
                })
                .then(() => {
                    window.dispatchEvent(new Event('cart-changed'));
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: `${item.title} moved to cart`, type: 'success' }
                        })
                    );
                    this.$router.push('/cart');
                })
                .catch((error) => {
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: 'Could not move to cart: ' + error.message, type: 'error' }
                        })
                    );
                })
                .finally(() => {
                    this.movingId = null;
                });
        }
    }
};
</script>

<template>
    <div class="page m-auto">
        <div class="page-heading">
            <span class="eyebrow">YOUR PERSONAL VAULT</span>
            <h1>Favorites</h1>
            <p v-if="userId">
                {{ favorites.length }} saved {{ favorites.length === 1 ? 'world' : 'worlds' }}, ready when you are.
            </p>
        </div>

        <div v-if="!userId" class="empty-vault text-center">
            <p>Please log in to view your saved favorites.</p>
            <router-link to="/login" class="btn btn-primary">Log In</router-link>
        </div>

        <div v-else-if="loading" class="empty-vault text-center">
            <p class="mb-0">Loading your vault...</p>
        </div>

        <div v-else-if="err" class="empty-vault text-center">
            <p class="text-danger mb-0">Error loading favorites: {{ err }}</p>
        </div>

        <div v-else-if="favorites.length" class="d-grid gap-3">
            <article v-for="item in favorites" :key="item.fav_id" class="fav-card">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-7 d-flex align-items-center gap-3">
                        <img v-if="item.img" :src="item.img" :alt="item.title" class="fav-cover" />
                        <div v-else class="fav-cover fav-fallback">{{ item.title[0] }}</div>

                        <div>
                            <router-link
                                v-if="item.rawGame"
                                :to="gameLink(item)"
                                class="text-white text-decoration-none"
                                @click="openGame(item)"
                            >
                                <h2 class="mb-1">{{ item.title }}</h2>
                            </router-link>
                            <h2 v-else class="mb-1">{{ item.title }}</h2>
                            <p class="mb-0">{{ item.meta }}</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-5 d-flex align-items-center justify-content-between justify-content-md-end gap-3">
                        <strong class="fav-price">
                            {{ item.price === 0 ? 'Free' : `RM ${item.price.toFixed(2)}` }}
                        </strong>

                        <div class="d-flex align-items-center gap-2">
                            <router-link
                                v-if="item.isOwned"
                                to="/profile"
                                class="btn btn-owned btn-sm px-3 text-decoration-none"
                            >
                                In Library
                            </router-link>

                            <button
                                v-else
                                type="button"
                                class="btn btn-primary btn-sm px-3"
                                :disabled="movingId === item.fav_id"
                                @click="moveToCart(item)"
                            >
                                {{ movingId === item.fav_id ? 'Moving...' : 'Move to cart' }}
                            </button>

                            <button
                                type="button"
                                class="remove"
                                aria-label="Remove"
                                @click="removeFavorite(item)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="empty-vault text-center">
            <p>Your vault is empty. Save games while browsing to find them here.</p>
            <router-link to="/game" class="btn btn-primary">Explore Games</router-link>
        </div>
    </div>
</template>

<style scoped>
.page {
    max-width: 1400px;
    padding: 80px max(4vw, 28px);
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
    font-size: 16px;
}

.fav-card {
    background: var(--panel);
    border: 1px solid var(--line);
    padding: 16px 20px;
    transition: border-color 0.2s;
}

.fav-card:hover {
    border-color: rgba(0, 242, 254, 0.35);
}

.fav-cover {
    width: 110px;
    height: 76px;
    object-fit: cover;
    border: 1px solid var(--line);
    flex-shrink: 0;
}

.fav-fallback {
    display: grid;
    place-items: center;
    font: 700 26px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: radial-gradient(circle, rgba(0, 242, 254, 0.08), transparent 70%);
}

.fav-card h2 {
    font-size: 18px;
}

.fav-card h2:hover {
    color: var(--cyan);
}

.fav-card p {
    color: var(--muted);
    font-size: 12px;
}

.fav-price {
    font-size: 15px;
    color: #fff;
}

.btn {
    border-radius: 0;
    min-height: 36px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
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
    border: 1px solid #55f5ae;
}

.remove {
    color: var(--muted);
    background: none;
    border: 0;
    font-size: 20px;
    line-height: 1;
    padding: 6px 10px;
    cursor: pointer;
    transition: color 0.2s;
}

.remove:hover {
    color: var(--danger);
}

.empty-vault {
    border: 1px solid var(--line);
    background: var(--panel);
    padding: 70px 24px;
}

.empty-vault p {
    color: var(--muted);
    margin-bottom: 20px;
}
</style>