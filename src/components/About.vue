<script setup>
import { ref } from 'vue';

const stats = [
    { value: '2.4M', label: 'Players connected' },
    { value: '1,800+', label: 'Curated worlds' },
    { value: '92', label: 'Countries reached' },
    { value: '4.9/5', label: 'Community rating' }
];

const timeline = [
    { year: '2018', title: 'A room, two desks', text: 'Nexus began as a weekly list of games we could not stop talking about.' },
    { year: '2021', title: 'The community arrives', text: '100,000 players joined to discover hidden gems and back emerging studios.' },
    { year: '2025', title: 'A new kind of platform', text: 'Today, discovery, commerce, and community share one connected world.' }
];

const team = [
    { name: 'Kok Zhong Bo', role: 'Team Lead', img: '' },
    { name: 'Looi Yi Sean', role: 'Team Lead', img: '' },
    { name: 'Nyin Seng Yik', role: 'Team Lead', img: '' }
];

const faqs = [
    {
        q: 'What makes Nexus different?',
        a: 'Nexus stands out by combining discovery, commerce, and community into a single, connected platform. We focus on curating unique experiences and supporting emerging developers.'
    },
    {
        q: 'Can I play across devices?',
        a: 'Your collection follows your Nexus profile across supported desktop and console platforms.'
    },
    {
        q: 'How do creators join?',
        a: 'Creator access is reviewed on a rolling basis. Apply through your profile with a playable build and studio details.'
    }
];

const openFaq = ref(0);
const toggleFaq = (i) => {
    openFaq.value = openFaq.value === i ? null : i;
};
</script>

<template>
    <header class="about-hero text-center d-grid">
        <span class="badge">OUR MISSION</span>
        <h1>Games are more than<br><em>something we play</em></h1>
        <p>They are places we remember. Nexus exists to connect extraordinary players with the people building extraordinary worlds.</p>
    </header>

    <section class="stats m-auto d-grid">
        <div v-for="stat in stats" :key="stat.label" class="text-center">
            <strong class="d-block">{{ stat.value }}</strong>
            <span>{{ stat.label }}</span>
        </div>
    </section>

    <main class="page m-auto">
        <section class="section">
            <span class="eyebrow">OUR STORY</span>
            <h2>Built one signal at a time</h2>
            <div class="timeline d-grid">
                <article v-for="item in timeline" :key="item.year">
                    <span>{{ item.year }}</span>
                    <h3>{{ item.title }}</h3>
                    <p>{{ item.text }}</p>
                </article>
            </div>
        </section>

        <section class="section">
            <span class="eyebrow">THE PEOPLE BEHIND THE PIXELS</span>
            <h2>Meet the crew</h2>
            <div class="team d-grid">
                <article v-for="member in team" :key="member.name">
                    <img v-if="member.img" :src="member.img" :alt="member.name" />
                    <div v-else class="avatar-fallback">{{ member.name[0] }}</div>
                    <h3>{{ member.name }}</h3>
                    <span>{{ member.role }}</span>
                </article>
            </div>
        </section>

        <section class="faq m-auto">
            <span class="eyebrow">NEED TO KNOW</span>
            <h2>Frequently Asked Questions</h2>
            <article
                v-for="(item, i) in faqs"
                :key="item.q"
                :class="{ open: openFaq === i }"
            >
                <button type="button" @click="toggleFaq(i)">
                    <span>{{ item.q }}</span>
                    <span>{{ openFaq === i ? '−' : '+' }}</span>
                </button>
                <p v-show="openFaq === i">{{ item.a }}</p>
            </article>
        </section>
    </main>
</template>

<style scoped>
.about-hero {
    background: radial-gradient(circle, rgba(138, 108, 255, 0.18), transparent 45%);
    min-height: 500px;
    place-content: center;
    justify-items: center;
    padding: 40px 24px;
}

.badge {
    border: 1px solid currentColor;
    padding: 6px 10px;
    font-size: 10px;
    line-height: 1;
    letter-spacing: 0.13em;
    font-weight: 700;
    color: var(--cyan);
    background: rgba(0, 242, 254, 0.07);
}

.about-hero h1 {
    letter-spacing: -0.065em;
    margin: 24px 0;
    font-size: clamp(44px, 8vw, 100px);
    line-height: 0.95;
}

.about-hero em {
    color: transparent;
    -webkit-text-stroke: 1px var(--cyan);
    text-shadow: 0 0 28px rgba(0, 242, 254, 0.2);
    font-style: normal;
}

.about-hero p {
    max-width: 690px;
    color: var(--muted);
    font-size: 18px;
    line-height: 1.7;
}

.stats {
    border: 1px solid var(--line);
    grid-template-columns: repeat(4, 1fr);
    max-width: 1250px;
}

.stats div {
    border-right: 1px solid var(--line);
    padding: 35px 20px;
}

.stats div:last-child {
    border-right: 0;
}

.stats strong {
    color: var(--cyan);
    font: 700 34px 'Space Grotesk', sans-serif;
    margin-bottom: 4px;
}

.stats span {
    color: var(--muted);
    font-size: 14px;
}

.page {
    max-width: 1400px;
    padding: 80px max(4vw, 28px);
}

.section {
    margin-bottom: 100px;
}

.eyebrow {
    color: var(--cyan);
    letter-spacing: 0.2em;
    font-size: 11px;
    font-weight: 700;
    display: block;
}

h2 {
    letter-spacing: -0.04em;
    margin: 6px 0 30px;
    font-size: clamp(28px, 4vw, 44px);
}

.timeline,
.team {
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.timeline article {
    border-top: 1px solid var(--line);
    padding: 25px 0;
}

.timeline span {
    color: var(--cyan);
    font: 700 12px 'Space Grotesk', sans-serif;
}

.timeline h3 {
    margin: 12px 0;
}

.timeline p {
    color: var(--muted);
    line-height: 1.6;
}

.team img,
.avatar-fallback {
    width: 100%;
    aspect-ratio: 1;
    border: 1px solid var(--line);
    object-fit: cover;
}

.avatar-fallback {
    display: grid;
    place-items: center;
    font: 700 42px 'Space Grotesk', sans-serif;
    color: var(--cyan);
    background: rgba(0, 242, 254, 0.04);
}

.team h3 {
    margin: 15px 0 4px;
}

.team span {
    color: var(--muted);
    font-size: 13px;
}

.faq {
    max-width: 850px;
}

.faq article {
    border-top: 1px solid var(--line);
}

.faq article:last-child {
    border-bottom: 1px solid var(--line);
}

.faq button {
    color: #fff;
    background: none;
    border: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 22px 0;
    font-size: 16px;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
}

.faq button:hover,
.faq article.open button {
    color: var(--cyan);
}

.faq p {
    color: var(--muted);
    line-height: 1.65;
    margin: 0 0 24px;
}

@media (max-width: 900px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
        margin: 0 24px;
    }

    .stats div:nth-child(2) {
        border-right: 0;
    }

    .stats div:nth-child(-n+2) {
        border-bottom: 1px solid var(--line);
    }

    .timeline,
    .team {
        grid-template-columns: 1fr;
    }
}
</style>