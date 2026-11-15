<template>
    <main class="profile-page">

        <!-- Profile Banner -->
        <section class="profile-hero">
            <div class="container profile-container">

                <div class="profile-main">

                    <div class="profile-avatar">
                        <img
                            v-if="formattedAvatar"
                            :src="formattedAvatar"
                            :alt="displayName"
                        />
                        <span v-else>{{ initials }}</span>
                        <span class="online-indicator"></span>
                    </div>

                    <div class="profile-details">

                        <span class="profile-badge" :class="{ 'badge-admin': isAdmin }">
                            NEXUS {{ isAdmin ? 'SYSTEM ADMIN' : isCreator ? 'DEVELOPER' : 'PLAYER' }}
                        </span>

                        <h1>{{ displayName }}</h1>

                        <p>{{ profile.email }}</p>

                        <div class="profile-stats">
                            <span>
                                <strong>
                                    {{ isAdmin ? adminGames.length :
                                       isCreator ? creatorGames.length :
                                       purchasedGames.length }}
                                </strong>
                                {{ isAdmin ? 'Total Games' :
                                   isCreator ? 'Created Games' : 'Games Owned' }}
                            </span>

                            <span v-if="isAdmin">
                                <strong>{{ users.length }}</strong>
                                Registered Users
                            </span>

                            <span v-if="isAdmin">
                                <strong>{{ developers.length }}</strong>
                                Developers
                            </span>

                            <span v-if="!isAdmin">
                                <strong>{{ favouriteGames.length }}</strong>
                                Favourites
                            </span>

                            <span v-if="(isCreator || isAdmin) && creatorId">
                                <strong>ID</strong>
                                {{ creatorId }}
                            </span>
                        </div>

                    </div>

                </div>

                <router-link
                    to="/profile/edit"
                    class="edit-profile-btn"
                >
                    <span>✎</span> Edit Profile
                </router-link>

            </div>
        </section>

        <!-- Dashboard -->
        <section class="profile-content">
            <div class="container">

                <p v-if="loading" class="empty-state">Loading dashboard data...</p>
                <p v-else-if="err" class="empty-state text-danger">Error: {{ err }}</p>

                <!-- Normal User -->
                <template v-else-if="!isCreator && !isAdmin">

                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">YOUR COLLECTION</span>
                            <h2>My Games</h2>
                            <p>All the games you have purchased.</p>
                        </div>

                        <router-link to="/game" class="view-all">
                            Explore Games →
                        </router-link>
                    </div>

                    <div v-if="purchasedGames.length" class="row g-4">
                        <div
                            v-for="game in purchasedGames"
                            :key="game.game_id"
                            class="col-12 col-sm-6 col-lg-3"
                        >
                            <article class="profile-game-card">
                                <img
                                    v-if="game.img"
                                    :src="game.img"
                                    :alt="game.title"
                                />

                                <div v-else class="game-placeholder">
                                    🎮
                                </div>

                                <div class="game-card-info">
                                    <span>{{ game.genre }}</span>
                                    <h3>{{ game.title }}</h3>

                                    <router-link :to="gameLink(game)" @click="openGame(game)">
                                        View Details →
                                    </router-link>
                                </div>
                            </article>
                        </div>
                    </div>

                    <p v-else class="empty-state">
                        You haven't purchased any games yet.
                    </p>

                </template>

                <!-- Developer / Creator -->
                <template v-else-if="isCreator">

                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">DEVELOPER STUDIO</span>
                            <h2>My Created Games</h2>
                            <p>Manage the games you have published on Nexus.</p>
                        </div>

                        <router-link
                            to="/games/add"
                            class="add-game-btn"
                        >
                            + Add New Game
                        </router-link>
                    </div>

                    <div v-if="creatorGames.length" class="table-responsive">
                        <table class="profile-table">
                            <thead>
                                <tr>
                                    <th>Game</th>
                                    <th>Genre</th>
                                    <th>Price</th>
                                    <th>Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="game in creatorGames"
                                    :key="game.game_id"
                                >
                                    <td>
                                        <div class="game-table-info">
                                            <img
                                                v-if="game.img"
                                                :src="game.img"
                                                :alt="game.title"
                                            />
                                            <div v-else class="table-placeholder">
                                                🎮
                                            </div>

                                            <router-link
                                                :to="gameLink(game)"
                                                class="text-white text-decoration-none"
                                                @click="openGame(game)"
                                            >
                                                <strong>{{ game.title }}</strong>
                                            </router-link>
                                        </div>
                                    </td>

                                    <td>{{ game.genre }}</td>
                                    <td>{{ price(game.price) }}</td>
                                    <td>★ {{ game.rating.toFixed(1) }}</td>

                                    <td>
                                        <div class="table-actions">
                                            <router-link
                                                :to="`/games/edit/${game.game_id}`"
                                                class="edit-action"
                                            >
                                                Edit
                                            </router-link>

                                            <button
                                                type="button"
                                                class="delete-action"
                                                @click="deleteGame(game.game_id)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-else class="empty-state">
                        You haven't created any games yet.
                    </p>

                </template>

                <!-- Admin Dashboard (D0000) -->
                <template v-else-if="isAdmin">

                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">COMMAND CENTER</span>
                            <h2>Admin Dashboard</h2>
                            <p>Full marketplace oversight, catalog control, and account directory.</p>
                        </div>

                        <router-link
                            to="/games/add"
                            class="add-game-btn"
                        >
                            + Publish New Game
                        </router-link>
                    </div>

                    <!-- Live KPI Cards -->
                    <div class="row g-3 mb-5">

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="admin-stat-card">
                                <div class="stat-top">
                                    <span>TOTAL GAMES</span>
                                    <span class="stat-icon">🕹️</span>
                                </div>
                                <h3>{{ adminGames.length }}</h3>
                                <small>Active catalog listings</small>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="admin-stat-card">
                                <div class="stat-top">
                                    <span>REGISTERED USERS</span>
                                    <span class="stat-icon">👥</span>
                                </div>
                                <h3>{{ users.length }}</h3>
                                <small>Player accounts on Nexus</small>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="admin-stat-card">
                                <div class="stat-top">
                                    <span>DEVELOPERS</span>
                                    <span class="stat-icon">🛠️</span>
                                </div>
                                <h3>{{ developers.length }}</h3>
                                <small>Verified studio accounts</small>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="admin-stat-card stat-highlight">
                                <div class="stat-top">
                                    <span>TOTAL REVENUE</span>
                                    <span class="stat-icon">💎</span>
                                </div>
                                <h3>RM {{ totalRevenue.toFixed(2) }}</h3>
                                <small>Across {{ payments.length }} transactions</small>
                            </div>
                        </div>

                    </div>

                    <!-- Interactive Management Tabs & Search -->
                    <div class="admin-toolbar">
                        <div class="admin-tabs">
                            <button
                                type="button"
                                class="admin-tab"
                                :class="{ active: adminTab === 'games' }"
                                @click="adminTab = 'games'; adminSearch = ''"
                            >
                                Games ({{ adminGames.length }})
                            </button>
                            <button
                                type="button"
                                class="admin-tab"
                                :class="{ active: adminTab === 'users' }"
                                @click="adminTab = 'users'; adminSearch = ''"
                            >
                                Users ({{ users.length }})
                            </button>
                            <button
                                type="button"
                                class="admin-tab"
                                :class="{ active: adminTab === 'developers' }"
                                @click="adminTab = 'developers'; adminSearch = ''"
                            >
                                Developers ({{ developers.length }})
                            </button>
                        </div>

                        <input
                            v-model="adminSearch"
                            type="text"
                            class="admin-search"
                            :placeholder="`Search ${adminTab}...`"
                        />
                    </div>

                    <!-- Tab 1: All Games Management -->
                    <div v-if="adminTab === 'games'">
                        <div v-if="filteredAdminGames.length" class="table-responsive">
                            <table class="profile-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Game</th>
                                        <th>Developer</th>
                                        <th>Genre</th>
                                        <th>Price</th>
                                        <th>Rating</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="game in filteredAdminGames" :key="game.game_id">
                                        <td class="id-cell">{{ game.game_id }}</td>
                                        <td>
                                            <div class="game-table-info">
                                                <img
                                                    v-if="game.img"
                                                    :src="game.img"
                                                    :alt="game.title"
                                                />
                                                <div v-else class="table-placeholder">🎮</div>
                                                <router-link
                                                    :to="gameLink(game)"
                                                    class="text-white text-decoration-none"
                                                    @click="openGame(game)"
                                                >
                                                    <strong>{{ game.title }}</strong>
                                                </router-link>
                                            </div>
                                        </td>
                                        <td>{{ resolveDevName(game) }}</td>
                                        <td><span class="pill-tag">{{ game.genre }}</span></td>
                                        <td>{{ price(game.price) }}</td>
                                        <td class="rating-cell">★ {{ game.rating.toFixed(1) }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <router-link
                                                    :to="`/games/edit/${game.game_id}`"
                                                    class="edit-action"
                                                >
                                                    Edit
                                                </router-link>
                                                <button
                                                    type="button"
                                                    class="delete-action"
                                                    @click="deleteGame(game.game_id)"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="empty-state">No games match your search.</p>
                    </div>

                    <!-- Tab 2: Users Directory -->
                    <div v-else-if="adminTab === 'users'">
                        <div v-if="filteredAdminUsers.length" class="table-responsive">
                            <table class="profile-table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Games Owned</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="u in filteredAdminUsers" :key="u.user_id">
                                        <td class="id-cell">{{ u.user_id }}</td>
                                        <td><strong>{{ u.username }}</strong></td>
                                        <td>{{ u.email }}</td>
                                        <td>{{ countUserGames(u.user_id) }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="delete-action"
                                                @click="deleteAccount('user', 'user_id', u.user_id, u.username)"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="empty-state">No users match your search.</p>
                    </div>

                    <!-- Tab 3: Developers Directory -->
                    <div v-else-if="adminTab === 'developers'">
                        <div v-if="filteredAdminDevelopers.length" class="table-responsive">
                            <table class="profile-table">
                                <thead>
                                    <tr>
                                        <th>Developer ID</th>
                                        <th>Studio / Username</th>
                                        <th>Email</th>
                                        <th>Published Games</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="d in filteredAdminDevelopers" :key="d.developer_id">
                                        <td class="id-cell">{{ d.developer_id }}</td>
                                        <td>
                                            <strong>{{ d.username }}</strong>
                                            <span
                                                v-if="String(d.developer_id).toUpperCase() === 'D0000'"
                                                class="admin-inline-badge"
                                            >
                                                ADMIN
                                            </span>
                                        </td>
                                        <td>{{ d.email }}</td>
                                        <td>{{ countDevGames(d) }}</td>
                                        <td>
                                            <button
                                                v-if="String(d.developer_id).toUpperCase() !== 'D0000'"
                                                type="button"
                                                class="delete-action"
                                                @click="deleteAccount('developer', 'developer_id', d.developer_id, d.username)"
                                            >
                                                Remove
                                            </button>
                                            <span v-else class="text-muted">Protected</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="empty-state">No developers match your search.</p>
                    </div>

                </template>

                <!-- Favourites (hidden when admin is logged in) -->
                <section v-if="!isAdmin" class="favourites-section">
                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">SAVED WORLDS</span>
                            <h2>My Favourites</h2>
                            <p>Games you have added to your favourites.</p>
                        </div>

                        <router-link to="/favourite" class="view-all">
                            View All →
                        </router-link>
                    </div>

                    <div v-if="favouriteGames.length" class="row g-3">

                        <div
                            v-for="game in favouriteGames"
                            :key="game.game_id"
                            class="col-12 col-sm-6 col-lg-3"
                        >
                            <router-link
                                :to="gameLink(game)"
                                class="favourite-item"
                                @click="openGame(game)"
                            >
                                <strong>{{ game.title }}</strong>
                                <span>★ {{ game.rating.toFixed(1) }}</span>
                            </router-link>
                        </div>

                    </div>

                    <p v-else class="empty-state">
                        No favourite games yet.
                    </p>
                </section>

            </div>
        </section>

    </main>
</template>

<script>
export default {
    name: 'ProfileView',
    data() {
        return {
            profile: {
                id: '',
                username: 'Guest',
                email: '',
                role: 'user',
                joinedDate: '2025'
            },
            games: [],
            users: [],
            developers: [],
            favourites: [],
            payments: [],
            adminTab: 'games',
            adminSearch: '',
            loading: false,
            err: ''
        };
    },
    computed: {
        role() {
            const devId = String(this.profile.developer_id || '').trim().toLowerCase();
            const rawRole = String(this.profile.role || '').trim().toLowerCase();

            // Admin is identified by developer_id D0000 or explicit admin role
            if (devId === 'd0000' || rawRole === 'admin') {
                return 'admin';
            }
            if (rawRole === 'developer' || rawRole === 'creator' || Boolean(devId)) {
                return 'creator';
            }
            return 'user';
        },
        displayName() {
            return this.profile.username || this.profile.displayName || 'Player';
        },
        formattedAvatar() {
            const raw = String(this.profile.avatar || '').trim();
            if (!raw) return '';
            if (raw.startsWith('data:image/') || raw.startsWith('http://') || raw.startsWith('https://')) {
                return raw;
            }
            return `data:image/jpeg;base64,${raw}`;
        },
        initials() {
            return this.displayName
                .split(' ')
                .map((word) => word[0])
                .join('')
                .slice(0, 2)
                .toUpperCase();
        },
        isCreator() {
            return this.role === 'creator';
        },
        isAdmin() {
            return this.role === 'admin';
        },
        creatorId() {
            return String(this.profile.developer_id || this.profile.id || '').trim();
        },
        userId() {
            return String(this.profile.user_id || this.profile.id || '').trim();
        },
        creatorGames() {
            const devId = this.creatorId.toLowerCase();
            const devName = String(this.profile.username || '').trim().toLowerCase();

            return this.games.filter((game) => {
                const gameDevId = String(game.developer_id || '').trim().toLowerCase();
                const gameDev = String(game.developer || '').trim().toLowerCase();

                return (
                    (devId && (gameDevId === devId || gameDev === devId)) ||
                    (devName && gameDev === devName)
                );
            });
        },
        purchasedGames() {
            const paidIds = new Set(
                this.payments
                    .filter((p) => String(p.user_id || '') === this.userId)
                    .map((p) => String(p.game_id))
            );
            return this.games.filter((game) => paidIds.has(String(game.game_id)));
        },
        favouriteGames() {
            const currentId = this.isCreator ? this.creatorId : this.userId;
            const favIds = new Set(
                this.favourites
                    .filter((f) => String(f.user_id || '') === currentId)
                    .map((f) => String(f.game_id))
            );
            return this.games.filter((game) => favIds.has(String(game.game_id)));
        },
        adminGames() {
            return this.games;
        },
        totalRevenue() {
            return this.payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
        },
        filteredAdminGames() {
            const q = this.adminSearch.trim().toLowerCase();
            if (!q) return this.adminGames;
            return this.adminGames.filter(
                (g) =>
                    String(g.title || '').toLowerCase().includes(q) ||
                    String(g.genre || '').toLowerCase().includes(q) ||
                    String(g.game_id || '').toLowerCase().includes(q) ||
                    this.resolveDevName(g).toLowerCase().includes(q)
            );
        },
        filteredAdminUsers() {
            const q = this.adminSearch.trim().toLowerCase();
            if (!q) return this.users;
            return this.users.filter(
                (u) =>
                    String(u.username || '').toLowerCase().includes(q) ||
                    String(u.email || '').toLowerCase().includes(q) ||
                    String(u.user_id || '').toLowerCase().includes(q)
            );
        },
        filteredAdminDevelopers() {
            const q = this.adminSearch.trim().toLowerCase();
            if (!q) return this.developers;
            return this.developers.filter(
                (d) =>
                    String(d.username || '').toLowerCase().includes(q) ||
                    String(d.email || '').toLowerCase().includes(q) ||
                    String(d.developer_id || '').toLowerCase().includes(q)
            );
        }
    },
    mounted() {
        this.loadProfile();
        this.fetchDashboardData();
    },
    methods: {
        loadProfile() {
            try {
                const saved =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user'));

                if (saved) {
                    this.profile = saved;
                }
            } catch {
                // Keep default profile if storage is empty
            }
        },
        toSlug(title) {
            return String(title)
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        },
        price(value) {
            const num = Number(value) || 0;
            return num === 0 ? 'Free' : `RM ${num.toFixed(2)}`;
        },
        gameLink(game) {
            return `/game/${this.toSlug(game.title)}`;
        },
        openGame(game) {
            sessionStorage.setItem('selectedGame', JSON.stringify(game));
        },
        resolveDevName(game) {
            const raw = String(game.developer_id || game.developer || '').trim();
            if (!raw) return 'Unknown';
            const found = this.developers.find(
                (d) =>
                    String(d.developer_id || '').trim().toLowerCase() === raw.toLowerCase() ||
                    String(d.username || '').trim().toLowerCase() === raw.toLowerCase()
            );
            return found ? `${found.username} (${found.developer_id})` : raw;
        },
        countUserGames(uid) {
            return this.payments.filter((p) => String(p.user_id) === String(uid)).length;
        },
        countDevGames(dev) {
            const dId = String(dev.developer_id || '').trim().toLowerCase();
            const dName = String(dev.username || '').trim().toLowerCase();
            return this.games.filter((g) => {
                const gDev = String(g.developer || '').trim().toLowerCase();
                const gDevId = String(g.developer_id || '').trim().toLowerCase();
                return gDev === dId || gDevId === dId || (dName && gDev === dName);
            }).length;
        },
        fetchDashboardData() {
            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.loading = true;
            this.err = '';

            Promise.all([
                fetch(`${baseApi}/game`).then((r) => r.json()),
                fetch(`${baseApi}/favourite`).then((r) => r.json()).catch(() => []),
                fetch(`${baseApi}/payment`).then((r) => r.json()).catch(() => []),
                fetch(`${baseApi}/user`).then((r) => r.json()).catch(() => []),
                fetch(`${baseApi}/developer`).then((r) => r.json()).catch(() => [])
            ])
                .then(([gamesRes, favRes, payRes, usersRes, devsRes]) => {
                    this.games = Array.isArray(gamesRes)
                        ? gamesRes.map((row) => ({
                              ...row,
                              id: row.game_id,
                              img: row.image || '',
                              price: Number(row.price) || 0,
                              rating: Number(row.rating) || 0
                          }))
                        : [];

                    this.favourites = Array.isArray(favRes) ? favRes : [];
                    this.payments = Array.isArray(payRes) ? payRes : [];
                    this.users = Array.isArray(usersRes) ? usersRes : [];
                    this.developers = Array.isArray(devsRes) ? devsRes : [];
                })
                .catch((error) => {
                    this.err = error.message;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        deleteGame(gameId) {
            const confirmed = window.confirm(
                'Are you sure you want to delete this game?'
            );
            if (!confirmed) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;

            fetch(`${baseApi}/game/game_id/${encodeURIComponent(gameId)}`, {
                method: 'DELETE'
            })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    if (data.error) {
                        alert('Failed to delete game: ' + data.error);
                        return;
                    }
                    this.games = this.games.filter(
                        (game) => String(game.game_id) !== String(gameId)
                    );
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: 'Game deleted from marketplace.', type: 'info' }
                        })
                    );
                })
                .catch((error) => {
                    alert('Error deleting game: ' + error.message);
                });
        },
        deleteAccount(table, idCol, idVal, label) {
            const confirmed = window.confirm(
                `Remove ${label} (${idVal}) from Nexus?`
            );
            if (!confirmed) return;

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;

            fetch(`${baseApi}/${table}/${idCol}/${encodeURIComponent(idVal)}`, {
                method: 'DELETE'
            })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    if (data.error) {
                        alert('Failed to remove account: ' + data.error);
                        return;
                    }
                    if (table === 'user') {
                        this.users = this.users.filter((u) => String(u.user_id) !== String(idVal));
                    } else {
                        this.developers = this.developers.filter((d) => String(d.developer_id) !== String(idVal));
                    }
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: `${label} removed.`, type: 'info' }
                        })
                    );
                })
                .catch((error) => {
                    alert('Error removing account: ' + error.message);
                });
        }
    }
};
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
}

