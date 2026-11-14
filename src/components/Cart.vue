<script setup>
import { ref, computed } from 'vue';

const cartItems = ref([
    { id: 1, title: 'Orbit Zero', platforms: 'PC · Xbox', price: 29.99, qty: 1, img: '' },
    { id: 2, title: 'Aether Protocol', platforms: 'PC · PlayStation', price: 49.99, qty: 1, img: '' }
]);

const taxRate = 0.06;

const subtotal = computed(() =>
    cartItems.value.reduce((sum, item) => sum + item.price * item.qty, 0)
);

const tax = computed(() => subtotal.value * taxRate);
const total = computed(() => subtotal.value + tax.value);

function updateQty(item, delta) {
    const next = item.qty + delta;
    if (next <= 0) {
        removeItem(item.id);
    } else {
        item.qty = next;
    }
}

function removeItem(id) {
    cartItems.value = cartItems.value.filter((item) => item.id !== id);
}
</script>

<template>
    <div class="page m-auto">
        <div class="page-heading">
            <span class="eyebrow">SECURE LOADOUT</span>
            <h1>Your Cart</h1>
        </div>

        <div v-if="cartItems.length" class="row g-4 align-items-start">
            <div class="col-lg-8 d-grid gap-3">
                <article
                    v-for="item in cartItems"
                    :key="item.id"
                    class="cart-item d-flex align-items-center justify-content-between gap-3 flex-wrap flex-sm-nowrap"
                >
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <img v-if="item.img" :src="item.img" :alt="item.title" class="item-cover" />
                        <div v-else class="item-cover item-fallback">{{ item.title[0] }}</div>

                        <div class="cart-title">
                            <h2>{{ item.title }}</h2>
                            <span>{{ item.platforms }}</span>
                            <button type="button" @click="removeItem(item.id)">Remove</button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-4 ms-auto">
                        <div class="quantity d-flex align-items-center">
                            <button type="button" @click="updateQty(item, -1)">−</button>
                            <span>{{ item.qty }}</span>
                            <button type="button" @click="updateQty(item, 1)">+</button>
                        </div>

                        <strong class="item-price text-end">RM {{ (item.price * item.qty).toFixed(2) }}</strong>
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

                    <router-link to="/checkout" class="btn btn-primary w-100">
                        Proceed to Payment
                    </router-link>

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