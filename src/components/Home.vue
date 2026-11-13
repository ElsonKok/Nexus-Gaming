<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// API endpoint
const API_URL =
  'https://www.freetogame.com/api/games?platform=all&sort-by=popularity'

// Reactive data
const games = ref([])
const loading = ref(true)
const error = ref('')
const searchQuery = ref('')

// Fetch games from FreeToGame
const fetchGames = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch(API_URL)

    if (!response.ok) {
      throw new Error(`API request failed: ${response.status}`)
    }

    const data = await response.json()

    if (!Array.isArray(data)) {
      throw new Error('Unexpected API response format')
    }

    games.value = data

  } catch (err) {
    console.error('Error fetching games:', err)
    error.value = 'Unable to load games. Please try again later.'

  } finally {
    loading.value = false
  }
}

// Display first 4 popular games
const featuredGames = computed(() => {
  return games.value.slice(0, 4)
})

// Display 4 recently released games
const latestGames = computed(() => {
  return [...games.value]
    .sort((a, b) => {
      return new Date(b.release_date) - new Date(a.release_date)
    })
    .slice(0, 4)
})

// Search games
const searchGames = () => {
  router.push({
    path: '/game',
    query: searchQuery.value
      ? { search: searchQuery.value }
      : {}
  })
}

// Browse games by category
const browseCategory = (category) => {
  router.push({
    path: '/game',
    query: { category: category }
  })
}

// Open game details through the Game page
const viewGame = (game) => {
  router.push({
    path: '/game',
    query: { id: game.id }
  })
}

// Fetch data when Home page is mounted
onMounted(() => {
  fetchGames()
})
</script>


