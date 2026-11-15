<script>
export default {
    name: 'App',
    data() {
        return {
            currentUser: null,
            cartCount: 0,
            toast: {
                visible: false,
                message: '',
                type: 'success' // 'success' | 'info' | 'error'
            },
            toastTimer: null
        };
    },
    computed: {
        userInitials() {
            const name =
                this.currentUser?.username ||
                this.currentUser?.email ||
                'P';

            return name
                .split(' ')
                .map((w) => w[0])
                .join('')
                .slice(0, 2)
                .toUpperCase();
        }
    },
    watch: {
        '$route.fullPath'() {
            this.handleAuthChange();
        }
    },
    mounted() {
        this.handleAuthChange();
        window.addEventListener('auth-changed', this.handleAuthChange);
        window.addEventListener('storage', this.handleAuthChange);
        window.addEventListener('cart-changed', this.fetchCartCount);
        window.addEventListener('show-toast', this.handleToastEvent);
    },
    beforeUnmount() {
        window.removeEventListener('auth-changed', this.handleAuthChange);
        window.removeEventListener('storage', this.handleAuthChange);
        window.removeEventListener('cart-changed', this.fetchCartCount);
        window.removeEventListener('show-toast', this.handleToastEvent);
        if (this.toastTimer) clearTimeout(this.toastTimer);
    },
    methods: {
        syncAuth() {
            try {
                this.currentUser =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user'));
            } catch {
                this.currentUser = null;
            }
        },
        fetchCartCount() {
            const uid = this.currentUser?.user_id || this.currentUser?.id;
            if (!uid || this.currentUser?.role === 'creator') {
                this.cartCount = 0;
                return;
            }

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            fetch(`${baseApi}/cart/user_id/${encodeURIComponent(uid)}`)
                .then((res) => res.json())
                .then((data) => {
                    this.cartCount = Array.isArray(data) ? data.length : 0;
                })
                .catch(() => {
                    this.cartCount = 0;
                });
        },
        handleAuthChange() {
            this.syncAuth();
            this.fetchCartCount();
        },
        handleToastEvent(event) {
            const detail = event.detail || {};
            const message = typeof detail === 'string' ? detail : detail.message;
            if (!message) return;

            this.showToast(message, detail.type || 'success', detail.duration || 3500);
        },
        showToast(message, type = 'success', duration = 3500) {
            if (this.toastTimer) clearTimeout(this.toastTimer);

            this.toast = {
                visible: true,
                message,
                type
            };

            this.toastTimer = setTimeout(() => {
                this.toast.visible = false;
            }, duration);
        },
        handleLogout() {
            localStorage.removeItem('nexusUser');
            localStorage.removeItem('user');
            this.currentUser = null;
            this.cartCount = 0;
            window.dispatchEvent(new Event('auth-changed'));
            this.showToast('Signed out of Nexus', 'info');
            this.$router.push('/');
        }
    }
};
</script>

<template>
    <nav v-if="!$route.meta?.hideNav" class="navbar cus-navbar sticky-top">
        <!-- Brand -->
        <router-link to="/" class="brand-link d-flex align-items-center">
            <img
                src="/logo.png"
                alt="Nexus"
                class="logo"
                width="120"
                height="50"
            />
        </router-link>

        <!-- Primary Links -->
        <div class="nav-center d-flex align-items-center gap-4">
            <router-link to="/" class="nav-link nav-links" exact-active-class="active">
                Home
            </router-link>

            <router-link to="/game" class="nav-link nav-links" active-class="active">
                Games
            </router-link>

            <router-link to="/favourite" class="nav-link nav-links" active-class="active">
                Favourites
            </router-link>

            <router-link to="/about" class="nav-link nav-links" active-class="active">
                About
            </router-link>
        </div>

        <!-- Guest Actions -->
        <div v-if="!currentUser" class="d-flex align-items-center gap-3">
            <router-link
                to="/login"
                class="text-decoration-none login-btn"
            >
                Log in
            </router-link>

            <router-link
                :to="{ path: '/login', query: { tab: 'register' } }"
                class="text-decoration-none cta-btn"
            >
                Join Nexus
            </router-link>
        </div>

        <!-- Authenticated Actions -->
        <div v-else class="d-flex align-items-center gap-3">
            <!-- Creator Quick Action -->
            <router-link
                v-if="currentUser.role === 'creator'"
                to="/games/add"
                class="text-decoration-none creator-btn"
            >
                + Publish Game
            </router-link>

            <!-- Admin Quick Action -->
            <router-link
                v-if="currentUser.role === 'admin'"
                to="/admin"
                class="text-decoration-none cta-btn"
            >
                Admin Panel
            </router-link>

            <!-- Cart Icon (Players) -->
            <router-link
                v-if="currentUser.role !== 'creator'"
                to="/cart"
                class="icon-link text-decoration-none"
                title="Shopping Cart"
            >
                <img
                    src="/cart.svg"
                    alt="Cart"
                    width="18"
                    height="18"
                />
                <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
            </router-link>

            <!-- Profile Pill -->
            <router-link
                to="/profile"
                class="profile-pill text-decoration-none"
            >
                <span class="pill-avatar">{{ userInitials }}</span>
                <span class="pill-name">{{ currentUser.username || currentUser.email }}</span>
            </router-link>

            <!-- Logout -->
            <button
                type="button"
                class="logout-btn"
                @click="handleLogout"
            >
                Logout
            </button>
        </div>
    </nav>

    <router-view></router-view>

    <!-- Global Nexus Toast -->
    <Transition name="nexus-toast">
        <div
            v-if="toast.visible"
            class="nexus-toast"
            :class="`toast-${toast.type}`"
            role="status"
            @click="toast.visible = false"
        >
            <span class="toast-dot"></span>
            <span class="toast-text">{{ toast.message }}</span>
        </div>
    </Transition>
