import { gamesData } from '../assets/games.js'

// TEMPORARY: returns the local sample data.
// When the PHP REST API is ready, replace the body with:
//   const res = await fetch(`${API_URL}/games`)
//   if (!res.ok) throw new Error('Failed to load games')
//   return await res.json()
// Home.vue (and Game.vue, if you use it there) will not need to change.
export async function getGames() {
  return gamesData
}