<template>
  <main class="home-page">

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container hero-container">
        <div class="row align-items-center">

          <div class="col-lg-7 hero-content">
            <span class="hero-tag">
              YOUR NEXT ADVENTURE STARTS HERE
            </span>

            <h1>
              Discover Your Next
              <span>Gaming Adventure.</span>
            </h1>

            <p>
              Explore thousands of games, discover new adventures
              and find your next favourite game at the best prices.
            </p>

            <form class="hero-search" @submit.prevent="searchGames">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search for your favourite games..."
                aria-label="Search games"
              />

              <button type="submit">
                <i class="bi bi-search"></i>
                Search
              </button>
            </form>

            <div class="hero-actions">
              <router-link to="/game" class="btn-primary-custom">
                Explore Games
                <i class="bi bi-arrow-right"></i>
              </router-link>

              <router-link to="/about" class="btn-secondary-custom">
                Learn More
              </router-link>
            </div>
          </div>

          <div class="col-lg-5 hero-visual">
            <div class="hero-image-wrapper">
              <img
                src="/filter.svg"
                alt="Featured gaming artwork"
                class="hero-image"
              />

              <div class="hero-floating-card">
                <span class="floating-icon">🎮</span>
                <div>
                  <strong>Discover More</strong>
                  <small>Your next favourite game awaits.</small>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>


    <!-- Quick Benefits -->
    <section class="benefits-section">
      <div class="container">
        <div class="row g-3">

          <div class="col-12 col-md-4">
            <div class="benefit-item">
              <i class="bi bi-controller"></i>
              <div>
                <strong>Huge Game Collection</strong>
                <small>Discover games from different genres.</small>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="benefit-item">
              <i class="bi bi-tags"></i>
              <div>
                <strong>Great Deals</strong>
                <small>Find games at attractive prices.</small>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="benefit-item">
              <i class="bi bi-heart"></i>
              <div>
                <strong>Your Favourite Games</strong>
                <small>Save games to your favourite list.</small>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>


    <!-- Featured Games -->
    <section class="content-section">
    <div class="container">

        <div class="section-heading">
        <div>
            <span class="section-subtitle">HANDPICKED FOR YOU</span>
            <h2>Popular Games</h2>
            <p>Discover some of the most popular free-to-play games.</p>
        </div>

        <router-link to="/game" class="view-all">
            View All Games
            <i class="bi bi-arrow-right"></i>
        </router-link>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-info"></div>
        <p class="mt-3 text-secondary">Loading games...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-center py-5">
        <p class="text-danger">{{ error }}</p>

        <button
            class="btn btn-info"
            @click="fetchGames"
        >
            Try Again
        </button>
        </div>

        <!-- Game Cards -->
        <div v-else class="row g-4">

        <div
            v-for="game in featuredGames"
            :key="game.id"
            class="col-12 col-sm-6 col-lg-3"
        >
            <div class="game-card">

            <img
                :src="game.thumbnail"
                :alt="game.title"
                class="game-thumbnail"
            />

            <div class="game-info">

                <h5>{{ game.title }}</h5>

                <span class="game-category">
                {{ game.genre }}
                </span>

                <p class="game-description">
                {{ game.short_description }}
                </p>

                <div class="game-platform">
                <i class="bi bi-pc-display"></i>
                {{ game.platform }}
                </div>

                <button
                class="game-link"
                @click="viewGame(game)"
                >
                View Details
                <i class="bi bi-arrow-right"></i>
                </button>

            </div>
            </div>
        </div>

        </div>

    </div>
    </section>


    <!-- Browse Categories -->
    <section class="category-section">
    <div class="container">

        <div class="section-heading">
        <div>
            <span class="section-subtitle">FIND YOUR PLAYSTYLE</span>
            <h2>Browse Categories</h2>
            <p>Find games that match your interests.</p>
        </div>
        </div>

        <div class="row g-3">

        <div class="col-6 col-md-3">
            <button
            class="category-card category-action"
            @click="browseCategory('shooter')"
            >
            <i class="bi bi-crosshair"></i>
            <h5>Shooter</h5>
            <span>Test your skills</span>
            </button>
        </div>

        <div class="col-6 col-md-3">
            <button
            class="category-card category-adventure"
            @click="browseCategory('mmorpg')"
            >
            <i class="bi bi-globe"></i>
            <h5>MMORPG</h5>
            <span>Explore massive worlds</span>
            </button>
        </div>

        <div class="col-6 col-md-3">
            <button
            class="category-card category-rpg"
            @click="browseCategory('strategy')"
            >
            <i class="bi bi-lightbulb"></i>
            <h5>Strategy</h5>
            <span>Plan your next move</span>
            </button>
        </div>

        <div class="col-6 col-md-3">
            <button
            class="category-card category-racing"
            @click="browseCategory('racing')"
            >
            <i class="bi bi-car-front-fill"></i>
            <h5>Racing</h5>
            <span>Feel the excitement</span>
            </button>
        </div>

        </div>
    </div>
    </section>




    <!-- Recently Released -->
    <section class="offers-section">
    <div class="container">

        <div class="section-heading">
        <div>
            <span class="section-subtitle">FRESH FROM THE GAME WORLD</span>
            <h2>Recently Released</h2>
            <p>Explore some of the latest additions to our game collection.</p>
        </div>

        <router-link to="/game" class="view-all">
            Explore More
            <i class="bi bi-arrow-right"></i>
        </router-link>
        </div>

        <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-info"></div>
        <p class="mt-3 text-secondary">Loading latest games...</p>
        </div>

        <div v-else-if="!error" class="row g-4">

        <div
            v-for="game in latestGames"
            :key="game.id"
            class="col-12 col-md-6"
        >
            <div class="offer-card">

            <img
                :src="game.thumbnail"
                :alt="game.title"
                class="offer-thumbnail"
            />

            <div class="offer-details">

                <div>
                <span class="offer-label">NEW RELEASE</span>

                <h5>{{ game.title }}</h5>

                <span class="game-category">
                    {{ game.genre }}
                </span>
                </div>

                <div class="release-date">
                <i class="bi bi-calendar-event"></i>
                {{ game.release_date }}
                </div>

                <button
                class="offer-button"
                @click="viewGame(game)"
                >
                View Details
                </button>

            </div>
            </div>
        </div>

        </div>

    </div>
    </section>



    <!-- Bottom Call To Action -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <span class="section-subtitle">JOIN THE COMMUNITY</span>

          <h2>
            Ready to Start Your
            <span>Next Adventure?</span>
          </h2>

          <p>
            Create an account and start discovering games
            that you will love.
          </p>

          <router-link to="/register" class="btn-primary-custom">
            Get Started
            <i class="bi bi-arrow-right"></i>
          </router-link>
        </div>
      </div>
    </section>

    <div class="api-attribution">
    Game information provided by
    <a
        href="https://www.freetogame.com/"
        target="_blank"
        rel="noopener noreferrer"
    >
        FreeToGame
    </a>
    </div>

  </main>
