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

                        <!-- Account Role -->
                        <div v-if="mode === 'register'" class="mb-3">
                            <label class="d-block mb-2">
                                Select Account Type
                            </label>

                            <div class="role-options">
                                <label class="role-option">
                                    <input
                                        v-model="role"
                                        type="radio"
                                        value="user"
                                    />

                                    <div>
                                        <strong>Player</strong>
                                        <small>Browse and purchase games.</small>
                                    </div>
                                </label>

                                <label class="role-option">
                                    <input
                                        v-model="role"
                                        type="radio"
                                        value="creator"
                                    />

                                    <div>
                                        <strong>Creator</strong>
                                        <small>Publish and manage your own games.</small>
                                    </div>
                                </label>
                            </div>

                            <small class="role-note">
                                Admin accounts require administrator approval.
                            </small>
                        </div>

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
                                    v-model="confirmPassword"
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

                        <p v-if="errorMessage" class="auth-error">
                            {{ errorMessage }}
                        </p>

                        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                            {{ loading ? 'Please wait...' : (mode === 'login' ? 'Enter the Nexus' : 'Create Account') }}
                        </button>
                    </form>

                    <p class="auth-legal text-center mx-auto mt-4 mb-0">
                        By continuing, you agree to the Nexus Terms and Privacy Policy. This prototype stores the session only in this browser.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import { saveUser } from '../api/auth';

