
<script setup>
import { reactive, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { gamesData } from '../assets/games.js';

const route = useRoute();
const router = useRouter();

const gameId = Number(route.params.id);

const game = gamesData.find(item => item.id === gameId);

function loadCurrentUser() {
    try {
        return JSON.parse(localStorage.getItem('user')) || {
            id: 1,
            role: 'creator',
            creatorGameIds: [1, 2]
        };
    } catch {
        return {
            id: 1,
            role: 'creator',
            creatorGameIds: [1, 2]
        };
    }
}

const currentUser = loadCurrentUser();

const isAllowed = computed(() => {
    if (!game) return false;

    if (currentUser.role === 'admin') return true;

    return currentUser.role === 'creator' &&
        (
            game.creatorId === currentUser.id ||
            (currentUser.creatorGameIds || []).includes(game.id)
        );
});

const platforms = [
    'pc',
    'playstation',
    'xbox',
    'switch'
];

const genres = [
    'action',
    'adventure',
    'racing',
    'sci-fi',
    'rpg',
    'strategy',
    'simulation',
    'sports',
    'horror',
    'puzzle'
];

const form = reactive({
    title: game?.title || '',
    genre: game?.genre || 'action',
    platforms: [...(game?.platforms || [])],
    rating: game?.rating ?? 0,
    year: game?.year ?? new Date().getFullYear(),
    price: game?.price ?? 0,
    releaseDate: game?.releaseDate || '',
    img: game?.img || '',
    tagline: game?.tagline || '',
    description: game?.description || ''
});

const errorMessage = reactive({
    text: ''
});

function updateGame() {
    if (!game || !isAllowed.value) return;

    if (!form.platforms.length) {
        errorMessage.text = 'Please select at least one platform.';
        return;
    }

    Object.assign(game, {
        ...form,
        platforms: [...form.platforms],
        year: new Date(form.releaseDate).getFullYear()
    });

    router.push('/profile');
}

function cancel() {
    router.push('/profile');
}
</script>

<template>
    <main class="game-form-page">

        <div class="container">

            <template v-if="!game || !isAllowed">

                <div class="not-found">
                    <span>ACCESS DENIED</span>
                    <h1>Game Not Available</h1>
                    <p>
                        This game does not exist or you do not have
                        permission to edit it.
                    </p>

                    <button @click="cancel">
                        Back to Profile
                    </button>
                </div>

            </template>

            <template v-else>

                <div class="page-heading">
                    <span class="eyebrow">CREATOR STUDIO</span>
                    <h1>Edit Game</h1>
                    <p>Update your game information on Nexus.</p>
                </div>

                <form
                    class="game-form-card"
                    @submit.prevent="updateGame"
                >

                    <div class="form-section">
                        <h2>Basic Information</h2>

                        <div class="form-group">
                            <label>Game Title *</label>

                            <input
                                v-model="form.title"
                                type="text"
                                maxlength="100"
                                required
                            />
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Genre *</label>

                                    <select v-model="form.genre" required>
                                        <option
                                            v-for="genre in genres"
                                            :key="genre"
                                            :value="genre"
                                        >
                                            {{ genre }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Price (RM) *</label>

                                    <input
                                        v-model.number="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                </div>
                            </div>

                        </div>

                        <div class="form-group">
                            <label>Supported Platforms *</label>

                            <div class="platform-options">

                                <label
                                    v-for="platform in platforms"
                                    :key="platform"
                                    class="platform-option"
                                >
                                    <input
                                        v-model="form.platforms"
                                        type="checkbox"
                                        :value="platform"
                                    />

                                    {{ platform }}
                                </label>

                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Game Details</h2>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Release Date *</label>

                                    <input
                                        v-model="form.releaseDate"
                                        type="date"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rating (0 - 5)</label>

                                    <input
                                        v-model.number="form.rating"
                                        type="number"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                    />
                                </div>
                            </div>

                        </div>

                        <div class="form-group">
                            <label>Thumbnail Image URL</label>

                            <input
                                v-model="form.img"
                                type="url"
                                placeholder="https://example.com/game-image.jpg"
                            />
                        </div>

                        <div class="form-group">
                            <label>Tagline *</label>

                            <input
                                v-model="form.tagline"
                                type="text"
                                maxlength="150"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label>Description *</label>

                            <textarea
                                v-model="form.description"
                                rows="5"
                                required
                            ></textarea>
                        </div>
                    </div>

                    <p v-if="errorMessage.text" class="error-message">
                        {{ errorMessage.text }}
                    </p>

                    <div class="form-actions">

                        <button
                            type="button"
                            class="cancel-btn"
                            @click="cancel"
                        >
                            Cancel
                        </button>

                        <button type="submit" class="submit-btn">
                            Save Changes
                        </button>

                    </div>

                </form>

            </template>

        </div>

    </main>
</template>

<style scoped>
/* Same styling as AddGame.vue */

.game-form-page {
    min-height: 100vh;
    padding: 60px 20px;
    background: var(--bg);
    color: var(--text);
}

.container {
    max-width: 850px;
}

.page-heading {
    margin-bottom: 30px;
}

.eyebrow,
.not-found > span {
    color: var(--cyan);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.2em;
}

.page-heading h1 {
    font-size: 38px;
    font-weight: 800;
    margin: 10px 0;
}

.page-heading p {
    color: var(--muted);
    font-size: 13px;
}

.game-form-card {
    padding: 30px;
    background: var(--panel);
    border: 1px solid var(--line);
}

.form-section {
    margin-bottom: 35px;
}

.form-section h2 {
    font-size: 18px;
    font-weight: 800;
    padding-bottom: 15px;
    margin-bottom: 22px;
    border-bottom: 1px solid var(--line);
}

.form-group {
    display: grid;
    gap: 9px;
    margin-bottom: 20px;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 13px;
    color: var(--text);
    background: #0c0e13;
    border: 1px solid var(--line);
    outline: none;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--cyan);
}

.form-group select option {
    background: #0c0e13;
}

.platform-options {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.platform-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #ffffff0b;
    border: 1px solid var(--line);
    cursor: pointer;
    text-transform: capitalize;
}

.platform-option input {
    accent-color: var(--cyan);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.cancel-btn,
.submit-btn {
    padding: 12px 20px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--line);
}

.cancel-btn {
    background: transparent;
    color: var(--text);
}

.submit-btn {
    background: var(--cyan);
    color: #061014;
    border-color: var(--cyan);
}

.error-message {
    color: #ff6969;
    font-size: 12px;
}

.not-found {
    padding: 70px 20px;
    text-align: center;
    background: var(--panel);
    border: 1px solid var(--line);
}

.not-found h1 {
    margin: 15px 0;
}

.not-found p {
    color: var(--muted);
}

.not-found button {
    padding: 12px 20px;
    background: var(--cyan);
    border: 0;
    font-weight: 700;
}

@media (max-width: 576px) {
    .game-form-card {
        padding: 20px;
    }
}
</style>
