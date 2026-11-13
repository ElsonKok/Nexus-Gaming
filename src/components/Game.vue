<template>
    <div class="page">
        <div class="page-heading">
            <span class="eyebrow">THE NEXUS ARCHIVE</span>
            <h1>Game Library</h1>
            <p>Explore hand-picked worlds from visionary studios.</p>
        </div>
        <div>
            <aside class="filters">
                <h3><img src="/filter.svg" alt="Filters">Filters</h3>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.page {
    padding: 60px max(4vw, 28px);
}

.eyebrow {
    color: var(--cyan);
    letter-spacing: .2em;
    font-size: 11px;
    font-weight: 700;
    display: block;
}

.page-heading h1 {
    letter-spacing: -.055em;
    margin: 10px 0;
    font-size: clamp(42px, 6vw, 78px);
}

.page-heading p {
    color: var(--muted);
    font-size: 17px;
}

.filters {
    border-right: 1px solid var(--line);
    padding-right: 30px;
}
</style>