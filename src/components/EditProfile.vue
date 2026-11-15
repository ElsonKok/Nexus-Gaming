<script>
export default {
    name: 'EditProfile',
    data() {
        return {
            profile: null,
            username: '',
            email: '',
            avatarPreview: '',
            avatarData: '',
            avatarChanged: false,
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
            showPasswords: false,
            saving: false,
            errorMessage: '',
            successMessage: ''
        };
    },
    computed: {
        isDeveloper() {
            const role = String(this.profile?.role || '').toLowerCase();
            return role === 'creator' || role === 'developer' || Boolean(this.profile?.developer_id);
        },
        displayRole() {
            if (this.isDeveloper) return 'CREATOR';
            return String(this.profile?.role || 'USER').toUpperCase();
        },
        formattedAvatar() {
            const raw = String(this.avatarPreview || '').trim();
            if (!raw) return '';
            if (raw.startsWith('data:image/') || raw.startsWith('http://') || raw.startsWith('https://')) {
                return raw;
            }
            return `data:image/jpeg;base64,${raw}`;
        }
    },
    mounted() {
        this.loadProfile();
    },
    methods: {
        loadProfile() {
            try {
                this.profile =
                    JSON.parse(localStorage.getItem('nexusUser')) ||
                    JSON.parse(localStorage.getItem('user')) ||
                    null;
            } catch {
                this.profile = null;
            }

            if (this.profile) {
                this.username = this.profile.username || this.profile.displayName || '';
                this.email = this.profile.email || '';
                this.avatarPreview = this.profile.avatar || '';
                this.avatarData = this.profile.avatar || '';
                this.avatarChanged = false;
                this.fetchLatestAccount();
            }
        },
        async fetchLatestAccount() {
            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            const table = this.isDeveloper ? 'developer' : 'user';
            const idCol = this.isDeveloper ? 'developer_id' : 'user_id';
            const accountId = String(
                this.isDeveloper
                    ? this.profile.developer_id
                    : (this.profile.user_id || this.profile.id || '')
            ).trim();

            if (!accountId) return;

            try {
                const res = await fetch(
                    `${baseApi}/${table}/${idCol}/${encodeURIComponent(accountId)}`
                );
                if (!res.ok) return;
                const rows = await res.json();
                const dbUser = Array.isArray(rows) ? rows[0] : rows;
                if (dbUser && !this.avatarChanged && dbUser.avatar) {
                    this.avatarPreview = dbUser.avatar;
                    this.avatarData = dbUser.avatar;
                }
            } catch {
                // Keep cached localStorage avatar on network error
            }
        },
        triggerAvatarPicker() {
            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.click();
            }
        },
        handleAvatarUpload(event) {
            this.errorMessage = '';
            const file = event.target.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'Please select a valid image file (PNG, JPG, WEBP).';
                event.target.value = '';
                return;
            }

            const maxBytes = 2 * 1024 * 1024; // 2 MB
            if (file.size > maxBytes) {
                this.errorMessage = 'Avatar image must be smaller than 2 MB.';
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const dataUrl = e.target?.result || '';
                this.avatarPreview = dataUrl;
                this.avatarData = dataUrl;
                this.avatarChanged = true;
            };
            reader.onerror = () => {
                this.errorMessage = 'Failed to read the selected avatar image.';
            };
            reader.readAsDataURL(file);
        },
        removeAvatar() {
            this.avatarPreview = '';
            this.avatarData = '';
            this.avatarChanged = true;
            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },
        async saveProfile() {
            this.errorMessage = '';
            this.successMessage = '';

            if (!this.profile) {
                this.$router.push('/login');
                return;
            }

            if (!this.username.trim() || !this.email.trim()) {
                this.errorMessage = 'Please complete all required fields.';
                return;
            }

            const wantsPasswordChange =
                Boolean(this.currentPassword) ||
                Boolean(this.newPassword) ||
                Boolean(this.confirmPassword);

            if (wantsPasswordChange) {
                if (!this.currentPassword || !this.newPassword || !this.confirmPassword) {
                    this.errorMessage = 'Please fill in current, new, and confirm password fields.';
                    return;
                }
                if (this.newPassword.length < 6) {
                    this.errorMessage = 'New password must be at least 6 characters.';
                    return;
                }
                if (this.newPassword !== this.confirmPassword) {
                    this.errorMessage = 'New password and confirmation do not match.';
                    return;
                }
                if (this.currentPassword === this.newPassword) {
                    this.errorMessage = 'New password must be different from your current password.';
                    return;
                }
            }

            const baseApi = `${import.meta.env.BASE_URL}resources/apis.php`;
            const table = this.isDeveloper ? 'developer' : 'user';
            const idCol = this.isDeveloper ? 'developer_id' : 'user_id';
            const accountId = String(
                this.isDeveloper
                    ? this.profile.developer_id
                    : (this.profile.user_id || this.profile.id || '')
            ).trim();

            if (!accountId) {
                this.errorMessage = 'Unable to identify your account ID. Please log in again.';
                return;
            }

            this.saving = true;

            try {
                if (wantsPasswordChange) {
                    const checkRes = await fetch(
                        `${baseApi}/${table}/${idCol}/${encodeURIComponent(accountId)}`
                    );
                    if (!checkRes.ok) {
                        throw new Error(`HTTP error: ${checkRes.status}`);
                    }
                    const rows = await checkRes.json();
                    const dbUser = Array.isArray(rows) ? rows[0] : rows;

                    if (!dbUser || String(dbUser.password ?? '') !== this.currentPassword) {
                        this.errorMessage = 'Current password is incorrect.';
                        this.saving = false;
                        return;
                    }
                }

                const payload = {
                    username: this.username.trim(),
                    email: this.email.trim()
                };

                if (this.avatarChanged) {
                    payload.avatar = this.avatarData;
                }

                if (wantsPasswordChange) {
                    payload.password = this.newPassword;
                }

                const updateRes = await fetch(
                    `${baseApi}/${table}/${idCol}/${encodeURIComponent(accountId)}`,
                    {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    }
                );

                if (!updateRes.ok) {
                    throw new Error(`HTTP error: ${updateRes.status}`);
                }

                const data = await updateRes.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                const updatedProfile = {
                    ...this.profile,
                    username: payload.username,
                    email: payload.email,
                    avatar: this.avatarChanged ? this.avatarData : (this.profile.avatar || '')
                };

                if (localStorage.getItem('nexusUser')) {
                    localStorage.setItem('nexusUser', JSON.stringify(updatedProfile));
                }
                if (localStorage.getItem('user')) {
                    localStorage.setItem('user', JSON.stringify(updatedProfile));
                }
                window.dispatchEvent(new Event('auth-changed'));

                this.currentPassword = '';
                this.newPassword = '';
                this.confirmPassword = '';
                this.avatarChanged = false;

                this.successMessage = wantsPasswordChange
                    ? 'Profile and password updated successfully!'
                    : 'Profile updated successfully!';

                window.dispatchEvent(
                    new CustomEvent('show-toast', {
                        detail: { message: this.successMessage, type: 'success' }
                    })
                );

                setTimeout(() => {
                    this.$router.push('/profile');
                }, 700);
            } catch (err) {
                this.errorMessage = 'Could not update profile: ' + err.message;
            } finally {
                this.saving = false;
            }
        },
        cancelEdit() {
            this.$router.push('/profile');
        }
    }
};
</script>