.profile-hero {
    min-height: 420px;
    padding: 100px 0;
    background:
        linear-gradient(90deg, #08090df2, #08090d99),
        url('../assets/hero.png') center / cover;
    border-bottom: 1px solid var(--line);
}

.profile-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
}

.profile-main {
    display: flex;
    align-items: center;
    gap: 42px;
}

.profile-avatar {
    width: 160px;
    height: 160px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    position: relative;
    border: 1px solid var(--cyan);
    border-radius: 50%;
    background: #171b26;
    color: #fff;
    font-size: 42px;
    font-weight: 800;
    box-shadow: 0 0 35px #00f2fe18;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.online-indicator {
    position: absolute;
    width: 17px;
    height: 17px;
    right: 12px;
    bottom: 17px;
    border-radius: 50%;
    background: #55f5ae;
    border: 3px solid var(--bg);
}

.profile-badge {
    display: inline-block;
    padding: 7px 12px;
    border: 1px solid var(--cyan);
    color: var(--cyan);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.15em;
}

.badge-admin {
    border-color: #a58fff;
    color: #a58fff;
    background: rgba(165, 143, 255, 0.1);
}

.profile-details h1 {
    font-size: clamp(34px, 5vw, 64px);
    font-weight: 800;
    letter-spacing: -0.05em;
    margin: 15px 0 5px;
}

