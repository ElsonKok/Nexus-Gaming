<script setup>
import { ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const mode = ref(route.query.tab === 'register' ? 'register' : 'login');

watch(
    () => route.query.tab,
    (tab) => {
        mode.value = tab === 'register' ? 'register' : 'login';
    }
);

const showPassword = ref(false);
const username = ref('');
const email = ref('');
const password = ref('');
const remember = ref(false);

function handleSubmit() {
    // Handle login or registration logic here
    console.log(mode.value, { username: username.value, email: email.value, remember: remember.value });
    router.push('/');
}
</script>

<template>
    <div class="auth-page container-fluid p-0">
        <div class="row g-0 min-vh-100">
            <section class="col-lg-6 auth-art d-flex flex-column justify-content-between">
                <router-link to="/">
                    <img src="/logo.png" alt="Nexus" width="120" height="50" />
                </router-link>

                <div class="my-5">
                    <span class="badge">PLAYER NETWORK</span>
                    <h1>YOUR NEXT WORLD<br><em>IS WAITING.</em></h1>
                    <p>One account unlocks your collection, favorites, achievements, and the entire Nexus community.</p>
                    <div class="auth-benefits d-flex flex-wrap gap-3 mt-4">
                        <span>Secure player identity</span>
                        <span>Personalized discovery</span>
                    </div>
                </div>

                <small>Curated worlds. Built for players.</small>
            </section>

            <section class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                <div class="auth-card position-relative w-100">
                    <router-link class="auth-close text-decoration-none" to="/">×</router-link>

                    <span class="eyebrow">{{ mode === 'login' ? 'WELCOME BACK' : 'JOIN THE NETWORK' }}</span>
                    <h2>{{ mode === 'login' ? 'Login to Nexus' : 'Create your player ID' }}</h2>
                    <p>{{ mode === 'login' ? 'Enter your credentials to continue your journey.' : 'Build your collection and never lose your signal.' }}</p>

                    <div class="auth-tab row g-0">
                        <button
                            type="button"
                            class="col-6"
                            :class="{ active: mode === 'login' }"
                            @click="mode = 'login'"
                        >
                            Log in
                        </button>
                        <button
                            type="button"
                            class="col-6"
                            :class="{ active: mode === 'register' }"
                            @click="mode = 'register'"
                        >
                            Register
                        </button>
                    </div>

                    <form @submit.prevent="handleSubmit">
                        <label v-if="mode === 'register'" class="d-grid gap-2 mb-3">
                            Display name
                            <input v-model="username" type="text" placeholder="How players will see you" required />
                        </label>

                        <label class="d-grid gap-2 mb-3">
                            Email
                            <input v-model="email" type="email" placeholder="player@nexus.gg" required />
                        </label>

                        <label class="d-grid gap-2 mb-3">
                            Password
                            <div class="password-field d-flex align-items-center">
                                <img src="/lock.svg" alt="" width="17" height="17" />
                                <input
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="At least 6 characters"
                                    minlength="6"
                                    required
                                />
                                <button type="button" @click="showPassword = !showPassword">
                                    <img src="/eye.svg" alt="Toggle password" width="15" height="15" />
                                </button>
                            </div>
                        </label>

                        <label v-if="mode === 'register'" class="d-grid gap-2 mb-3">
                            Confirm Password
                            <div class="password-field d-flex align-items-center">
                                <img src="/lock.svg" alt="" width="17" height="17" />
                                <input
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="At least 6 characters"
                                    minlength="6"
                                    required
                                />
                                <button type="button" @click="showPassword = !showPassword">
                                    <img src="/eye.svg" alt="Toggle password" width="15" height="15" />
                                </button>
                            </div>
                        </label>

                        <div v-if="mode === 'login'" class="auth-options d-flex justify-content-between align-items-center mb-4">
                            <label class="d-flex align-items-center gap-2 m-0">
                                <input v-model="remember" type="checkbox" />
                                Remember me
                            </label>
                            <button type="button">Forgot Password?</button>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ mode === 'login' ? 'Enter the Nexus' : 'Create Account' }}
                        </button>
                    </form>

                    <div class="auth-divider d-flex align-items-center gap-3 my-4">
                        <span>or continue with</span>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-secondary w-100">G &nbsp;Google</button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-secondary w-100">◈ &nbsp;Discord</button>
                        </div>
                    </div>

                    <p class="auth-legal text-center mx-auto mt-4 mb-0">
                        By continuing, you agree to the Nexus Terms and Privacy Policy. This prototype stores the session only in this browser.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    background: var(--bg);
}

