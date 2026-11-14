<script>
export default {
    name: 'CartView',
    data() {
        return {
            currentUser: null,
            cartItems: [],
            taxRate: 0.06,
            loading: false,
            err: ''
        };
    },
    computed: {
        userId() {
            return String(
                this.currentUser?.user_id || this.currentUser?.id || ''
            ).trim();
        },
        subtotal() {
            return this.cartItems.reduce(
                (sum, item) => sum + Number(item.price) * Number(item.quantity),
                0
            );
        },
        tax() {
            return this.subtotal * this.taxRate;
        },
        total() {
            return this.subtotal + this.tax;
        }
    },
    mounted() {
        this.loadUser();
        if (this.userId) {
            this.fetchCart();
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
        fetchCart() {
            const baseApi = `/resources/apis.php`;
            this.loading = true;
            this.err = '';

            Promise.all([
                fetch(`${baseApi}/cart/user_id/${encodeURIComponent(this.userId)}`).then((r) => r.json()),
                fetch(`${baseApi}/game`).then((r) => r.json())
            ])
                .then(([cartRows, gameRows]) => {
                    const carts = Array.isArray(cartRows) ? cartRows : [];
                    const games = Array.isArray(gameRows) ? gameRows : [];

                    const gameMap = new Map(
                        games.map((g) => [String(g.game_id), g])
                    );

                    const mergedMap = new Map();

                    carts.forEach((row) => {
                        const gid = String(row.game_id);
                        const game = gameMap.get(gid) || {};
                        const rowQty = Number(row.quantity) || 1;

                        if (mergedMap.has(gid)) {
                            const existing = mergedMap.get(gid);
                            existing.quantity += rowQty;
                            existing.extraCartIds.push(row.cart_id);
                        } else {
                            mergedMap.set(gid, {
                                cart_id: row.cart_id,
                                extraCartIds: [],
                                user_id: row.user_id,
                                game_id: gid,
                                title: game.title || `Game (${gid})`,
                                platforms: game.platforms || game.platform || game.genre || 'PC',
                                price: Number(game.price) || 0,
                                quantity: rowQty,
                                img: game.image || ''
                            });
                        }
                    });

                    this.cartItems = Array.from(mergedMap.values());
                })
                .catch((error) => {
                    this.err = error.message;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        updateQty(item, delta) {
            const next = item.quantity + delta;
            if (next <= 0) {
                this.removeItem(item);
                return;
            }

            const prevQty = item.quantity;
            item.quantity = next;

            const baseApi = `/resources/apis.php`;
            fetch(`${baseApi}/cart/cart_id/${encodeURIComponent(item.cart_id)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quantity: next })
            })
                .then((res) => {
                    if (!res.ok) throw new Error('Failed to update quantity');
                })
                .catch(() => {
                    item.quantity = prevQty;
                });
        },
        removeItem(item) {
            const baseApi = `/resources/apis.php`;
            const idsToDelete = [item.cart_id, ...(item.extraCartIds || [])].filter(Boolean);

            this.cartItems = this.cartItems.filter((i) => i.cart_id !== item.cart_id);

            Promise.all(
                idsToDelete.map((cid) =>
                    fetch(`${baseApi}/cart/cart_id/${encodeURIComponent(cid)}`, {
                        method: 'DELETE'
                    })
                )
            )
                .then(() => {
                    window.dispatchEvent(new Event('cart-changed'));
                })
                .catch(() => {
                    this.fetchCart();
                });
        },
        proceedToCheckout() {
            sessionStorage.setItem(
                'checkoutData',
                JSON.stringify({
                    items: this.cartItems,
                    subtotal: this.subtotal,
                    tax: this.tax,
                    total: this.total
                })
            );
            this.$router.push('/checkout');
        }
    }
};
</script>

<template>
    <div class="page m-auto">
        <div class="page-heading">
            <span class="eyebrow">SECURE LOADOUT</span>
            <h1>Your Cart</h1>
        </div>

        <div v-if="!userId" class="empty-cart text-center">
            <p>Please log in to view your cart.</p>
            <router-link to="/login" class="btn btn-primary">Log In</router-link>
        </div>

        <div v-else-if="loading" class="empty-cart text-center">
            <p class="mb-0">Loading your loadout...</p>
        </div>

        <div v-else-if="err" class="empty-cart text-center">
            <p class="text-danger mb-0">Error loading cart: {{ err }}</p>
        </div>

        <div v-else-if="cartItems.length" class="row g-4 align-items-start">
            <div class="col-lg-8 d-grid gap-3">
                <article
                    v-for="item in cartItems"
                    :key="item.cart_id"
                    class="cart-item d-flex align-items-center justify-content-between gap-3 flex-wrap flex-sm-nowrap"
                >
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <img v-if="item.img" :src="item.img" :alt="item.title" class="item-cover" />
                        <div v-else class="item-cover item-fallback">{{ item.title[0] }}</div>

                        <div class="cart-title">
                            <h2>{{ item.title }}</h2>
                            <span>{{ item.platforms }}</span>
                            <button type="button" @click="removeItem(item)">Remove</button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-4 ms-auto">
                        <div class="quantity d-flex align-items-center">
                            <button type="button" @click="updateQty(item, -1)">−</button>
                            <span>{{ item.quantity }}</span>
                            <button type="button" @click="updateQty(item, 1)">+</button>
                        </div>

                        <strong class="item-price text-end">RM {{ (item.price * item.quantity).toFixed(2) }}</strong>
                    </div>
                </article>
            </div>

            <aside class="col-lg-4">
                <div class="order-card">
                    <h2>Order summary</h2>

                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <strong class="text-white">RM {{ subtotal.toFixed(2) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Estimated Tax (6%)</span>
                        <strong class="text-white">RM {{ tax.toFixed(2) }}</strong>
                    </div>

                    <hr />

                    <div class="total d-flex justify-content-between align-items-center">
                        <span>Total</span>
                        <strong>RM {{ total.toFixed(2) }}</strong>
                    </div>

                    <button type="button" class="btn btn-primary w-100" @click="proceedToCheckout">
                        Proceed to Payment
                    </button>

                    <p class="text-center mb-0">Encrypted and secure checkout</p>
                </div>
            </aside>
        </div>

        <div v-else class="empty-cart text-center">
            <p>Your loadout is currently empty.</p>
            <router-link to="/game" class="btn btn-primary">Browse Games</router-link>
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

.cart-item {
    border: 1px solid var(--line);
    background: var(--panel);
    padding: 16px;
    transition: border-color 0.2s;
}

.cart-item:hover {
    border-color: rgba(0, 242, 254, 0.35);
}

.item-cover {
    width: 90px;
    height: 110px;
    object-fit: cover;
    border: 1px solid var(--line);
    flex-shrink: 0;
}

.item-fallback {
    display: grid;
    place-items: center;
    font: 700 28px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: radial-gradient(circle, rgba(0, 242, 254, 0.08), transparent 70%);
}

.cart-title h2 {
    margin-bottom: 4px;
    font-size: 18px;
}

.cart-title span {
    color: var(--muted);
    font-size: 12px;
}

.cart-title button {
    color: var(--danger);
    background: none;
    border: 0;
    margin-top: 14px;
    padding: 0;
    font-size: 11px;
    display: block;
    cursor: pointer;
}

.cart-title button:hover {
    text-decoration: underline;
}

.quantity {
    border: 1px solid var(--line);
    background: #0c0e13;
}

.quantity button {
    background: none;
    border: 0;
    color: var(--muted);
    width: 30px;
    height: 32px;
    cursor: pointer;
}

.quantity button:hover {
    color: var(--cyan);
}

.quantity span {
    min-width: 28px;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
}

.item-price {
    min-width: 95px;
    font-size: 15px;
}

.order-card {
    background: var(--panel2);
    border: 1px solid var(--line);
    padding: 25px;
}

.order-card h2 {
    font-size: 18px;
    margin-bottom: 20px;
}

.order-card > div {
    color: var(--muted);
    margin: 14px 0;
    font-size: 13px;
}

.order-card hr {
    border: 0;
    border-top: 1px solid var(--line);
    margin: 20px 0;
}

.order-card .total {
    color: #fff;
    font-size: 15px;
    margin-bottom: 22px;
}

.order-card .total strong {
    color: var(--cyan);
    font: 700 22px 'Space Grotesk', sans-serif;
}

.btn {
    border-radius: 0;
    min-height: 44px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

.order-card > p {
    color: var(--muted);
    margin-top: 16px;
    font-size: 11px;
}

.empty-cart {
    border: 1px solid var(--line);
    background: var(--panel);
    padding: 70px 24px;
}

.empty-cart p {
    color: var(--muted);
    margin-bottom: 20px;
}
</style>