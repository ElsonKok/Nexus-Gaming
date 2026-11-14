<template>
    <div class="page m-auto">
        <div class="page-heading">
            <span class="eyebrow">SECURE LOADOUT</span>
            <h1>Your Cart</h1>
        </div>
        <div class="cart-layout d-grid">
            <div class="cart-items d-grid gap-5">
                <article class="d-grid align-items-center">
                    <img src="#">
                    <div class="cart-title">
                        <h2>Orbit Zero</h2>
                        <span>PC · Xbox</span>
                        <button>Remove</button>
                    </div>
                    <div class="quantity d-flex align-items-center p-2">
                        <span>1</span>
                    </div>
                    <strong>RM 29.99</strong>
                </article>
            </div>
            <aside class="order-card">
                <h2 class="fs-inherit">Order summary</h2>
                <div>
                    <span>Subtotal</span>
                    <strong>RM 29.99</strong>
                </div>
                <div>
                    <span>Estimated Tax</span>
                    <strong>RM 2.00</strong>
                </div>
                <hr/>
                <div class="total">
                    <span>Total</span>
                    <strong>RM 29.99</strong>
                </div>
                <a href="/checkout">
                    
                </a>
            </aside>
        </div>
    </div>
</template>

<style scope>
.page {
    max-width: 1400px;
    padding: 80px max(4vw, 28px);
}

.page-heading {
    margin-bottom: 42px;
}

.eyebrow {
    color: var(--cyan);
    letter-spacing: .2em;
    font-size: 11px;
    font-weight: 700;
    display: block;
}

.page-heading h1 {
    letter-spacing: -.055em;
    margin: 10px 0;
    font-size: clamp(42px, 6vw, 78px);
}

.cart-layout {
    grid-template-columns: 1fr 360px;
    gap: 35px;
}

.cart-items article {
    border: 1px solid var(--line);
    background: var(--panel);
    grid-template-columns: 120px 1fr auto 90px;
    gap: 20px;
    padding: 15px;
}

.cart-title h2 {
    margin-bottom: 5px;
    font-size: 18px;
}

.cart-title span {
    color: var(--muted);
    font-size: 11px;
}

.cart-title button {
    color: var(--danger);
    background: none;
    border: 0;
    margin-top: 15px;
    padding: 0;
    font-size: 11px;
    display: block;
}

.quantity {
    border: 1px solid var(--line);
}

.order-card {
    background: var(--panel2);
    border: 1px solid var(--line);
    height: max-content;
    padding: 25px;
}

.order-card > div {
    color: var(--muted);
    justify-content: space-between;
    margin: 16px 0;
    font-size: 13px;
    display: flex;
}

.order-card hr {
    border: 0;
    border-top: 2px solid var(--line);
    width: 100%;
}
</style>