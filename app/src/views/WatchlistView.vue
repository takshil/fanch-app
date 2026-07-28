<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import client from '../api/client'
import { useUiStore } from '../store/ui'

const router = useRouter()
const ui = useUiStore()
const items = ref(null)

async function load() {
  const { data } = await client.get('/watchlist')
  items.value = data.items
}
onMounted(load)

async function start(show) {
  await client.post(`/shows/${show.slug}/watchlist-start`)
  router.push({ name: 'serie', params: { slug: show.slug } })
}

function importFrom() {
  ui.showToast('Import bientôt disponible.')
}
</script>

<template>
  <div class="screen">
    <div class="screen-title">Pour plus tard</div>

    <template v-if="items && items.length">
      <div v-for="show in items" :key="show.slug" class="card row">
        <div class="poster" :style="{ background: show.color }">{{ show.initiale }}</div>
        <div class="info">
          <div class="stitle h-display">{{ show.title }}</div>
          <div class="sinfo muted">{{ show.info }}</div>
        </div>
        <div class="btn-chip" @click="start(show)">Commencer</div>
      </div>
    </template>
    <div v-else-if="items" class="empty muted">Rien pour plus tard — cherchez une série à ajouter.</div>

    <div class="dashed-import" @click="importFrom">Importer depuis TV Time, Trakt ou CSV</div>
    <div class="foot faint">Personne ne resaisira deux cents séries à la main.</div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.row { padding: 12px; display: flex; gap: 12px; align-items: center; }
.poster {
  width: 48px; height: 66px; flex: none; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: var(--terra-text); font: 800 22px var(--font-display);
}
.info { flex: 1; min-width: 0; }
.stitle { font-size: 15px; }
.sinfo { font: 700 12px var(--font-body); margin-top: 2px; }
.empty { text-align: center; padding: 24px 12px; font: 700 13px var(--font-body); }
.foot { text-align: center; font: 600 11px var(--font-body); }
</style>