.profile-details > p {
    color: var(--muted);
    margin-bottom: 12px;
}

.profile-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    color: var(--muted);
    font-size: 12px;
}

.profile-stats strong {
    color: #fff;
}

.edit-profile-btn,
.add-game-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 20px;
    color: #fff;
    background: #ffffff12;
    border: 1px solid #ffffff35;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.edit-profile-btn:hover {
    color: var(--cyan);
    border-color: var(--cyan);
}

.profile-content {
    padding: 65px 0;
}

.container {
    max-width: 1200px;
}

.section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
}

.section-subtitle {
    color: var(--cyan);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.2em;
}

.section-heading h2 {
    font-size: 28px;
    font-weight: 800;
    margin: 8px 0;
}

.section-heading p {
    color: var(--muted);
    font-size: 13px;
    margin: 0;
}

.view-all {
    color: var(--cyan);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.profile-game-card {
    height: 100%;
    background: var(--panel);
    border: 1px solid var(--line);
}

.profile-game-card > img,
.game-placeholder {
    width: 100%;
    height: 165px;
    object-fit: cover;
}

.game-placeholder,
.table-placeholder {
    display: grid;
    place-items: center;
    background: var(--panel2);
    font-size: 35px;
}

.game-card-info {
    padding: 16px;
}

.game-card-info > span {
    color: var(--cyan);
    font-size: 10px;
    text-transform: uppercase;
}

.game-card-info h3 {
    font-size: 17px;
    margin: 7px 0 15px;
}

.game-card-info a {
    color: var(--cyan);
    font-size: 12px;
    text-decoration: none;
}

.add-game-btn {
    background: var(--cyan);
    color: #061014;
    border-color: var(--cyan);
}

.add-game-btn:hover {
    color: #061014;
    filter: brightness(1.1);
}

/* Admin KPI Cards */
.admin-stat-card {
    height: 100%;
    padding: 24px;
    background: var(--panel);
    border: 1px solid var(--line);
    transition: border-color 0.2s, transform 0.2s;
}

.admin-stat-card:hover {
    border-color: var(--cyan);
    transform: translateY(-2px);
}

.admin-stat-card.stat-highlight {
    border-color: rgba(165, 143, 255, 0.45);
    background: linear-gradient(145deg, rgba(165, 143, 255, 0.08), var(--panel));
}

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.admin-stat-card span {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.stat-icon {
    font-size: 18px;
}

.admin-stat-card h3 {
    font-size: 30px;
    font-weight: 800;
    margin: 12px 0 6px;
}

.admin-stat-card small {
    color: var(--muted);
    font-size: 12px;
}

/* Admin Toolbar & Tabs */
.admin-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 20px;
}

.admin-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.admin-tab {
    padding: 10px 18px;
    background: var(--panel);
    border: 1px solid var(--line);
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.admin-tab:hover {
    color: #fff;
    border-color: rgba(0, 242, 254, 0.45);
}

.admin-tab.active {
    background: rgba(0, 242, 254, 0.12);
    border-color: var(--cyan);
    color: var(--cyan);
}

.admin-search {
    min-width: 260px;
    padding: 10px 14px;
    background: #0c0e13;
    border: 1px solid var(--line);
    color: var(--text);
    font-size: 12px;
    outline: none;
}

.admin-search:focus {
    border-color: var(--cyan);
}

.profile-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--panel);
    border: 1px solid var(--line);
    font-size: 12px;
}