</template>

<style scoped>
.cus-navbar {
    background: rgba(8, 10, 15, 0.85);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 12px max(4vw, 28px);
    z-index: 1000;
}

.brand-link {
    transition: opacity 0.2s;
}

.brand-link:hover {
    opacity: 0.88;
}

.nav-link.nav-links {
    position: relative;
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.04em;
    padding: 8px 2px;
    transition: color 0.2s;
}

.nav-link.nav-links::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: var(--cyan);
    transform: scaleX(0);
    transform-origin: center;
    transition: transform 0.22s ease;
    box-shadow: 0 0 10px var(--cyan);
}

.nav-link.nav-links:hover,
.nav-link.nav-links.active {
    color: #fff;
}

.nav-link.nav-links.active::after,
.nav-link.nav-links:hover::after {
    transform: scaleX(1);
}

.login-btn {
    color: #d5d8e0;
    font-size: 13px;
    font-weight: 600;
    padding: 9px 14px;
    transition: color 0.2s;
}

.login-btn:hover {
    color: var(--cyan);
}

.cta-btn {
    background: var(--cyan);
    color: #061014;
    padding: 10px 18px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    box-shadow: 0 0 20px rgba(0, 242, 254, 0.28);
    transition: filter 0.2s, transform 0.15s;
}

.cta-btn:hover {
    color: #061014;
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.creator-btn {
    background: rgba(138, 108, 255, 0.14);
    border: 1px solid #a58fff;
    color: #cbbdff;
    padding: 9px 16px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    transition: all 0.2s;
}

.creator-btn:hover {
    background: #a58fff;
    color: #08090d;
}

.icon-link {
    position: relative;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid #252936;
    background: #101218;
    transition: border-color 0.2s;
}

.icon-link:hover {
    border-color: var(--cyan);
}

.cart-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: grid;
    place-items: center;
    background: var(--cyan);
    color: #061014;
    font-size: 10px;
    font-weight: 800;
    border-radius: 999px;
    box-shadow: 0 0 10px rgba(0, 242, 254, 0.6);
}

.profile-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 5px 14px 5px 6px;
    background: #101218;
    border: 1px solid #252936;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: border-color 0.2s;
}

.profile-pill:hover {
    border-color: var(--cyan);
    color: var(--cyan);
}

.pill-avatar {
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: linear-gradient(135deg, #00f2fe33, #8a6cff44);
    border: 1px solid var(--cyan);
    color: var(--cyan);
    font-size: 10px;
    font-weight: 800;
}

.logout-btn {
    background: transparent;
    border: 1px solid #252936;
    color: var(--muted);
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
}

.logout-btn:hover {
    color: #ff6969;
    border-color: #ff6969;
}

/* Global Bottom-Right Toast */
.nexus-toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 2000;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 15px 22px;
    background: #11141c;
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f4f6fb;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.01em;
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.65);
    cursor: pointer;
}

.toast-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    background: #55f5ae;
    box-shadow: 0 0 10px #55f5ae;
}

.toast-info .toast-dot {
    background: var(--cyan);
    box-shadow: 0 0 10px var(--cyan);
}

.toast-error .toast-dot {
    background: #ff6969;
    box-shadow: 0 0 10px #ff6969;
}

.nexus-toast-enter-active,
.nexus-toast-leave-active {
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.nexus-toast-enter-from,
.nexus-toast-leave-to {
    opacity: 0;
    transform: translateY(14px);
}

@media (max-width: 860px) {
    .nav-center {
        order: 3;
        width: 100%;
        justify-content: center;
        padding-top: 10px;
        margin-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }
}

@media (max-width: 480px) {
    .pill-name {
        display: none;
    }

    .nexus-toast {
        right: 16px;
        left: 16px;
        bottom: 16px;
    }
}
</style>