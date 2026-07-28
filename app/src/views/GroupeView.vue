<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import client from '../api/client'
import { useAuthStore } from '../store/auth'
import { useUiStore } from '../store/ui'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const data = ref(null)

const statusLabels = { actif: 'Actif', pause: 'En pause', abandonne: 'Abandonné' }

async function load() {
  data.value = null
  const { data: res } = await client.get(`/groups/${route.params.id}`)
  data.value = res
}
onMounted(load)
watch(() => route.params.id, load)

function isMe(member) {
  return auth.user && member.user.id === auth.user.id
}

async function setMyStatus(status) {
  const { data: res } = await client.post(`/groups/${route.params.id}/status`, { status })
  data.value = res
}

async function removeMember(member) {
  const { data: res } = await client.delete(`/groups/${route.params.id}/members/${member.user.id}`)
  data.value = res
}

async function addSuggestion(suggestion) {
  const { data: res } = await client.post(`/groups/${route.params.id}/members`, { user_id: suggestion.id })
  data.value = res
}

function copyInvite() {
  navigator.clipboard?.writeText(data.value.group.invite_code)
  ui.showToast('Code copié : ' + data.value.group.invite_code)
}
</script>

<template>
  <div v-if="data" class="screen">
    <div>
      <div class="eyebrow">GROUPE · {{ data.group.show.title.toUpperCase() }}</div>
      <div class="h-display group-name">{{ data.group.name }}</div>
    </div>

    <div v-if="data.group.archived" class="warn-box">
      💤 Ce groupe est archivé — plus personne n'est actif dessus. Repassez « Actif » pour le relancer.
    </div>

    <div class="hero">
      <div class="eyebrow" style="opacity:.85">ON EN EST OÙ ?</div>
      <div class="h-display pc-label">{{ data.point_commun.label }}</div>
      <div class="pc-note">{{ data.point_commun.note }}</div>
    </div>

    <div class="card members-card">
      <div class="eyebrow" style="margin-bottom:10px">LA PETITE TROUPE</div>
      <div v-for="m in data.members" :key="m.user.id" class="member-row">
        <div class="avatar" :style="{ background: m.user.color, width: '34px', height: '34px', fontSize: '14px' }">{{ m.user.initial }}</div>
        <div class="mname">{{ m.user.name }}</div>
        <div class="track"><div class="fill" :style="{ width: m.pct + '%', background: m.statut === 'actif' ? 'var(--sage)' : 'var(--ring-off)' }" /></div>
        <div class="pos muted">{{ m.pos_code }}</div>
        <span class="tag" :class="m.statut">{{ statusLabels[m.statut] }}</span>
        <div
          v-if="data.group.is_creator && !isMe(m)" class="remove-btn" title="Retirer du groupe"
          @click="removeMember(m)"
        >✕</div>
      </div>
      <div class="hint faint">Les membres en pause ou abandonnés ne bloquent pas le groupe.</div>

      <template v-if="data.household_suggestions?.length">
        <div class="eyebrow" style="margin-top:10px">DU FOYER, PAS ENCORE DANS LE GROUPE</div>
        <div class="suggestions">
          <div
            v-for="s in data.household_suggestions" :key="s.id" class="suggestion-chip"
            @click="addSuggestion(s)"
          >
            <div class="avatar" :style="{ background: s.color, width: '20px', height: '20px', fontSize: '10px' }">{{ s.initial }}</div>
            {{ s.name }}
          </div>
        </div>
      </template>
    </div>

    <div class="card status-card">
      <div class="eyebrow" style="margin-bottom:10px">MON STATUT DANS CE GROUPE</div>
      <div class="switch">
        <div
          v-for="s in ['actif', 'pause', 'abandonne']" :key="s" class="switch-opt"
          :class="{ active: data.group.my_status === s }" @click="setMyStatus(s)"
        >{{ statusLabels[s] }}</div>
      </div>
    </div>

    <div class="card invite-card" @click="copyInvite">
      <div class="eyebrow">CODE D'INVITATION — cliquez pour copier</div>
      <div class="invite-code h-display">{{ data.group.invite_code }}</div>
    </div>

    <div class="card ep-card">
      <div v-for="ep in data.episodes" :key="ep.num" class="ep-row divider-dashed" :style="{ opacity: ep.masked ? 0.7 : 1 }">
        <div class="ep-num" :style="{ color: ep.masked ? 'var(--ink-faint)' : 'var(--terra-dark)' }">{{ ep.num }}</div>
        <div class="ep-mid">
          <div class="ep-titre" :style="{ color: ep.masked ? 'var(--ink-soft)' : 'var(--ink)' }">{{ ep.titre }}</div>
          <div class="ep-sub faint">{{ ep.sub }}</div>
        </div>
        <div class="ep-pic">{{ ep.pic }}</div>
      </div>
    </div>

    <div class="btn-primary" @click="router.push({ name: 'seance', params: { id: data.group.id } })">🛋️ Enregistrer une séance</div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.eyebrow { font: 800 12px var(--font-body); color: var(--ink-soft); }
