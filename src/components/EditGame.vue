<script>
import { cpu } from '../api/cpuapi.js';
import { gpu } from '../api/gpuapi.js';

export default {
    name: 'EditGame',
    data() {
        return {
            currentUser: null,
            game: null,
            loading: true,
            submitting: false,
            errorMessage: '',
            imagePreview: '',
            imageFileName: '',
            imageChanged: false,
            isToday: false,
            cpuOptions: cpu,
            gpuOptions: gpu,
            osOptions: [
                { label: 'Windows 10', value: 'windows10' },
                { label: 'Windows 11', value: 'windows11' },
                { label: 'Windows 10 / Windows 11', value: 'windows10,windows11' }
            ],
            memoryOptions: [2, 4, 6, 8, 10, 12, 14, 16],
            platforms: [
                'PC',
                'PlayStation',
                'Xbox',
                'Nintendo Switch'
            ],
            genres: [
                'Battle Royale',
                'Sports',
                'Shooter',
                'Action RPG',
                'MMORPG',
                'Adventure',
                'Racing',
                'Strategy'
            ],
            form: {
                title: '',
                genre: 'Action RPG',
                platforms: [],
                release_date: '',
                description: '',
                image: null,
                url: '',
                price: 0,
                rating: 0,
                os: 'windows10,windows11',
                cpuId: cpu[0]?.id || '',
                memory: 8,
                gpuId: gpu[0]?.id || ''
            }
        };
    },
    computed: {
        todayDate() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        isAllowed() {
            if (!this.game || !this.currentUser) return false;

            const devId = String(this.currentUser.developer_id || '').trim().toLowerCase();
            if (!devId) return false;

            // Admin developer account (D0000) can edit all games
            if (devId === 'd0000') return true;

            // Regular developers can only edit their own games
            const gameDev = String(this.game.developer || '').trim().toLowerCase();
            const devUsername = String(this.currentUser.username || '').trim().toLowerCase();

            return (
                gameDev === devId ||
                (devUsername && gameDev === devUsername)
            );
        }
    },
    watch: {
        isToday(checked) {
            if (checked) {
                this.form.release_date = this.todayDate;
            }
        },
        '$route.params.id'() {
            this.loadGame();
        }
    },
    mounted() {
        this.loadCurrentUser();
        this.loadGame();
    },
    methods: {
        loadCurrentUser() {
            try {
                this.currentUser =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user'));
            } catch {
                this.currentUser = null;
            }
        },
        normalizePlatform(raw) {
            const lower = raw.trim().toLowerCase();
            if (lower === 'pc') return 'PC';
            if (lower === 'playstation' || lower === 'ps5' || lower === 'ps4') return 'PlayStation';
            if (lower === 'xbox') return 'Xbox';
            if (lower.includes('switch')) return 'Nintendo Switch';
            return raw.trim();
        },
        loadGame() {
            const gameId = String(this.$route.params.id || '').trim();
            if (!gameId) {
                this.loading = false;
                this.game = null;
                return;
            }

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.loading = true;
            this.errorMessage = '';

            fetch(`${baseApi}/game/game_id/${encodeURIComponent(gameId)}`)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((rows) => {
                    const row = Array.isArray(rows) ? rows[0] : rows;
                    if (!row || !row.game_id) {
                        this.game = null;
                        return;
                    }

                    this.game = row;
                    const parsedPlatforms = String(row.platform || '')
                        .split(',')
                        .map((p) => this.normalizePlatform(p))
                        .filter(Boolean);

                    // Ensure custom genres from DB remain selectable in dropdown
                    if (row.genre && !this.genres.includes(row.genre)) {
                        this.genres.unshift(row.genre);
                    }

                    // Parse saved specs array [os, cpuId, memoryGB, gpuId]
                    let parsedSpecs = [
                        'windows10,windows11',
                        cpu[0]?.id || '',
                        8,
                        gpu[0]?.id || ''
                    ];
                    if (row.specs) {
                        try {
                            const arr = Array.isArray(row.specs)
                                ? row.specs
                                : JSON.parse(row.specs);
                            if (Array.isArray(arr) && arr.length >= 4) {
                                parsedSpecs = arr;
                            }
                        } catch {
                            // Fall back to default specs if empty/invalid
                        }
                    }

                    const savedMemory = Number(parsedSpecs[2]) || 8;
                    if (!this.memoryOptions.includes(savedMemory)) {
                        this.memoryOptions.push(savedMemory);
                        this.memoryOptions.sort((a, b) => a - b);
                    }

                    this.form = {
                        title: row.title || '',
                        genre: row.genre || 'Action RPG',
                        platforms: parsedPlatforms,
                        release_date: row.release_date || '',
                        description: row.description || '',
                        image: row.image || null,
                        url: row.url || '',
                        price: Number(row.price) || 0,
                        rating: Number(row.rating) || 0,
                        os: parsedSpecs[0] || 'windows10,windows11',
                        cpuId: Number(parsedSpecs[1]) || (cpu[0]?.id || ''),
                        memory: savedMemory,
                        gpuId: Number(parsedSpecs[3]) || (gpu[0]?.id || '')
                    };

                    this.isToday = row.release_date === this.todayDate;
                    this.imagePreview = row.image || '';
                    this.imageFileName = row.image ? 'Current saved image' : '';
                    this.imageChanged = false;
                })
                .catch((error) => {
                    this.errorMessage = 'Failed to load game: ' + error.message;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        handleImageUpload(event) {
            this.errorMessage = '';
            const file = event.target.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'Please select a valid image file (PNG, JPG, WEBP).';
                event.target.value = '';
                return;
            }

            const maxBytes = 5 * 1024 * 1024; // 5 MB
            if (file.size > maxBytes) {
                this.errorMessage = 'Image must be smaller than 5 MB.';
                event.target.value = '';
                return;
            }

            this.imageFileName = file.name;
            const reader = new FileReader();
            reader.onload = (e) => {
                const dataUrl = e.target?.result || '';
                this.imagePreview = dataUrl;
                this.form.image = dataUrl;
                this.imageChanged = true;
            };
            reader.onerror = () => {
                this.errorMessage = 'Failed to read the selected image file.';
            };
            reader.readAsDataURL(file);
        },
        clearImage() {
            this.form.image = null;
            this.imagePreview = '';
            this.imageFileName = '';
            this.imageChanged = true;
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        },
        updateGame() {
            if (!this.game || !this.isAllowed) return;
            this.errorMessage = '';

            if (!this.form.platforms.length) {
                this.errorMessage = 'Please select at least one platform.';
                return;
            }

            const releaseDate = this.isToday ? this.todayDate : this.form.release_date;
            if (!releaseDate) {
                this.errorMessage = 'Please choose a release date or check "Release date is Today".';
                return;
            }

            const specsArray = [
                this.form.os,
                Number(this.form.cpuId),
                Number(this.form.memory),
                Number(this.form.gpuId)
            ];

            const payload = {
                title: this.form.title.trim(),
                genre: this.form.genre,
                platform: this.form.platforms.join(', '),
                release_date: releaseDate,
                description: this.form.description.trim(),
                url: this.form.url.trim(),
                price: Number(this.form.price) || 0,
                rating: Number(this.form.rating) || 0,
                specs: JSON.stringify(specsArray)
            };

            if (this.imageChanged) {
                payload.image = this.form.image || null;
            }

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            this.submitting = true;

            fetch(`${baseApi}/game/game_id/${encodeURIComponent(this.game.game_id)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
                    return res.json();
                })
                .then((data) => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    sessionStorage.removeItem('selectedGame');
                    window.dispatchEvent(
                        new CustomEvent('show-toast', {
                            detail: { message: `${payload.title} updated successfully!`, type: 'success' }
                        })
                    );
                    this.$router.push('/profile');
                })
                .catch((error) => {
                    this.errorMessage = 'Failed to update game: ' + error.message;
                })
                .finally(() => {
                    this.submitting = false;
                });
        },
        cancel() {
            this.$router.push('/profile');
        }
    }
};
</script>

<template>
    <main class="game-form-page">
        <div class="container">
            <div v-if="loading" class="not-found">
                <span>CREATOR STUDIO</span>
                <h1>Loading Game...</h1>
                <p>Fetching game record from Nexus database.</p>
            </div>

            <template v-else-if="!game || !isAllowed">
                <div class="not-found">
                    <span>ACCESS DENIED</span>
                    <h1>Game Not Available</h1>
                    <p>
                        This game does not exist or you do not have
                        permission to edit it.
                    </p>

                    <button type="button" @click="cancel">
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
                                maxlength="255"
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

                    <!-- Minimum System Requirements -->
                    <div class="form-section">
                        <h2>Minimum System Requirements</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Operating System (OS) *</label>
                                    <select v-model="form.os" required>
                                        <option
                                            v-for="item in osOptions"
                                            :key="item.value"
                                            :value="item.value"
                                        >
                                            {{ item.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Memory (RAM) *</label>
                                    <select v-model.number="form.memory" required>
                                        <option
                                            v-for="ram in memoryOptions"
                                            :key="ram"
                                            :value="ram"
                                        >
                                            {{ ram }} GB RAM
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Processor (CPU) *</label>
                                    <select v-model.number="form.cpuId" required>
                                        <option
                                            v-for="item in cpuOptions"
                                            :key="item.id"
                                            :value="item.id"
                                        >
                                            {{ item.manufacturer }} {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Graphics (GPU) *</label>
                                    <select v-model.number="form.gpuId" required>
                                        <option
                                            v-for="item in gpuOptions"
                                            :key="item.id"
                                            :value="item.id"
                                        >
                                            {{ item.manufacturer }} {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Game Details</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="mb-0">Release Date *</label>
                                        <label class="today-check">
                                            <input v-model="isToday" type="checkbox" />
                                            <span>Release date is Today</span>
                                        </label>
                                    </div>

                                    <input
                                        v-model="form.release_date"
                                        type="date"
                                        :disabled="isToday"
                                        :required="!isToday"
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
                            <label>Game Cover Image (Saved to Database)</label>
                            <input
                                ref="imageInput"
                                type="file"
                                accept="image/*"
                                class="file-input"
                                @change="handleImageUpload"
                            />

                            <div v-if="imagePreview" class="image-preview-box">
                                <img :src="imagePreview" alt="Selected game cover preview" />
                                <div class="preview-meta">
                                    <span>{{ imageFileName }}</span>
                                    <button type="button" class="remove-img-btn" @click="clearImage">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Official Website URL *</label>
                            <input
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.com/your-game"
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

                    <p v-if="errorMessage" class="error-message">
                        {{ errorMessage }}
                    </p>

                    <div class="form-actions">
                        <button
                            type="button"
                            class="cancel-btn"
                            :disabled="submitting"
                            @click="cancel"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="submit-btn"
                            :disabled="submitting"
                        >
                            {{ submitting ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </main>
</template>

<style scoped>
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

.form-group input:not([type="checkbox"]),
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 13px;
    color: var(--text);
    background: #0c0e13;
    border: 1px solid var(--line);
    outline: none;
}

.form-group input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--cyan);
}

.form-group select option {
    background: #0c0e13;
}

.today-check {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    color: var(--cyan);
    cursor: pointer;
}

.today-check input[type="checkbox"] {
    width: auto;
    accent-color: var(--cyan);
    cursor: pointer;
}

.file-input {
    cursor: pointer;
    font-size: 12px;
}

.image-preview-box {
    border: 1px solid var(--line);
    background: #0c0e13;
    padding: 12px;
    display: grid;
    gap: 10px;
}

.image-preview-box img {
    width: 100%;
    max-height: 240px;
    object-fit: cover;
    border: 1px solid var(--line);
}

.preview-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    color: var(--muted);
}

.remove-img-btn {
    background: transparent;
    border: 1px solid var(--line);
    color: #ff6969;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.remove-img-btn:hover {
    border-color: #ff6969;
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
    cursor: pointer;
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

.submit-btn:disabled,
.cancel-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
    cursor: pointer;
}

@media (max-width: 576px) {
    .game-form-card {
        padding: 20px;
    }
}
</style>