</template>

<style scoped>
/* General */
.home-page {
  background: #0b0e14;
  color: #f7f8fb;
  min-height: 100vh;
}

.container {
  max-width: 1200px;
}

.section-subtitle {
  color: #00dce8;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 30px;
}

.section-heading h2 {
  font-size: 28px;
  font-weight: 800;
  margin: 8px 0;
}

.section-heading p {
  color: #9299a9;
  font-size: 14px;
  margin: 0;
}

.view-all {
  color: #00dce8;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.view-all:hover {
  color: white;
}

/* Hero */
.hero-section {
  padding: 75px 0;
  background:
    radial-gradient(ellipse at 80% 40%, #10343d 0%, transparent 55%),
    linear-gradient(120deg, #10141e, #0b0e14);
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 2;
}

.hero-tag {
  display: inline-block;
  color: #00dce8;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 2px;
  margin-bottom: 20px;
}

.hero-content h1 {
  font-size: clamp(38px, 5vw, 64px);
  line-height: 1.1;
  font-weight: 900;
  max-width: 650px;
  margin-bottom: 22px;
}

.hero-content h1 span,
.cta-content h2 span {
  color: #00dce8;
}

.hero-content p {
  color: #a2a8b6;
  font-size: 15px;
  line-height: 1.8;
  max-width: 510px;
  margin-bottom: 28px;
}

.hero-search {
  display: flex;
  max-width: 510px;
  padding: 5px;
  background: #171c27;
  border: 1px solid #303847;
  border-radius: 7px;
}

.hero-search input {
  flex: 1;
  min-width: 0;
  background: transparent;
  border: none;
  outline: none;
  padding: 10px;
  color: white;
  font-size: 13px;
}

.hero-search input::placeholder {
  color: #747d8c;
}

.hero-search button {
  background: #00dce8;
  color: #061014;
  border: none;
  border-radius: 5px;
  padding: 10px 17px;
  font-size: 13px;
  font-weight: 800;
}

.hero-search button:hover,
.btn-primary-custom:hover {
  background: #7df7ff;
  color: #061014;
}

.hero-actions {
  display: flex;
  gap: 12px;
  margin-top: 25px;
  flex-wrap: wrap;
}

.btn-primary-custom,
.btn-secondary-custom {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  text-decoration: none;
  padding: 13px 20px;
  border-radius: 5px;
  font-size: 13px;
  font-weight: 800;
  transition: 0.2s;
}

.btn-primary-custom {
  background: #00dce8;
  color: #061014;
}

.btn-secondary-custom {
  border: 1px solid #394352;
  color: white;
}

.btn-secondary-custom:hover {
  border-color: #00dce8;
  color: #00dce8;
}

.hero-visual {
  position: relative;
  margin-top: 30px;
}

.hero-image-wrapper {
  position: relative;
  max-width: 450px;
  margin: auto;
}

.hero-image {
  width: 100%;
  height: 360px;
  object-fit: cover;
  border-radius: 12px;
  box-shadow: 0 15px 50px #0008;
}

.hero-floating-card {
  position: absolute;
  bottom: 20px;
  left: -25px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  background: #171e2b;
  border: 1px solid #344454;
  border-radius: 8px;
  box-shadow: 0 10px 30px #0008;
}

.floating-icon {
  font-size: 28px;
}

.hero-floating-card strong,
.hero-floating-card small {
  display: block;
}

.hero-floating-card strong {
  font-size: 13px;
}

.hero-floating-card small {
  color: #9299a9;
  font-size: 10px;
  margin-top: 4px;
}

/* Benefits */
.benefits-section {
  padding: 25px 0;
  background: #111620;
  border-top: 1px solid #242b37;
  border-bottom: 1px solid #242b37;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 10px;
}

.benefit-item > i {
  color: #00dce8;
  font-size: 27px;
}

.benefit-item strong,
.benefit-item small {
  display: block;
}

.benefit-item strong {
  font-size: 13px;
}

.benefit-item small {
  color: #8992a2;
  font-size: 11px;
  margin-top: 4px;
}

/* Main content sections */
.content-section,
.category-section,
.offers-section {
  padding: 65px 0;
}

/* Game cards */
.game-card {
  height: 100%;
  overflow: hidden;
  background: #151a24;
  border: 1px solid #272f3d;
  border-radius: 8px;
  transition: 0.25s;
}

.game-card:hover {
  transform: translateY(-6px);
  border-color: #00dce8;
  box-shadow: 0 10px 30px #00dce81a;
}

.game-cover {
  position: relative;
  height: 220px;
  display: flex;
  align-items: end;
  padding: 18px;
  overflow: hidden;
}

.game-cover::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(transparent 20%, #0009);
}

.game-cover-title {
  position: relative;
  z-index: 1;
  font-size: 23px;
  font-weight: 900;
  color: white;
  text-shadow: 0 2px 8px #000;
}

.discount-tag {
  position: absolute;
  z-index: 2;
  top: 12px;
  left: 12px;
  padding: 6px 9px;
  background: #00dce8;
  color: #061014;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 900;
}

.game-info {
  padding: 17px;
}

.game-info h5 {
  font-size: 15px;
  font-weight: 800;
  margin-bottom: 7px;
}

.game-category {
  color: #929aaa;
  font-size: 11px;
}

.game-price {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 17px;
  font-size: 14px;
}

.game-price strong {
  color: #00dce8;
}

.old-price {
  color: #7e8795;
  text-decoration: line-through;
  font-size: 12px;
}

.game-link {
  display: block;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 1px solid #303744;
  text-decoration: none;
  color: #d7dce5;
  font-size: 12px;
  font-weight: 700;
}

.game-link:hover {
  color: #00dce8;
}

/* Categories */
.category-section {
  background: #111620;
}

.category-card {
  width: 100%;
  height: 160px;
  padding: 20px;
  border: 1px solid #303847;
  border-radius: 8px;
  color: white;
  text-align: left;
  transition: 0.25s;
}

.category-card:hover {
  transform: translateY(-5px);
  border-color: #00dce8;
}

.category-card i {
  font-size: 28px;
  color: #00dce8;
}

.category-card h5 {
  font-size: 16px;
  font-weight: 800;
  margin: 12px 0 5px;
}

.category-card span {
  color: #b1b8c4;
  font-size: 11px;
}

.category-action {
  background: linear-gradient(135deg, #24213e, #171a29);
}

.category-adventure {
  background: linear-gradient(135deg, #153d3b, #171a29);
}

.category-rpg {
  background: linear-gradient(135deg, #402b25, #171a29);
}

.category-racing {
  background: linear-gradient(135deg, #243d53, #171a29);
}

/* Special offers */
.offer-card {
  display: flex;
  height: 100%;
  min-height: 200px;
  overflow: hidden;
  background: #151a24;
  border: 1px solid #2b3441;
  border-radius: 8px;
}

.offer-art {
  position: relative;
  width: 45%;
  min-height: 200px;
  display: flex;
  align-items: end;
  padding: 15px;
}

.offer-discount {
  position: absolute;
  top: 12px;
  left: 12px;
  background: #00dce8;
  color: #061014;
  padding: 6px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 900;
}

.offer-game-name {
  font-size: 19px;
  font-weight: 900;
  text-shadow: 0 2px 8px black;
}

.offer-details {
  flex: 1;
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 12px;
}

.offer-label {
  color: #00dce8;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1px;
}

.offer-details h5 {
  font-size: 15px;
  font-weight: 800;
  margin: 7px 0;
}

.offer-price {
  display: flex;
  gap: 10px;
  align-items: center;
}

.offer-price small {
  color: #929aaa;
  text-decoration: line-through;
}

.offer-price strong {
  color: #00dce8;
  font-size: 15px;
}

.offer-button {
  display: inline-block;
  align-self: start;
  background: #222c39;
  color: white;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 800;
}

.offer-button:hover {
  background: #00dce8;
  color: #061014;
}

/* CTA */
.cta-section {
  padding: 65px 0;
  background:
    radial-gradient(ellipse at center, #10404a, transparent 70%),
    #111620;
  border-top: 1px solid #29323e;
  text-align: center;
}

.cta-content h2 {
  font-size: clamp(28px, 4vw, 40px);
  font-weight: 900;
  margin: 15px 0;
}

.cta-content p {
  color: #a2a8b6;
  font-size: 14px;
  margin-bottom: 25px;
}

@media (max-width: 767px) {
  .hero-section {
    padding: 50px 0;
  }

  .hero-content h1 {
    font-size: 40px;
  }

  .hero-visual {
    margin-top: 45px;
  }

  .hero-image {
    height: 260px;
  }

  .hero-floating-card {
    left: 5px;
    bottom: 10px;
  }

  .section-heading {
    align-items: start;
  }

  .section-heading h2 {
    font-size: 23px;
  }

  .view-all {
    font-size: 11px;
  }

  .content-section,
  .category-section,
  .offers-section {
    padding: 45px 0;
  }

  .offer-card {
    min-height: 180px;
  }

  .offer-art {
    min-height: 180px;
  }
}

@media (max-width: 400px) {
  .hero-search button {
    padding: 10px;
  }

  .hero-search input {
    font-size: 11px;
  }

  .section-heading {
    flex-direction: column;
  }

  .offer-art {
    width: 40%;
  }

  .offer-details {
    padding: 12px;
  }
}

/* Actual API game thumbnails */
.game-thumbnail {
  width: 100%;
  height: 180px;
  object-fit: cover;
  display: block;
}

.game-description {
  color: #929aaa;
  font-size: 11px;
  line-height: 1.6;
  margin-top: 12px;

  /* Limit description to 3 lines */
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 53px;
}

.game-platform {
  color: #929aaa;
  font-size: 11px;
  margin-top: 10px;
}

.game-platform i {
  color: #00dce8;
  margin-right: 5px;
}

.game-link {
  width: 100%;
  cursor: pointer;
  background: transparent;
  text-align: left;
}

.game-card {
  overflow: hidden;
}

/* Recently released thumbnails */
.offer-thumbnail {
  width: 45%;
  min-height: 200px;
  object-fit: cover;
}

.release-date {
  color: #929aaa;
  font-size: 11px;
}

.release-date i {
  color: #00dce8;
  margin-right: 5px;
}

.offer-button {
  border: none;
  cursor: pointer;
}

@media (max-width: 767px) {
  .game-thumbnail {
    height: 160px;
  }

  .offer-thumbnail {
    width: 40%;
  }
}

.api-attribution {
  text-align: center;
  padding: 15px;
  color: #929aaa;
  font-size: 11px;
  background: #0b0e14;
}

.api-attribution a {
  color: #00dce8;
  text-decoration: none;
}

.api-attribution a:hover {
  text-decoration: underline;
}
</style>