.auth-art {
    background: linear-gradient(90deg, #08090ddb, #08090d59), url(https://images.unsplash.com/photo-1672872476232-da16b45c9001?auto=format&fit=crop&w=1400&q=85) center / cover;
    padding: 42px 6vw;
}

.auth-art > div {
    max-width: 650px;
}

.badge,
.eyebrow {
    letter-spacing: 0.15em;
    color: var(--cyan);
    font-size: 10px;
    font-weight: 700;
}

.badge {
    background: #00f2fe12;
    border: 1px solid currentColor;
    padding: 6px 9px;
    line-height: 1;
    border-radius: 0;
}

.auth-art h1 {
    letter-spacing: -0.07em;
    margin: 22px 0;
    font-size: clamp(46px, 5.5vw, 88px);
    line-height: 0.9;
}

.auth-art h1 em {
    color: transparent;
    -webkit-text-stroke: 1px var(--cyan);
    font-style: normal;
}

.auth-art p {
    color: #c3c7d0;
    max-width: 560px;
    font-size: 17px;
    line-height: 1.7;
}

.auth-benefits span {
    color: #dce0e8;
    font-size: 11px;
}

.auth-art > small {
    color: #aeb3be;
}

.auth-card {
    max-width: 480px;
}

.auth-close {
    border: 1px solid var(--line);
    width: 38px;
    height: 38px;
    color: var(--muted);
    display: grid;
    place-items: center;
    font-size: 22px;
    position: absolute;
    top: -6px;
    right: 0;
}

.auth-close:hover {
    color: var(--cyan);
    border-color: var(--cyan);
}

.auth-card h2 {
    letter-spacing: -0.04em;
    margin: 8px 0;
    font-size: 38px;
}

.auth-card > p {
    color: var(--muted);
    font-size: 13px;
}

.auth-tab {
    border-bottom: 1px solid var(--line);
    margin: 28px 0 24px;
}

.auth-tab button {
    color: var(--muted);
    background: none;
    border: 0;
    border-bottom: 2px solid transparent;
    padding: 13px;
    font-size: 12px;
    font-weight: 700;
}

.auth-tab button.active {
    border-color: var(--cyan);
    color: var(--cyan);
}

form label {
    color: #d5d8e0;
    font-size: 12px;
}

.auth-card input:not([type="checkbox"]) {
    border: 1px solid var(--line);
    color: #fff;
    background: #0c0e13;
    outline: 0;
    width: 100%;
    padding: 13px;
}

.auth-card input:focus,
.password-field:focus-within {
    border-color: var(--cyan);
}

.password-field {
    border: 1px solid var(--line);
    background: #0c0e13;
    padding-left: 12px;
}

.password-field input {
    background: none;
    border: 0 !important;
}

.password-field button {
    width: 42px;
    background: none;
    border: 0;
}

.auth-options label {
    color: var(--muted);
    font-size: 11px;
    cursor: pointer;
}

.auth-options button {
    color: var(--cyan);
    background: none;
    border: 0;
    font-size: 11px;
    padding: 0;
}

.btn {
    border-radius: 0;
    min-height: 44px;
    font-size: 13px;
    font-weight: 700;
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
    background: #ffffff12;
    border-color: #ffffff2e;
}

.auth-divider {
    color: var(--muted);
    font-size: 10px;
}

.auth-divider::before,
.auth-divider::after {
    content: "";
    background: var(--line);
    flex: 1;
    height: 1px;
}

.auth-legal {
    color: var(--muted);
    max-width: 390px;
    line-height: 1.6;
    font-size: 10px;
}

@media (max-width: 991px) {
    .auth-art {
        min-height: auto;
        padding: 36px 24px;
    }
}
</style>