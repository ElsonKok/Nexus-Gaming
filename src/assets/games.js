export function toSlug(title) {
    return title
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

export const gamesData = [
    {
        id: 1,
        title: 'Chromatic Drift',
        genre: 'racing',
        platforms: ['pc', 'playstation', 'xbox', 'switch'],
        rating: 4.5,
        year: 2025,
        price: 19.99,
        releaseDate: '2025-02-10',
        img: '',
        liked: false,
        tagline: 'Burn neon across zero-gravity highways.',
        description: 'Race across towering vertical circuits where momentum is everything.'
    },
    {
        id: 2,
        title: 'Aether Protocol',
        genre: 'sci-fi',
        platforms: ['pc', 'playstation'],
        rating: 4.9,
        year: 2025,
        price: 49.99,
        releaseDate: '2025-04-18',
        img: '',
        liked: true,
        tagline: 'Your choices outlive you.',
        description: 'Cross the electric divide in a city where every memory is currency.'
    }
    // ...rest of your games
];