.group-name { font-size: 26px; margin-top: 2px; }

.warn-box {
  background: var(--warn-bg); border: 2px solid var(--warn-border); border-radius: 18px;
  padding: 12px 16px; font: 700 13px var(--font-body); color: var(--warn-text); line-height: 1.45;
}

.hero {
  background: var(--sage);
  color: var(--sage-dark);
  border-radius: 22px;
  padding: 18px 20px;
  background-image: radial-gradient(circle at 90% 10%, #C4DAB8 0 60px, transparent 61px);
}
.pc-label { font-size: 30px; margin-top: 4px; }
.pc-note { font: 700 13px var(--font-body); margin-top: 4px; opacity: 0.92; }

.members-card { padding: 14px 16px; }
.member-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; }
.mname { width: 62px; flex: none; font: 800 13px var(--font-body); }
.track { flex: 1; height: 8px; background: var(--track); border-radius: 999px; overflow: hidden; }
.fill { height: 8px; border-radius: 999px; }
.pos { font: 800 12px var(--font-body); width: 46px; flex: none; text-align: right; }
.tag {
  padding: 2px 9px; border-radius: 999px; font: 800 10px var(--font-body); flex: none;
  background: var(--sage-chip); color: var(--sage-chip-text);
}
.tag.pause, .tag.abandonne { background: var(--track); color: var(--ink-soft); }
.hint { font: 600 11px var(--font-body); margin-top: 6px; }
.remove-btn {
  flex: none; width: 20px; height: 20px; border-radius: 999px; display: flex; align-items: center;
  justify-content: center; color: var(--ink-faint); cursor: pointer; font: 800 11px var(--font-body);
}
.remove-btn:hover { color: var(--terra-dark); background: var(--terra-chip); }

.suggestions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.suggestion-chip {
  display: flex; align-items: center; gap: 6px; background: var(--track); color: var(--ink);
  padding: 5px 12px 5px 6px; border-radius: 999px; font: 700 12px var(--font-body); cursor: pointer;
}
.suggestion-chip:hover { background: var(--terra-chip); color: var(--terra-dark); }

.status-card { padding: 14px; }
.switch { display: flex; background: var(--track); border-radius: 999px; padding: 4px; }
.switch-opt {
  flex: 1; text-align: center; padding: 8px; border-radius: 999px; cursor: pointer;
  font: 800 12px var(--font-body); color: var(--ink-soft);
}
.switch-opt.active { background: var(--paper2); color: var(--terra-dark); }

.invite-card { padding: 12px 16px; cursor: pointer; }
.invite-code { font-size: 18px; letter-spacing: 0.02em; margin-top: 4px; color: var(--terra-dark); }

.ep-card { padding: 4px 16px; }
.ep-row { display: flex; gap: 12px; align-items: center; padding: 10px 0; }
.ep-row:last-child { border-bottom: none; }
.ep-num { font: 800 13px var(--font-body); width: 52px; flex: none; }
.ep-mid { flex: 1; min-width: 0; }
.ep-titre { font: 700 14px var(--font-body); }
.ep-sub { font: 600 11px var(--font-body); margin-top: 1px; }
.ep-pic { font-size: 15px; flex: none; }
</style>
