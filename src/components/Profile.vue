
<script setup>
import { ref, computed } from 'vue';
import { gamesData, toSlug } from '../assets/games.js';

const demoProfile = {
    id: 1,
    username: 'Alex Morgan',
    email: 'alex@nexus.com',
    role: 'creator',
    joinedDate: '2024-01-15',
    purchasedGameIds: [1],
    creatorGameIds: [1, 2]
};

// Temporary profile until the authentication API is connected
function loadProfile() {
    try {
        return JSON.parse(localStorage.getItem('user')) || demoProfile;
    } catch {
        return demoProfile;
    }
}

const profile = ref(loadProfile());
const games = ref([...gamesData]);

const role = computed(() => profile.value.role?.toLowerCase() || 'user');

const displayName = computed(() =>
    profile.value.username || profile.value.displayName || 'Player'
);

const initials = computed(() =>
    displayName.value
        .split(' ')
        .map(word => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
);

const isCreator = computed(() => role.value === 'creator');
const isAdmin = computed(() => role.value === 'admin');

// Purchased games
const purchasedGames = computed(() => {
    const ids = profile.value.purchasedGameIds || [];

    return games.value.filter(game => ids.includes(game.id));
});

// Favourite games
const favouriteGames = computed(() =>
    games.value.filter(game => game.liked)
);

// Creator's own games
const creatorGames = computed(() => {
    const ids = profile.value.creatorGameIds || [];

    return games.value.filter(game => ids.includes(game.id));
});

// Admin can see every game
const adminGames = computed(() => games.value);

const price = (value) => `RM ${Number(value).toFixed(2)}`;

const gameLink = (game) => `/game/${toSlug(game.title)}`;

function deleteGame(id) {
    const confirmed = window.confirm(
        'Are you sure you want to delete this game?'
    );

    if (!confirmed) return;

    // Temporary frontend deletion only.
    // Replace with the backend DELETE API later.
    games.value = games.value.filter(game => game.id !== id);
}
</script>

<template>
    <main class="profile-page">

        <!-- Profile Banner -->
        <section class="profile-hero">
            <div class="container profile-container">

                <div class="profile-main">

                    <div class="profile-avatar">
                        {{ initials }}
                        <span class="online-indicator"></span>
                    </div>

                    <div class="profile-details">

                        <span class="profile-badge">
                            NEXUS {{ role.toUpperCase() }}
                        </span>

                        <h1>{{ displayName }}</h1>

                        <p>{{ profile.email }}</p>

                        <div class="profile-stats">
                            <span>
                                <strong>
                                    {{ isCreator ? creatorGames.length :
                                       isAdmin ? adminGames.length :
                                       purchasedGames.length }}
                                </strong>
                                {{ isCreator ? 'Games' :
                                   isAdmin ? 'Total Games' : 'Games Owned' }}
                            </span>

                            <span>
                                <strong>{{ favouriteGames.length }}</strong>
                                Favourites
                            </span>

                            <span>
                                <strong>Member</strong>
                                Since {{ profile.joinedDate || '2025' }}
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

                <!-- Normal User -->
                <template v-if="!isCreator && !isAdmin">

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
                            :key="game.id"
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

                                    <router-link :to="gameLink(game)">
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

                <!-- Creator -->
                <template v-else-if="isCreator">

                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">CREATOR STUDIO</span>
                            <h2>My Uploaded Games</h2>
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
                                    :key="game.id"
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

                                            <strong>{{ game.title }}</strong>
                                        </div>
                                    </td>

                                    <td>{{ game.genre }}</td>
                                    <td>{{ price(game.price) }}</td>
                                    <td>★ {{ game.rating }}</td>

                                    <td>
                                        <div class="table-actions">
                                            <router-link
                                                :to="`/games/edit/${game.id}`"
                                                class="edit-action"
                                            >
                                                Edit
                                            </router-link>

                                            <button
                                                type="button"
                                                class="delete-action"
                                                @click="deleteGame(game.id)"
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
                        You haven't uploaded any games yet.
                    </p>

                </template>

                <!-- Admin -->
                <template v-else-if="isAdmin">

                    <div class="section-heading">
                        <div>
                            <span class="section-subtitle">ADMINISTRATION</span>
                            <h2>Admin Dashboard</h2>
                            <p>Manage the Nexus gaming marketplace.</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-5">

                        <div class="col-12 col-md-4">
                            <div class="admin-stat-card">
                                <span>Total Games</span>
                                <h3>{{ adminGames.length }}</h3>
                                <small>Games in the marketplace</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="admin-stat-card">
                                <span>Total Users</span>
                                <h3>—</h3>
                                <small>Connect to database</small>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="admin-stat-card">
                                <span>Total Creators</span>
                                <h3>—</h3>
                                <small>Connect to database</small>
                            </div>
                        </div>

                    </div>

                    <div class="section-heading">
                        <div>
                            <h2>Management</h2>
                            <p>Manage accounts and marketplace content.</p>
                        </div>
                    </div>

                    <div class="row g-3">

                        <div class="col-12 col-md-4">
                            <router-link
                                to="/admin/users"
                                class="management-card"
                            >
                                <span>👥</span>
                                <h3>User Management</h3>
                                <p>View and manage user accounts.</p>
                                <strong>Manage Users →</strong>
                            </router-link>
                        </div>

                        <div class="col-12 col-md-4">
                            <router-link
                                to="/admin/creators"
                                class="management-card"
                            >
                                <span>🎮</span>
                                <h3>Creator Management</h3>
                                <p>Manage creator accounts.</p>
                                <strong>Manage Creators →</strong>
                            </router-link>
                        </div>

                        <div class="col-12 col-md-4">
                            <router-link
                                to="/admin/games"
                                class="management-card"
                            >
                                <span>🕹️</span>
                                <h3>Game Management</h3>
                                <p>Manage all games on Nexus.</p>
                                <strong>Manage Games →</strong>
                            </router-link>
                        </div>

                    </div>

                </template>

                <!-- Favourites shared by all roles -->
                <section class="favourites-section">
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
                            :key="game.id"
                            class="col-12 col-sm-6 col-lg-3"
                        >
                            <router-link
                                :to="gameLink(game)"
                                class="favourite-item"
                            >
                                <strong>{{ game.title }}</strong>
                                <span>★ {{ game.rating }}</span>
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

.profile-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--panel);
    font-size: 12px;
}

.profile-table th {
    color: var(--muted);
    text-align: left;
    padding: 17px;
    border-bottom: 1px solid var(--line);
}

.profile-table td {
    padding: 15px 17px;
    border-bottom: 1px solid var(--line);
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
    font-size: 12px;
    text-decoration: none;
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

.admin-stat-card,
.management-card {
    display: block;
    height: 100%;
    padding: 24px;
    background: var(--panel);
    border: 1px solid var(--line);
}

.admin-stat-card span {
    color: var(--muted);
    font-size: 12px;
}

.admin-stat-card h3 {
    font-size: 32px;
    font-weight: 800;
    margin: 12px 0;
}

.admin-stat-card small {
    color: var(--muted);
}

.management-card {
    color: var(--text);
    text-decoration: none;
    transition: border-color 0.2s;
}

.management-card:hover {
    color: var(--text);
    border-color: var(--cyan);
}

.management-card > span {
    font-size: 28px;
}

.management-card h3 {
    font-size: 17px;
    margin: 15px 0 8px;
}

.management-card p {
    color: var(--muted);
    font-size: 12px;
}

.management-card strong {
    color: var(--cyan);
    font-size: 12px;
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
}
</style>