<template>
    <main class="edit-profile-page">
        <div class="container">
            <div class="page-heading">
                <span class="eyebrow">ACCOUNT SETTINGS</span>
                <h1>Edit Profile</h1>
                <p>Update your avatar, personal information, and password on Nexus.</p>
            </div>

            <div v-if="!profile" class="edit-profile-card text-center">
                <p class="mb-3">Please sign in to edit your profile.</p>
                <router-link to="/login" class="save-btn text-decoration-none d-inline-block">
                    Go to Login
                </router-link>
            </div>

            <div v-else class="edit-profile-card">
                <div class="profile-preview">
                    <div
                        class="avatar"
                        title="Click to change avatar"
                        @click="triggerAvatarPicker"
                    >
                        <img
                            v-if="formattedAvatar"
                            :src="formattedAvatar"
                            :alt="username || 'Avatar'"
                        />
                        <span v-else>{{ username.charAt(0).toUpperCase() || 'U' }}</span>
                    </div>

                    <div class="flex-grow-1">
                        <h3>{{ username || 'Your Name' }}</h3>
                        <span class="role-tag">{{ displayRole }}</span>

                        <div class="avatar-actions">
                            <input
                                ref="avatarInput"
                                type="file"
                                accept="image/*"
                                class="d-none"
                                @change="handleAvatarUpload"
                            />
                            <button
                                type="button"
                                class="avatar-btn"
                                @click="triggerAvatarPicker"
                            >
                                {{ formattedAvatar ? 'Change Avatar' : 'Upload Avatar' }}
                            </button>
                            <button
                                v-if="formattedAvatar"
                                type="button"
                                class="avatar-remove-btn"
                                @click="removeAvatar"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-divider"></div>

                <form @submit.prevent="saveProfile">
                    <h2 class="section-title">Profile Information</h2>

                    <div class="form-group">
                        <label>Display Name *</label>
                        <input
                            v-model="username"
                            type="text"
                            placeholder="Enter your display name"
                            maxlength="40"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input
                            v-model="email"
                            type="email"
                            placeholder="player@nexus.gg"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label>Account Role</label>
                        <input
                            :value="displayRole"
                            type="text"
                            disabled
                        />
                        <small>Your account role cannot be changed here.</small>
                    </div>

                    <div class="form-divider"></div>

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="section-title mb-0">Change Password</h2>
                        <label class="toggle-password">
                            <input v-model="showPasswords" type="checkbox" />
                            <span>Show passwords</span>
                        </label>
                    </div>
                    <p class="section-hint">Leave blank if you do not want to change your password.</p>

                    <div class="form-group">
                        <label>Current Password</label>
                        <input
                            v-model="currentPassword"
                            :type="showPasswords ? 'text' : 'password'"
                            placeholder="Enter current password"
                            autocomplete="current-password"
                        />
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>New Password</label>
                                <input
                                    v-model="newPassword"
                                    :type="showPasswords ? 'text' : 'password'"
                                    placeholder="Minimum 6 characters"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Confirm New Password</label>
                                <input
                                    v-model="confirmPassword"
                                    :type="showPasswords ? 'text' : 'password'"
                                    placeholder="Re-enter new password"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>
                    </div>

                    <p v-if="errorMessage" class="error-message mt-3">
                        {{ errorMessage }}
                    </p>
                    <p v-if="successMessage" class="success-message mt-3">
                        {{ successMessage }}
                    </p>

                    <div class="form-actions">
                        <button
                            type="button"
                            class="cancel-btn"
                            :disabled="saving"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="save-btn"
                            :disabled="saving"
                        >
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</template>