export default {
    name: 'AuthView',
    data() {
        return {
            role: 'user',
            mode: this.$route.query.tab === 'register' ? 'register' : 'login',
            showPassword: false,
            username: '',
            email: '',
            password: '',
            confirmPassword: '',
            remember: false,
            errorMessage: '',
            msg: '',
            loading: false
        };
    },
    watch: {
        '$route.query.tab'(tab) {
            this.mode = tab === 'register' ? 'register' : 'login';
            this.errorMessage = '';
            this.msg = '';
        }
    },
    methods: {
        async hashPassword(plainPassword) {
            const encoder = new TextEncoder();
            const data = encoder.encode(plainPassword);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
        },

        async handleSubmit() {
            this.errorMessage = '';
            this.msg = '';

            const normalizedEmail = this.email.trim().toLowerCase();
            const cleanUsername = this.username.trim();

            if (!normalizedEmail) {
                this.errorMessage = 'Please enter a valid email address.';
                return;
            }

            if (this.mode === 'register') {
                if (!cleanUsername) {
                    this.errorMessage = 'Please enter a display name.';
                    return;
                }
                if (this.password !== this.confirmPassword) {
                    this.errorMessage = 'Passwords do not match.';
                    return;
                }
                const encodedPassword = await this.hashPassword(this.password);
                this.registerAccount(cleanUsername, normalizedEmail, encodedPassword, this.role);
            } else {
                const encodedPassword = await this.hashPassword(this.password);
                this.loginAccount(normalizedEmail, encodedPassword);
            }
        },

        registerAccount(uname, mail, encodedPwd, selectedRole) {
            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            const isDeveloper = selectedRole === 'creator';
            const targetTable = isDeveloper ? 'developer' : 'user';
            const self = this;
            this.loading = true;

            // Check BOTH `user` and `developer` tables so the email cannot exist in either
            Promise.all([
                fetch(`${baseApi}/user`).then((r) => r.json()),
                fetch(`${baseApi}/developer`).then((r) => r.json())
            ])
                .then(([allUsers, allDevs]) => {
                    const userExists =
                        Array.isArray(allUsers) &&
                        allUsers.some((u) => String(u.email || '').trim().toLowerCase() === mail);

                    const devExists =
                        Array.isArray(allDevs) &&
                        allDevs.some((d) => String(d.email || '').trim().toLowerCase() === mail);

                    if (userExists || devExists) {
                        self.errorMessage = 'This email is already registered. Please use a different email or log in.';
                        self.loading = false;
                        return null;
                    }

                    const payload = isDeveloper
                        ? {
                              username: uname,
                              email: mail,
                              passwd: encodedPwd
                          }
                        : {
                              username: uname,
                              email: mail,
                              passwd: encodedPwd,
                              avatar: ''
                          };

                    return fetch(`${baseApi}/${targetTable}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then((res) => {
                            if (!res.ok && res.status !== 409) {
                                throw new Error(`HTTP error: ${res.status}`);
                            }
                            return res.json();
                        })
                        .then((data) => {
                            if (data.error) {
                                self.errorMessage = data.error;
                                return;
                            }

                            const generatedId = isDeveloper ? data.developer_id : data.user_id;
                            self.msg = 'Account created successfully.';

                            const { passwd, avatar, ...userSession } = payload;
                            userSession.id = generatedId;
                            if (isDeveloper) {
                                userSession.developer_id = generatedId;
                                userSession.role =
                                    String(generatedId).toUpperCase() === 'D0000' ? 'admin' : 'creator';
                            } else {
                                userSession.user_id = generatedId;
                                userSession.role = 'user';
                            }

                            saveUser(userSession);
                            const firstName = (userSession.username || 'Player').split(' ')[0];
                            window.dispatchEvent(
                                new CustomEvent('show-toast', {
                                    detail: { message: `Welcome to Nexus, ${firstName}`, type: 'success' }
                                })
                            );
                            self.$router.push('/profile');
                        });
                })
                .catch((error) => {
                    self.errorMessage = 'There was an error! ' + error.message;
                })
                .finally(() => {
                    self.loading = false;
                });
        },

        loginAccount(mail, encodedPwd) {
            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            const self = this;
            this.loading = true;

            Promise.all([
                fetch(`${baseApi}/user`).then((r) => r.json()),
                fetch(`${baseApi}/developer`).then((r) => r.json())
            ])
                .then(([users, devs]) => {
                    const matchedUser = Array.isArray(users)
                        ? users.find(
                              (u) =>
                                  String(u.email || '').trim().toLowerCase() === mail &&
                                  u.passwd === encodedPwd
                          )
                        : null;

                    const matchedDev = Array.isArray(devs)
                        ? devs.find(
                              (d) =>
                                  String(d.email || '').trim().toLowerCase() === mail &&
                                  d.passwd === encodedPwd
                          )
                        : null;

                    if (matchedUser) {
                        const { passwd, avatar, ...userSession } = matchedUser;
                        userSession.id = matchedUser.user_id;
                        userSession.role = 'user';
                        saveUser(userSession);
                        const firstName = (userSession.username || 'Player').split(' ')[0];
                        window.dispatchEvent(
                            new CustomEvent('show-toast', {
                                detail: { message: `Welcome back, ${firstName}`, type: 'success' }
                            })
                        );
                        self.$router.push('/profile');
                    } else if (matchedDev) {
                        const { passwd, ...devSession } = matchedDev;
                        devSession.id = matchedDev.developer_id;
                        devSession.role =
                            String(matchedDev.developer_id).toUpperCase() === 'D0000'
                                ? 'admin'
                                : 'creator';
                        saveUser(devSession);
                        self.$router.push('/profile');
                    } else {
                        self.errorMessage = 'Invalid email or password.';
                    }
                })
                .catch((error) => {
                    self.errorMessage = 'There was an error! ' + error.message;
                })
                .finally(() => {
                    self.loading = false;
                });
        }
    }
};
</script>

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

.auth-error {
    color: #ff6969;
    font-size: 12px;
    margin-bottom: 15px;
}

.role-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.role-option {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 15px;
    background: #0c0e13;
    border: 1px solid var(--line);
    cursor: pointer;
    transition: 0.2s;
}

.role-option:has(input:checked) {
    border-color: var(--cyan);
    background: #00f2fe0a;
}

.role-option input {
    margin-top: 4px;
    accent-color: var(--cyan);
}

.role-option strong {
    display: block;
    color: var(--text);
    font-size: 12px;
}

.role-option small {
    display: block;
    color: var(--muted);
    font-size: 10px;
    margin-top: 5px;
}

.role-note {
    display: block;
    color: var(--muted);
    font-size: 10px;
    margin-top: 10px;
}

@media (max-width: 450px) {
    .role-options {
        grid-template-columns: 1fr;
    }
}
</style>