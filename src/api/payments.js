/*
 * Payments API: reads the `payment` table through resources/apis.php.
 *
 *   GET apis.php/payment/user_id/U0001   -> every payment row of that user
 *
 * Only the logged-in user's rows are requested (never the whole table).
 */
const API = (import.meta.env.VITE_API_URL ?? `${import.meta.env.BASE_URL}resources/apis.php`).replace(/\/$/, '')

// ids of the games a user has paid for: oldest purchase first, no duplicates
// (the same game can appear in several payment rows)
export async function getPurchasedGameIds(userId) {
    const id = String(userId || '').trim()
    if (!id) return []

    const res = await fetch(`${API}/payment/user_id/${encodeURIComponent(id)}`)
    if (!res.ok) throw new Error(`Request failed (${res.status})`)

    const text = await res.text()
    let rows
    try {
        rows = text ? JSON.parse(text) : []
    } catch (e) {
        throw new Error('The server sent an unexpected reply.')
    }
    if (!Array.isArray(rows)) return []

    rows.sort(
        (a, b) =>
            String(a.date).localeCompare(String(b.date)) ||
            String(a.payment_id).localeCompare(String(b.payment_id))
    )

    const ids = []
    for (const row of rows) {
        const gameId = String(row.game_id).trim()
        const i = ids.indexOf(gameId)
        if (i !== -1) ids.splice(i, 1) // keep only the latest purchase of each game
        ids.push(gameId)
    }
    return ids
}