<style scoped>
.edit-profile-page {
    min-height: 100vh;
    padding: 65px 20px;
    background: var(--bg);
    color: var(--text);
}

.container {
    max-width: 800px;
}

.page-heading {
    margin-bottom: 30px;
}

.eyebrow {
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

.edit-profile-card {
    padding: 30px;
    background: var(--panel);
    border: 1px solid var(--line);
}

.profile-preview {
    display: flex;
    align-items: center;
    gap: 18px;
}

.avatar {
    width: 78px;
    height: 78px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #171b26;
    border: 1px solid var(--cyan);
    color: var(--cyan);
    font-size: 28px;
    font-weight: 800;
    overflow: hidden;
    flex-shrink: 0;
    cursor: pointer;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-preview h3 {
    font-size: 18px;
    margin: 0 0 4px;
}

.role-tag {
    color: var(--cyan);
    font-size: 10px;
    letter-spacing: 0.12em;
    display: block;
}

.avatar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.avatar-btn,
.avatar-remove-btn {
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid var(--line);
    background: #0c0e13;
    cursor: pointer;
}

.avatar-btn {
    color: var(--cyan);
    border-color: rgba(0, 242, 254, 0.4);
}

.avatar-btn:hover {
    border-color: var(--cyan);
}

.avatar-remove-btn {
    color: #ff6969;
}

.avatar-remove-btn:hover {
    border-color: #ff6969;
}

.form-divider {
    height: 1px;
    background: var(--line);
    margin: 28px 0;
}

.section-title {
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 18px;
}

.section-hint {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 18px;
}

.toggle-password {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--muted);
    cursor: pointer;
}

.toggle-password input[type="checkbox"] {
    width: auto;
    accent-color: var(--cyan);
    cursor: pointer;
}

.form-group {
    display: grid;
    gap: 9px;
    margin-bottom: 22px;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
}

.form-group input:not([type="checkbox"]) {
    width: 100%;
    padding: 13px;
    color: var(--text);
    background: #0c0e13;
    border: 1px solid var(--line);
    outline: none;
}

.form-group input:focus {
    border-color: var(--cyan);
}

.form-group input:disabled {
    color: var(--muted);
    cursor: not-allowed;
}

.form-group small {
    color: var(--muted);
    font-size: 11px;
}

.error-message {
    color: #ff6969;
    font-size: 12px;
}

.success-message {
    color: #55f5ae;
    font-size: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 30px;
}

.cancel-btn,
.save-btn {
    padding: 12px 20px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--line);
    cursor: pointer;
}

.cancel-btn {
    color: var(--text);
    background: transparent;
}

.save-btn {
    color: #061014;
    background: var(--cyan);
    border-color: var(--cyan);
}

.cancel-btn:disabled,
.save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 576px) {
    .edit-profile-card {
        padding: 20px;
    }

    .page-heading h1 {
        font-size: 30px;
    }
}
</style>