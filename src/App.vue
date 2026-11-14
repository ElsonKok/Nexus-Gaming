<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();
</script>

<template>
  <nav v-if="!route.meta.hideNav" class="navbar cus-navbar">
    <a href="/">
      <img src="/logo.png" alt="Logo" class="logo" width="120" height="50"/>
    </a>
    <div class="d-flex gap-3">
      <router-link to="/" class="nav-link nav-links" active-class="active">Home</router-link>
      <router-link to="/game" class="nav-link nav-links" active-class="active">Game</router-link>
      <router-link to="/favourite" class="nav-link nav-links" active-class="active">Favourite</router-link>
      <router-link to="/about" class="nav-link nav-links" active-class="active">About</router-link>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a class="icon-link cart-button d-flex justify-content-center" href="./cart">
        <img src="/cart.svg" alt="User" class="user-icon" width="19" height="19"/>
        <!-- <span>1</span> -->
      </a>
      <router-link to="/login" class="text-decoration-none text-white login">Login</router-link>
      <router-link :to="{ path: '/login', query: { tab: 'register' } }" class="text-decoration-none register">Register</router-link>
    </div>
  </nav>
  <router-view></router-view>
</template>

<style scope>
nav {
  border-bottom: 1px solid #ffffff12;
}

.navbar.cus-navbar{
  padding: 10px 35px;
}

.nav-link.nav-links {
  color: var(--muted);
  font-size: 15px;
  font-weight: 500;
}

.nav-link.nav-links.active, .nav-link.nav-links:hover {
  color: var(--text);
}

.cart-button {
  border: 1px solid #252936;
  background-color: #101218;
  width: 38px;
  height: 38px;
}

.icon-link {
    position: relative;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid #252936;
    background: #101218;
    color: #f7f8fb;
}

.icon-link span {
  position: absolute;
  top: 0;
  right: 0;
  transform: translate(50%, -50%);
  background-color: #00f2fe;
  color: #000;
  font-weight: bold;
  padding: 2px 5px;
  border-radius: 100%;
  font-size: 9px;
}

.login {
  font-size: 13px;
}

.register {
  background-color: #00f2fe;
  padding: 10px 18px;
  color: #000;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 0 22px rgba(0, 242, 254, 0.5);
}
</style>
