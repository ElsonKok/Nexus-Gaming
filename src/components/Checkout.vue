<script>
export default {
    name: 'CheckoutView',
    data() {
        return {
            currentUser: null,
            activeStep: 1,
            isProcessing: false,
            errorMsg: '',
            successMsg: '',
            billing: {
                firstName: '',
                lastName: '',
                email: '',
                country: 'malaysia'
            },
            items: []
        };
    },
    computed: {
        userId() {
            return String(
                this.currentUser?.user_id || this.currentUser?.id || ''
            ).trim();
        },
        subtotal() {
            return this.items.reduce(
                (sum, item) => sum + Number(item.price) * Number(item.quantity || item.qty || 1),
                0
            );
        },
        tax() {
            return this.subtotal * 0.06;
        },
        total() {
            return this.subtotal + this.tax;
        }
    },
    mounted() {
        this.loadUser();
        this.loadCheckoutItems();

        // Check if returning from Stripe Checkout
        if (this.$route.query.success === 'true') {
            this.finalizeOrderAfterStripe();
        } else if (this.$route.query.canceled === 'true') {
            this.activeStep = 2;
            this.errorMsg = 'Stripe payment was canceled. You can try again below.';
        }
    },
    methods: {
        loadUser() {
            try {
                this.currentUser =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user'));

                if (this.currentUser?.email) {
                    this.billing.email = this.currentUser.email;
                }
            } catch {
                this.currentUser = null;
            }
        },
        loadCheckoutItems() {
            const savedCheckout = sessionStorage.getItem('checkoutData');
            if (savedCheckout) {
                try {
                    const parsed = JSON.parse(savedCheckout);
                    if (Array.isArray(parsed.items) && parsed.items.length > 0) {
                        this.items = parsed.items.map((item) => ({
                            ...item,
                            quantity: Number(item.quantity || item.qty || 1),
                            price: Number(item.price) || 0
                        }));
                        return;
                    }
                } catch {
                    // Fallback to API fetch below
                }
            }

            if (!this.userId) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            Promise.all([
                fetch(`${baseApi}/cart/user_id/${encodeURIComponent(this.userId)}`).then((r) => r.json()),
                fetch(`${baseApi}/game`).then((r) => r.json())
            ])
                .then(([cartRows, gameRows]) => {
                    const carts = Array.isArray(cartRows) ? cartRows : [];
                    const games = Array.isArray(gameRows) ? gameRows : [];
                    const gameMap = new Map(games.map((g) => [String(g.game_id), g]));

                    this.items = carts.map((row) => {
                        const game = gameMap.get(String(row.game_id)) || {};
                        return {
                            cart_id: row.cart_id,
                            game_id: row.game_id,
                            title: game.title || `Game (${row.game_id})`,
                            platforms: game.platform || game.genre || 'PC',
                            price: Number(game.price) || 0,
                            quantity: Number(row.quantity) || 1,
                            img: game.image || ''
                        };
                    });
                })
                .catch((err) => {
                    this.errorMsg = 'Failed to load order items: ' + err.message;
                });
        },
        nextStep() {
            this.errorMsg = '';
            this.activeStep = 2;
        },
        async submitOrder() {
            this.errorMsg = '';
            this.successMsg = '';

            if (!this.userId) {
                this.$router.push('/login');
                return;
            }

            if (!this.items.length) {
                this.errorMsg = 'Your cart is empty.';
                return;
            }

            // Save pending items so they are guaranteed available when Stripe redirects back
            sessionStorage.setItem(
                'checkoutData',
                JSON.stringify({
                    items: this.items,
                    subtotal: this.subtotal,
                    tax: this.tax,
                    total: this.total
                })
            );

            // If all games are free (RM 0.00), skip Stripe and finalize directly
            if (this.total <= 0) {
                this.finalizeOrderAfterStripe();
                return;
            }

            this.isProcessing = true;

            try {
                // Build base app URL supporting both hash (#/checkout) and history (/checkout) router modes
                const appBase = window.location.href.split('/checkout')[0];

                const res = await fetch(`${import.meta.env.BASE_URL}resources/checkout.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: this.userId,
                        email: this.billing.email,
                        billing: this.billing,
                        items: this.items,
                        returnUrl: appBase
                    })
                });
                console.log(appBase);
                console.log(res);

                const data = await res.json();

                if (!res.ok || data.error) {
                    throw new Error(data.error || `HTTP error: ${res.status}`);
                }

                if (data.url) {
                    window.location.href = data.url;
                } else {
                    throw new Error('Stripe checkout URL was not returned.');
                }
            } catch (err) {
                this.errorMsg = 'Payment redirect failed: ' + err.message;
                console.log(this.errorMsg);
                this.isProcessing = false;
            }
        },
        finalizeOrderAfterStripe() {
            if (!this.userId || !this.items.length) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            const today = new Date().toISOString().split('T')[0];
            this.isProcessing = true;
            this.activeStep = 2;
            this.successMsg = 'Payment verified! Unlocking your games...';

            const recordPayments = this.items.reduce((chain, item) => {
                return chain.then(() => {
                    return fetch(`${baseApi}/payment`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: this.userId,
                            game_id: item.game_id,
                            date: today
                        })
                    })
                        .then((res) => {
                            if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                            return res.json();
                        })
                        .then((data) => {
                            if (data.error) throw new Error(data.error);
                        });
                });
            }, Promise.resolve());

            recordPayments
                .then(() => {
                    return fetch(`${baseApi}/cart/user_id/${encodeURIComponent(this.userId)}`, {
                        method: 'DELETE'
                    });
                })
                .then(() => {
                    sessionStorage.removeItem('checkoutData');
                    window.dispatchEvent(new Event('cart-changed'));
                    this.successMsg = 'Payment successful! Redirecting to your library...';
                    setTimeout(() => {
                        this.$router.push('/profile');
                    }, 900);
                })
                .catch((err) => {
                    this.errorMsg = 'Error recording order: ' + err.message;
                })
                .finally(() => {
                    this.isProcessing = false;
                });
        }
    }
};
</script>

<template>
    <div class="page narrow m-auto">
        <div class="checkout-head d-flex justify-content-between align-items-center mb-4">
            <router-link to="/cart" class="text-decoration-none">← Back to Cart</router-link>
            <span>● Secure Checkout</span>
        </div>

        <div class="page-heading">
            <span class="eyebrow">FINAL STEP</span>
            <h1>Complete Your Order</h1>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="accordion">
                    <!-- Step 1: Contact & Billing -->
                    <section :class="{ open: activeStep === 1 }">
                        <button type="button" @click="activeStep = 1">
                            <span>1</span>
                            <div class="d-grid gap-1">
                                <strong>Contact & Billing</strong>
                                <small>Where should we send your receipt?</small>
                            </div>
                            <span class="toggle-icon border-0">{{ activeStep === 1 ? '−' : '+' }}</span>
                        </button>

                        <form v-show="activeStep === 1" class="accordion-content" @submit.prevent="nextStep">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label>
                                        First Name
                                        <input v-model="billing.firstName" type="text" placeholder="Alex" required />
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label>
                                        Last Name
                                        <input v-model="billing.lastName" type="text" placeholder="Morgan" required />
                                    </label>
                                </div>
                            </div>

                            <label>
                                Email Address
                                <input v-model="billing.email" type="email" placeholder="alex@example.com" required />
                            </label>

                            <label>
                                Country
                                <select v-model="billing.country">
                                    <option value="malaysia">Malaysia</option>
                                    <option value="singapore">Singapore</option>
                                    <option value="indonesia">Indonesia</option>
                                    <option value="thailand">Thailand</option>
                                </select>
                            </label>

                            <button type="submit" class="btn btn-primary mt-2">Continue</button>
                        </form>
                    </section>

                    <!-- Step 2: Stripe Hosted Payment -->
                    <section :class="{ open: activeStep === 2 }">
                        <button type="button" @click="activeStep = 2">
                            <span>2</span>
                            <div class="d-grid gap-1">
                                <strong>Stripe Secure Payment</strong>
                                <small>You will be redirected to Stripe to complete your payment.</small>
                            </div>
                            <span class="toggle-icon border-0">{{ activeStep === 2 ? '−' : '+' }}</span>
                        </button>

                        <form v-show="activeStep === 2" class="accordion-content" @submit.prevent="submitOrder">
                            <p class="small text-muted mb-2">
                                Click below to proceed to Stripe's encrypted checkout page. Visa, Mastercard, and FPX/wallets supported.
                            </p>

                            <p v-if="errorMsg" class="text-danger small mt-2 mb-0">{{ errorMsg }}</p>
                            <p v-if="successMsg" class="text-info small mt-2 mb-0">{{ successMsg }}</p>

                            <button type="submit" class="btn btn-primary w-100 mt-3" :disabled="isProcessing || !items.length">
                                {{ isProcessing ? 'Redirecting to Stripe...' : `Pay RM ${total.toFixed(2)}` }}
                            </button>
                        </form>
                    </section>
                </div>
            </div>

            <!-- Order Summary -->
            <aside class="col-lg-4">
                <div class="order-card">
                    <h2>Order summary</h2>

                    <div v-if="items.length">
                        <div v-for="item in items" :key="item.cart_id || item.game_id" class="summary-item d-flex justify-content-between my-3">
                            <div>
                                <strong class="text-white d-block">{{ item.title }}</strong>
                                <small>{{ item.platforms }} × {{ item.quantity }}</small>
                            </div>
                            <span class="text-white">RM {{ (item.price * item.quantity).toFixed(2) }}</span>
                        </div>
                    </div>
                    <p v-else class="small text-muted">No items in checkout.</p>

                    <hr />

                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <strong class="text-white">RM {{ subtotal.toFixed(2) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Estimated Tax (6%)</span>
                        <strong class="text-white">RM {{ tax.toFixed(2) }}</strong>
                    </div>

                    <hr />

                    <div class="total d-flex justify-content-between align-items-center mb-0">
                        <span>Total</span>
                        <strong>RM {{ total.toFixed(2) }}</strong>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.page {
    padding: 80px max(4vw, 28px);
}

.narrow {
    max-width: 1050px;
}

.checkout-head a {
    color: #d5d7df;
    font-size: 12px;
}

.checkout-head a:hover {
    color: var(--cyan);
}

.checkout-head > span {
    color: var(--green);
    font-size: 11px;
    font-weight: 600;
}

.page-heading {
    margin-bottom: 36px;
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
    font-size: clamp(38px, 5.5vw, 68px);
}

.accordion section {
    border: 1px solid var(--line);
    background: var(--panel);
    margin-bottom: 14px;
}

.accordion section.open {
    border-color: rgba(0, 242, 254, 0.35);
}

.accordion section > button {
    text-align: left;
    color: #fff;
    background: none;
    border: 0;
    grid-template-columns: 34px 1fr auto;
    align-items: center;
    gap: 15px;
    width: 100%;
    padding: 20px;
    display: grid;
    cursor: pointer;
}

.accordion section > button > span:first-child {
    border: 1px solid var(--line);
    width: 34px;
    height: 34px;
    color: var(--muted);
    place-items: center;
    display: grid;
    font-weight: 700;
    font-size: 13px;
}

.accordion section.open > button > span:first-child {
    color: var(--cyan);
    border-color: var(--cyan);
}

.toggle-icon {
    color: var(--muted);
    font-size: 18px;
}

.accordion small {
    color: var(--muted);
    font-size: 12px;
}

.accordion-content {
    padding: 4px 24px 24px;
}

.accordion label {
    color: #c9ccd5;
    gap: 8px;
    margin: 14px 0;
    font-size: 12px;
    display: grid;
}

.accordion input,
.accordion select {
    border: 1px solid var(--line);
    color: #fff;
    background: #0c0e13;
    outline: 0;
    width: 100%;
    padding: 12px;
}

.accordion input:focus,
.accordion select:focus {
    border-color: var(--cyan);
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

.summary-item small {
    color: var(--muted);
    font-size: 11px;
}

.order-card hr {
    border: 0;
    border-top: 1px solid var(--line);
    margin: 18px 0;
}

.order-card .total {
    color: #fff;
    font-size: 15px;
}

.order-card .total strong {
    color: var(--cyan);
    font: 700 22px 'Space Grotesk', sans-serif;
}

.btn {
    border-radius: 0;
    min-height: 44px;
    padding: 0 22px;
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

.btn-primary:hover:not(:disabled) {
    background: var(--cyan);
    border-color: var(--cyan);
    color: #061014;
    filter: brightness(1.08);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>