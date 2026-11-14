<script setup>
import { ref, computed } from 'vue';

const activeStep = ref(1);
const isRedirecting = ref(false);

const billing = ref({
    firstName: '',
    lastName: '',
    email: '',
    country: 'malaysia'
});

const card = ref({
    number: '',
    expiry: '',
    cvc: ''
});

const items = [
    { id: 1, title: 'Orbit Zero', platforms: 'PC · Xbox', price: 29.99, qty: 1 },
    { id: 2, title: 'Aether Protocol', platforms: 'PC · PlayStation', price: 49.99, qty: 1 }
];

const subtotal = computed(() => items.reduce((sum, item) => sum + item.price * item.qty, 0));
const tax = computed(() => subtotal.value * 0.06);
const total = computed(() => subtotal.value + tax.value);

function nextStep() {
    activeStep.value = 2;
}

async function submitOrder() {
    isRedirecting.value = true;

    try {
        // Replace with your payment gateway endpoint
        const res = await fetch('/api/payment/create-session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                billing: billing.value,
                card: card.value,
                items,
                total: Number(total.value.toFixed(2))
            })
        });

        const data = await res.json();

        if (data.redirectUrl) {
            window.location.href = data.redirectUrl;
        }
    } catch (err) {
        console.error('Payment redirect failed:', err);
        isRedirecting.value = false;
    }
}
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

                    <!-- Step 2: Card Payment -->
                    <section :class="{ open: activeStep === 2 }">
                        <button type="button" @click="activeStep = 2">
                            <span>2</span>
                            <div class="d-grid gap-1">
                                <strong>Card Payment</strong>
                                <small>Visa & Mastercard accepted.</small>
                            </div>
                            <span class="toggle-icon border-0">{{ activeStep === 2 ? '−' : '+' }}</span>
                        </button>

                        <form v-show="activeStep === 2" class="accordion-content" @submit.prevent="submitOrder">

                            <button type="submit" class="btn btn-primary w-100 mt-3" :disabled="isRedirecting">
                                {{ isRedirecting ? 'Redirecting to Payment...' : `Pay RM ${total.toFixed(2)}` }}
                            </button>
                        </form>
                    </section>
                </div>
            </div>

            <!-- Order Summary -->
            <aside class="col-lg-4">
                <div class="order-card">
                    <h2>Order summary</h2>

                    <div v-for="item in items" :key="item.id" class="summary-item d-flex justify-content-between">
                        <div>
                            <strong class="text-white d-block">{{ item.title }}</strong>
                            <small>{{ item.platforms }} × {{ item.qty }}</small>
                        </div>
                        <span class="text-white">RM {{ (item.price * item.qty).toFixed(2) }}</span>
                    </div>

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