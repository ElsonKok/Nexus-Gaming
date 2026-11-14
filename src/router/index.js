import { createRouter, createWebHistory } from 'vue-router'
import Home from '../components/Home.vue'
import Game from '../components/Game.vue'
import Favourite from '../components/Favourite.vue'
import About from '../components/About.vue'
import Login from '../components/Login.vue'
import Cart from '../components/Cart.vue'
import Checkout from '../components/Checkout.vue'
import GameDetail from '../components/GameDetail.vue'
import Profile from '../components/Profile.vue'
import EditProfile from '../components/EditProfile.vue'
import AddGame from '../components/AddGame.vue'
import EditGame from '../components/EditGame.vue'


const routes = [
    {path: '/', component: Home },
    {path: '/game', component: Game },
    {path: '/favourite', component: Favourite },
    {path: '/about', component: About },
    {path: '/login', component: Login, meta: { hideNav: true } },
    {path: '/cart', component: Cart },
    {path: '/checkout', component: Checkout },
    {path: '/game/:slug', component: GameDetail },  
    {path: '/profile', component: Profile},
    {path: '/profile/edit', component: EditProfile},
    {path: '/games/add', component: AddGame},
    {path: '/games/edit/:id', component: EditGame},
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
})

export default router