.profile-table th {
    color: var(--muted);
    text-align: left;
    padding: 16px 17px;
    border-bottom: 1px solid var(--line);
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.profile-table td {
    padding: 15px 17px;
    border-bottom: 1px solid var(--line);
}

.profile-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.id-cell {
    color: var(--cyan);
    font-family: monospace;
    font-weight: 700;
}

.rating-cell {
    color: var(--gold);
}

.pill-tag {
    display: inline-block;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--cyan);
    background: rgba(0, 242, 254, 0.08);
    border: 1px solid rgba(0, 242, 254, 0.35);
}

.admin-inline-badge {
    margin-left: 8px;
    padding: 2px 6px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #a58fff;
    border: 1px solid #a58fff;
}

.game-table-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.game-table-info img,
.table-placeholder {
    width: 55px;
    height: 40px;
    object-fit: cover;
}

.table-actions {
    display: flex;
    gap: 14px;
}

.table-actions a,
.table-actions button {
    background: none;
    border: none;
    padding: 0;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.edit-action {
    color: var(--cyan);
}

.delete-action {
    color: #ff6969;
}

.empty-state {
    padding: 35px;
    text-align: center;
    color: var(--muted);
    background: var(--panel);
    border: 1px dashed var(--line);
}

.favourites-section {
    margin-top: 75px;
    padding-top: 40px;
    border-top: 1px solid var(--line);
}

.favourite-item {
    display: flex;
    justify-content: space-between;
    padding: 17px;
    background: var(--panel);
    border: 1px solid var(--line);
    color: var(--text);
    text-decoration: none;
    font-size: 12px;
}

.favourite-item:hover {
    border-color: var(--cyan);
}

.favourite-item span {
    color: var(--gold);
}

@media (max-width: 767px) {
    .profile-hero {
        padding: 55px 0;
    }

    .profile-container {
        align-items: flex-start;
        flex-direction: column;
    }

    .profile-main {
        align-items: flex-start;
        flex-direction: column;
        gap: 20px;
    }

    .profile-avatar {
        width: 110px;
        height: 110px;
        font-size: 32px;
    }

    .profile-details h1 {
        font-size: 36px;
    }

    .profile-stats {
        gap: 12px;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-search {
        width: 100%;
    }
}
</style>