
import { ref } from 'vue';

function getSavedUser() {
    try {
        return JSON.parse(localStorage.getItem('user')) || null;
    } catch {
        return null;
    }
}

// Shared login status across Vue components
export const currentUser = ref(getSavedUser());

// Save logged-in user
export function saveUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
    currentUser.value = user;
}

// Logout
export function logoutUser() {
    localStorage.removeItem('user');
    currentUser.value = null;
}
