<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '../api/client'
import { useUiStore } from '../store/ui'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()

const data = ref(null)
const present = ref({})
const saving = ref(false)

async function load() {
  const { data: res } = await client.get(`/groups/${route.params.id}/seance`)
  data.value = res
  const initial = {}
  res.participants.forEach((p) => { initial[p.user.id] = p.default_present })
  present.value = initial
}
onMounted(load)

function toggle(userId) {
  present.value[userId] = !present.value[userId]
}

const nbPresents = computed(() => Object.values(present.value).filter(Boolean).length)

const absentNames = computed(() => {
  if (!data.value) return []
  return data.value.participants
    .filter((p) => p.note !== 'en pause' && !present.value[p.user.id])
    .map((p) => p.user.name)
})

async function save() {
  if (!data.value?.episode) return
  saving.value = true
  try {
    const participant_ids = Object.entries(present.value).filter(([, v]) => v).map(([id]) => Number(id))
    const { data: res } = await client.post(`/groups/${route.params.id}/seance`, {
      episode_id: data.value.episode.id,
      participant_ids,
    })
    ui.showToast(res.toast)
    router.push({ name: 'groupe', params: { id: route.params.id } })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="data" class="screen">
    <div class="back" @click="router.push({ name: 'groupe', params: { id: route.params.id } })">← Groupe</div>
    <div class="h-display title">Soirée série !</div>

    <div v-if="data.episode" class="card watching-card">
      <div class="eyebrow">ON REGARDE</div>
      <div class="ep h-display">{{ data.group.show.title }} <span class="code">{{ data.episode.code }}</span></div>
      <div class="ep-title muted">« {{ data.episode.title }} »</div>
    </div>
    <div v-else class="card watching-card">
      <div class="eyebrow">TOUT EST VU !</div>
      <div class="ep h-display">La troupe a rattrapé toute la série 🎉</div>
    </div>

    <div class="card participants-card">
      <div class="eyebrow" style="margin-bottom:6px">QUI EST SUR LE CANAPÉ ?</div>
      <div
        v-for="p in data.participants" :key="p.user.id" class="prow divider-dashed"
        @click="toggle(p.user.id)"
      >
        <div class="avatar" :style="{ background: p.user.color, width: '38px', height: '38px', fontSize: '15px', opacity: present[p.user.id] ? 1 : 0.45 }">{{ p.user.initial }}</div>
        <div class="pname">{{ p.user.name }}</div>
        <div class="pnote faint">{{ p.note }}</div>
        <div
          class="check-circle"
          :style="{ background: present[p.user.id] ? 'var(--sage)' : 'transparent', borderColor: present[p.user.id] ? 'var(--sage)' : 'var(--ring-off)', border: '2px solid', width: '28px', height: '28px' }"
        >{{ present[p.user.id] ? '✓' : '' }}</div>
      </div>
    </div>

    <div class="foot muted">Seuls les présents sont marqués — personne n'est coché par procuration.</div>

    <div v-if="absentNames.length" class="warn-box">
      💤 {{ absentNames.join(', ') }} {{ absentNames.length > 1 ? 'sont absent·e·s' : 'est absent·e' }} : le point commun attendra, et {{ absentNames.length > 1 ? 'ils sont' : 'iel est' }} protégé·e·s des spoilers.
    </div>

    <div class="btn-primary" :class="{ disabled: saving || !data.episode }" @click="save">
      Enregistrer la séance ({{ nbPresents }})
    </div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.back { font: 800 13px var(--font-body); color: var(--terra-dark); cursor: pointer; }
.title { font-size: 26px; }

.watching-card { padding: 16px; }
.eyebrow { font: 800 12px var(--font-body); color: var(--ink-soft); }
.ep { font-size: 19px; margin-top: 4px; }
.code { color: var(--terra-dark); }
.ep-title { font: 700 13px var(--font-body); margin-top: 2px; }

.participants-card { padding: 14px 16px; }
.prow { display: flex; align-items: center; gap: 12px; padding: 10px 0; cursor: pointer; }
.prow:last-child { border-bottom: none; }
.pname { flex: 1; font: 800 14px var(--font-body); }
.pnote { font: 600 12px var(--font-body); }

.foot { font: 600 12px var(--font-body); line-height: 1.5; }

.warn-box {
  background: var(--warn-bg);
  border: 2px solid var(--warn-border);
  border-radius: 18px;
  padding: 12px 16px;
  font: 700 13px var(--font-body);
  color: var(--warn-text);
  line-height: 1.45;
}

.btn-primary.disabled { opacity: 0.5; pointer-events: none; }
</style>
