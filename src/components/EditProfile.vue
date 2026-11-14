
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const demoProfile = {
    id: 1,
    username: 'Alex Morgan',
    email: 'alex@nexus.com',
    role: 'creator',
    joinedDate: '2024-01-15',
    purchasedGameIds: [1],
    creatorGameIds: [1, 2]
};

function loadProfile() {
    try {
        return JSON.parse(localStorage.getItem('user')) || demoProfile;
    } catch {
        return demoProfile;
    }
}

const profile = ref(loadProfile());

const username = ref(profile.value.username || profile.value.displayName || '');
const email = ref(profile.value.email || '');
const message = ref('');

function saveProfile() {
    if (!username.value.trim() || !email.value.trim()) {
        message.value = 'Please complete all required fields.';
        return;
    }

    const updatedProfile = {
        ...profile.value,
        username: username.value.trim(),
        email: email.value.trim()
    };

    localStorage.setItem('user', JSON.stringify(updatedProfile));

    message.value = 'Profile updated successfully!';

    setTimeout(() => {
        router.push('/profile');
    }, 700);
}

function cancelEdit() {
    router.push('/profile');
}
</script>

<template>
    <main class="edit-profile-page">

        <div class="container">

            <div class="page-heading">
                <span class="eyebrow">ACCOUNT SETTINGS</span>
                <h1>Edit Profile</h1>
                <p>Update your personal information on Nexus.</p>
            </div>

            <div class="edit-profile-card">

                <div class="profile-preview">
                    <div class="avatar">
                        {{ username.charAt(0).toUpperCase() || 'U' }}
                    </div>

                    <div>
                        <h3>{{ username || 'Your Name' }}</h3>
                        <span>
                            {{ profile.role?.toUpperCase() || 'USER' }}
                        </span>
                    </div>
                </div>

                <div class="form-divider"></div>

                <form @submit.prevent="saveProfile">

                    <div class="form-group">
                        <label>Display Name</label>

                        <input
                            v-model="username"
                            type="text"
                            placeholder="Enter your display name"
                            maxlength="40"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>

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
                            :value="profile.role || 'user'"
                            type="text"
                            disabled
                        />

                        <small>
                            Your account role cannot be changed here.
                        </small>
                    </div>

                    <p v-if="message" class="success-message">
                        {{ message }}
                    </p>

                    <div class="form-actions">

                        <button
                            type="button"
                            class="cancel-btn"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>

                        <button type="submit" class="save-btn">
                            Save Changes
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
    width: 65px;
    height: 65px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #171b26;
    border: 1px solid var(--cyan);
    color: var(--cyan);
    font-size: 25px;
    font-weight: 800;
}

.profile-preview h3 {
    font-size: 18px;
    margin: 0 0 5px;
}

.profile-preview span {
    color: var(--cyan);
    font-size: 10px;
    letter-spacing: 0.12em;
}

.form-divider {
    height: 1px;
    background: var(--line);
    margin: 28px 0;
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

.form-group input {
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

@media (max-width: 576px) {
    .edit-profile-card {
        padding: 20px;
    }

    .page-heading h1 {
        font-size: 30px;
    }
}
</style>
