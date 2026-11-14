<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const favorites = ref([
    { id: 1, title: 'Neon Veil', meta: 'Action RPG · PC · PS5', price: 29.99, img: '' },
    { id: 2, title: 'Aether Protocol', meta: 'Sci-Fi · PC · PS5', price: 49.99, img: '' }
]);

function removeFavorite(id) {
    favorites.value = favorites.value.filter((item) => item.id !== id);
}

function moveToCart(item) {
    // Add item to your cart store/localStorage here
    removeFavorite(item.id);
    router.push('/cart');
}
</script>

<template>
    <div class="page m-auto">
        <div class="page-heading">
            <span class="eyebrow">YOUR PERSONAL VAULT</span>
            <h1>Favorites</h1>
            <p>{{ favorites.length }} saved {{ favorites.length === 1 ? 'world' : 'worlds' }}, ready when you are.</p>
        </div>

        <div v-if="favorites.length" class="d-grid gap-3">
            <article v-for="item in favorites" :key="item.id" class="fav-card">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-7 d-flex align-items-center gap-3">
                        <img v-if="item.img" :src="item.img" :alt="item.title" class="fav-cover" />
                        <div v-else class="fav-cover fav-fallback">{{ item.title[0] }}</div>

                        <div>
                            <h2 class="mb-1">{{ item.title }}</h2>
                            <p class="mb-0">{{ item.meta }}</p>
                        </div>
                    </div>

                    <div class="col-12 col-md-5 d-flex align-items-center justify-content-between justify-content-md-end gap-3">
                        <strong class="fav-price">RM {{ item.price.toFixed(2) }}</strong>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary btn-sm px-3" @click="moveToCart(item)">
                                Move to cart
                            </button>
                            <button type="button" class="remove" aria-label="Remove" @click="removeFavorite(item.id)">
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