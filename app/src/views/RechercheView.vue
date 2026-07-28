<script setup>
import { ref, onMounted, watch } from 'vue'
import client from '../api/client'

const query = ref('sh')
const results = ref([])
let debounceTimer = null

async function search() {
  const q = query.value.trim()
  if (!q) { results.value = []; return }
  const { data } = await client.get('/search', { params: { q } })
  results.value = data.results
}

watch(query, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(search, 300)
})
onMounted(search)

async function toggle(r) {
  const payload = r.source === 'local'
    ? { show_id: r.id }
    : { tvmaze_id: r.tvmaze_id, title: r.title, platform: r.platform, seasons_count: r.seasons_count }
  const { data } = await client.post('/watchlist/toggle', payload)
  r.added = data.added
  if (r.source === 'tvmaze' && data.added) {
    r.id = data.show_id
    r.source = 'local'
  }
}
</script>

<template>
  <div class="screen">
    <div class="screen-title">Une nouvelle série ?</div>
    <input v-model="query" placeholder="Cherchez un titre…" class="search-input">
    <div class="eyebrow">RÉSULTATS POUR « {{ query.toUpperCase() }} »</div>

    <div v-for="r in results" :key="r.slug" class="card row">
      <div class="poster" :style="{ background: r.color }">{{ r.initiale }}</div>
      <div class="info">
        <div class="rtitle h-display">{{ r.title }}</div>
        <div class="rinfo muted">{{ r.info }}</div>
      </div>
      <div class="btn-chip" :class="{ added: r.added }" @click="toggle(r)">
        {{ r.added ? '✓ Ajoutée' : '+ Ajouter' }}
      </div>
    </div>
    <div v-if="query && !results.length" class="empty faint">Aucun résultat.</div>

    <div class="foot faint">Catalogue TVmaze — aucune clé requise. TMDB en option pour les affiches et le français.</div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.search-input {
  border: 2px solid var(--border-strong);
  border-radius: 999px;
  padding: 13px 20px;
  background: var(--paper2);
  font: 700 15px var(--font-body);
  color: var(--ink);
  width: 100%;
}
.eyebrow { font: 800 12px var(--font-body); color: var(--ink-soft); }

.row { padding: 12px; display: flex; gap: 12px; align-items: center; }
.poster {
  width: 48px; height: 66px; flex: none; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: var(--terra-text); font: 800 22px var(--font-display);
}
.info { flex: 1; min-width: 0; }
.rtitle { font-size: 15px; }
.rinfo { font: 700 12px var(--font-body); margin-top: 2px; }
.btn-chip.added { background: var(--sage-chip); color: var(--sage-chip-text); }
.btn-chip.added:hover { opacity: 0.85; }

.empty { text-align: center; padding: 12px; font: 700 13px var(--font-body); }
.foot { text-align: center; font: 600 11px var(--font-body); }
</style>
