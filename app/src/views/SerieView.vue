<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '../api/client'

const route = useRoute()
const router = useRouter()
const data = ref(null)
const joinCode = ref('')
const groupBusy = ref(false)
const groupError = ref(null)

const visOpts = [
  { v: 'heritee', t: 'Héritée' },
  { v: 'privee', t: 'Privée' },
  { v: 'visible', t: 'Visible' },
]

async function load() {
  data.value = null
  const { data: res } = await client.get(`/shows/${route.params.slug}`)
  data.value = res
}
onMounted(load)
watch(() => route.params.slug, load)

async function createGroup() {
  groupBusy.value = true
  groupError.value = null
  try {
    const { data: res } = await client.post(`/shows/${route.params.slug}/groups`, {})
    router.push({ name: 'groupe', params: { id: res.group.id } })
  } finally {
    groupBusy.value = false
  }
}

async function joinGroup() {
  if (!joinCode.value.trim()) return
  groupBusy.value = true
  groupError.value = null
  try {
    const { data: res } = await client.post('/groups/join', { code: joinCode.value.trim() })
    router.push({ name: 'groupe', params: { id: res.group.id } })
  } catch (e) {
    groupError.value = e.response?.data?.message || 'Code invalide.'
  } finally {
    groupBusy.value = false
  }
}

async function toggleEp(ep) {
  ep.vu = !ep.vu
  await client.post(`/episodes/${ep.id}/toggle`)
  data.value.nb_vus = data.value.episodes.filter((e) => e.vu).length
  data.value.pct_vus = Math.round((data.value.nb_vus / data.value.total) * 100)
}

async function markAll() {
  data.value.episodes.forEach((e) => { e.vu = true })
  data.value.nb_vus = data.value.total
  data.value.pct_vus = 100
  await client.post(`/shows/${route.params.slug}/mark-all`)
}

async function setVisibility(v) {
  data.value.visibility = v
  await client.post(`/shows/${route.params.slug}/visibility`, { visibility: v })
}
</script>

<template>
  <div v-if="data" class="screen">
    <div class="back" @click="router.push({ name: 'a-voir' })">← À voir</div>

    <div class="hero-row">
      <div class="poster" :style="{ background: data.show.color }">{{ data.show.initiale }}</div>
      <div class="hero-info">
        <div class="title h-display">{{ data.show.title }}</div>
        <div class="meta muted">{{ data.show.platform }} · {{ data.show.status }} · {{ data.show.genre }}</div>
        <div class="progress-wrap">
          <div class="track"><div class="fill" :style="{ width: data.pct_vus + '%' }" /></div>
          <div class="progress-label">{{ data.nb_vus }} / {{ data.total }} vus — saison {{ data.season_number }}</div>
        </div>
      </div>
    </div>

    <div class="card vis-card">
      <div class="eyebrow">QUI PEUT VOIR CETTE SÉRIE ?</div>
      <div class="switch">
        <div
          v-for="o in visOpts" :key="o.v" class="switch-opt"
          :class="{ active: data.visibility === o.v }" @click="setVisibility(o.v)"
        >{{ o.t }}</div>
      </div>
      <div class="hint faint">Héritée = votre réglage global (« mon foyer »).</div>
    </div>

    <div class="card group-card">
      <div class="eyebrow">REGARDER EN GROUPE</div>
      <template v-if="data.group_id">
        <div class="group-link" @click="router.push({ name: 'groupe', params: { id: data.group_id } })">
          → Voir le groupe de visionnage
        </div>
      </template>
      <template v-else>
        <div class="hint faint" style="margin:6px 0 10px">
          Suivez cette série avec d'autres comptes — même dans un foyer différent.
        </div>
        <div class="group-actions">
          <div class="btn-chip" :class="{ disabled: groupBusy }" @click="createGroup">Créer un groupe</div>
          <input v-model="joinCode" placeholder="Code d'invitation" class="join-input">
          <div class="btn-chip" :class="{ disabled: groupBusy }" @click="joinGroup">Rejoindre</div>
        </div>
        <div v-if="groupError" class="error">{{ groupError }}</div>
      </template>
    </div>

    <div class="season-row">
      <div class="h-display season-title">Saison {{ data.season_number }}</div>
      <div class="btn-chip" @click="markAll">Tout marquer vu</div>
    </div>

    <div class="card ep-list">
      <div v-for="ep in data.episodes" :key="ep.id" class="ep-row divider-dashed">
        <div class="ep-num">{{ ep.num }}</div>
        <div class="ep-title">{{ ep.titre }}</div>
        <div
          class="check-circle"
          :style="{ background: ep.vu ? 'var(--sage)' : 'transparent', borderColor: ep.vu ? 'var(--sage)' : 'var(--ring-off)', border: '2px solid', width: '28px', height: '28px' }"
          @click="toggleEp(ep)"
        >{{ ep.vu ? '✓' : '' }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.back { font: 800 13px var(--font-body); color: var(--terra-dark); cursor: pointer; }

.hero-row { display: flex; gap: 14px; }
.poster {
  width: 88px; height: 122px; flex: none; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  color: var(--terra-text); font: 800 38px var(--font-display);
}
.hero-info { min-width: 0; flex: 1; }
.title { font-size: 24px; line-height: 1.05; }
.meta { font: 700 13px var(--font-body); margin-top: 4px; }
.progress-wrap { margin-top: 12px; }
.track { height: 10px; background: var(--track); border-radius: 999px; overflow: hidden; }
.fill { height: 10px; background: var(--terra); border-radius: 999px; }
.progress-label { font: 800 13px var(--font-body); margin-top: 6px; color: var(--terra-dark); }

.vis-card { padding: 14px; }
.eyebrow { font: 800 12px var(--font-body); color: var(--ink-soft); }
.switch { display: flex; background: var(--track); border-radius: 999px; padding: 4px; margin-top: 10px; }
.switch-opt {
  flex: 1; text-align: center; padding: 8px; border-radius: 999px; cursor: pointer;
  font: 800 12px var(--font-body); color: var(--ink-soft);
}
.switch-opt.active { background: var(--paper2); color: var(--terra-dark); }
.hint { font: 600 11px var(--font-body); margin-top: 8px; }

.season-row { display: flex; justify-content: space-between; align-items: center; }
.season-title { font-size: 17px; }

.ep-list { padding: 4px 16px; }
.ep-row { display: flex; gap: 12px; align-items: center; padding: 11px 0; }
.ep-row:last-child { border-bottom: none; }
.ep-num { font: 800 13px var(--font-body); color: var(--terra-dark); width: 28px; flex: none; }
.ep-title { flex: 1; font: 700 14px var(--font-body); }

.group-card { padding: 14px; }
.group-link { margin-top: 8px; font: 800 13px var(--font-body); color: var(--terra-dark); cursor: pointer; }
.group-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.join-input {
  flex: 1; min-width: 120px; border: 2px solid var(--border-strong); border-radius: 999px;
  padding: 8px 14px; background: var(--paper2); font: 700 12px var(--font-body); color: var(--ink);
}
.btn-chip.disabled { opacity: 0.5; pointer-events: none; }
.error { font: 700 12px var(--font-body); color: var(--terra-dark); margin-top: 6px; }
</style>
