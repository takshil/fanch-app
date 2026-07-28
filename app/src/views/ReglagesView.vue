<script setup>
import { ref, onMounted } from 'vue'
import client from '../api/client'
import { useUiStore } from '../store/ui'
import { useAuthStore } from '../store/auth'

const ui = useUiStore()
const auth = useAuthStore()
const data = ref(null)

const roleLabels = { admin: 'Admin', membre: 'Membre', enfant: 'Enfant' }

const visDefs = [
  { v: 'privee', t: 'Rien que moi', d: 'Personne ne voit vos séries' },
  { v: 'foyers', t: 'Mon foyer', d: 'Pratique en famille, révocable série par série' },
  { v: 'groupes', t: 'Mes groupes', d: 'Seulement les séries suivies ensemble' },
]

const joinCode = ref('')
const newFoyerName = ref('')
const foyerBusy = ref(false)

async function load() {
  const { data: res } = await client.get('/reglages')
  data.value = res
  if (!auth.households.length) await auth.fetchMe()
}
onMounted(load)

async function setGlobal(v) {
  data.value.visibility_global = v
  await client.post('/reglages/visibility-global', { visibility: v })
}

async function switchFoyer(h) {
  if (h.id === auth.currentHouseholdId) return
  await auth.switchHousehold(h.id)
  ui.showToast('Foyer actif : ' + h.name)
}

async function joinFoyer() {
  if (!joinCode.value.trim()) return
  foyerBusy.value = true
  try {
    await auth.joinHousehold(joinCode.value.trim())
    ui.showToast('Foyer rejoint : ' + auth.household?.name)
    joinCode.value = ''
  } catch (e) {
    ui.showToast(e.response?.data?.message || 'Code invalide.')
  } finally {
    foyerBusy.value = false
  }
}

async function createFoyer() {
  if (!newFoyerName.value.trim()) return
  foyerBusy.value = true
  try {
    await auth.createHousehold(newFoyerName.value.trim())
    ui.showToast('Foyer créé : ' + auth.household?.name)
    newFoyerName.value = ''
  } finally {
    foyerBusy.value = false
  }
}

async function leaveFoyer(h) {
  try {
    await auth.leaveHousehold(h.id)
    ui.showToast('Vous avez quitté ' + h.name)
  } catch (e) {
    ui.showToast(e.response?.data?.message || 'Impossible de quitter ce foyer.')
  }
}

function exportData() {
  ui.showToast('Export bientôt disponible.')
}
</script>

<template>
  <div v-if="data" class="screen">
    <div class="screen-title">Qui voit quoi ?</div>

    <div class="card foyers-card">
      <div class="eyebrow">MES FOYERS</div>
      <div v-for="h in auth.households" :key="h.id" class="foyer-row divider-dashed">
        <div class="foyer-mid" @click="switchFoyer(h)">
          <div class="foyer-name">{{ h.name }}</div>
          <div class="foyer-role faint">{{ roleLabels[h.role] }}</div>
        </div>
        <span v-if="h.id === auth.currentHouseholdId" class="active-tag">Actif</span>
        <div v-else class="btn-chip small" @click="switchFoyer(h)">Activer</div>
        <div v-if="auth.households.length > 1" class="leave-btn" title="Quitter" @click="leaveFoyer(h)">✕</div>
      </div>
      <div class="hint faint">Vos séries et votre progression restent sur votre compte, même en changeant de foyer.</div>
    </div>

    <div class="card join-card">
      <div class="foyer-actions">
        <input v-model="joinCode" placeholder="Code d'invitation" class="foyer-input">
        <div class="btn-chip" :class="{ disabled: foyerBusy }" @click="joinFoyer">Rejoindre</div>
      </div>
      <div class="foyer-actions">
        <input v-model="newFoyerName" placeholder="Nom du nouveau foyer" class="foyer-input">
        <div class="btn-chip" :class="{ disabled: foyerBusy }" @click="createFoyer">Créer</div>
      </div>
    </div>

    <div class="intro muted">Vos séries sont privées par défaut. Choisissez ici pour tout le monde, avec des exceptions série par série.</div>

    <div class="card vis-card">
      <div
        v-for="v in visDefs" :key="v.v" class="vis-row divider-dashed"
        @click="setGlobal(v.v)"
      >
        <div class="ring" :style="{ borderColor: data.visibility_global === v.v ? 'var(--terra)' : 'var(--ring-off)' }">
          <div class="dot" :style="{ background: data.visibility_global === v.v ? 'var(--terra)' : 'transparent' }" />
        </div>
        <div>
          <div class="vt">{{ v.t }}</div>
          <div class="vd faint">{{ v.d }}</div>
        </div>
      </div>
    </div>

    <div v-if="data.exceptions.length" class="card exceptions-card">
      <div class="eyebrow">EXCEPTIONS</div>
      <div v-for="(e, i) in data.exceptions" :key="i" class="exc-row">
        <span class="exc-name">{{ e.show.title }}</span>
        <span class="exc-badge" :class="e.visibility">
          {{ e.visibility === 'privee' ? '🔒 Privée' : '👥 Visible' }}
        </span>
      </div>
    </div>

    <div class="warn-box">
      Rejoindre un groupe montre votre position sur cette série — c'est ce qui permet le « on en est où ? ». Vos dates, notes et rewatchs restent pour vous.
    </div>

    <div class="dashed-import" @click="exportData">Emporter toutes mes données</div>
    <div class="foot faint">Aucun tracker · Hébergé chez vous ou en UE · Suppression réelle</div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); }
.intro { font: 700 13px var(--font-body); line-height: 1.5; }
.eyebrow { font: 800 12px var(--font-body); color: var(--ink-soft); }

.foyers-card { padding: 6px 16px 14px; }
.foyer-row { display: flex; align-items: center; gap: 10px; padding: 11px 0; }
.foyer-row:last-of-type { border-bottom: none; }
.foyer-mid { flex: 1; min-width: 0; cursor: pointer; }
.foyer-name { font: 800 14px var(--font-body); }
.foyer-role { font: 600 11px var(--font-body); margin-top: 1px; }
.active-tag {
  background: var(--sage-chip); color: var(--sage-chip-text); padding: 3px 12px;
  border-radius: 999px; font: 800 11px var(--font-body); flex: none;
}
.btn-chip.small { padding: 6px 12px; font-size: 11px; flex: none; }
.btn-chip.disabled { opacity: 0.5; pointer-events: none; }
.leave-btn {
  flex: none; width: 20px; height: 20px; border-radius: 999px; display: flex; align-items: center;
  justify-content: center; color: var(--ink-faint); cursor: pointer; font: 800 11px var(--font-body);
}
.leave-btn:hover { color: var(--terra-dark); background: var(--terra-chip); }
.hint { font: 600 11px var(--font-body); margin-top: 6px; }

.join-card { padding: 14px 16px; display: flex; flex-direction: column; gap: 10px; }
.foyer-actions { display: flex; gap: 8px; }
.foyer-input {
  flex: 1; min-width: 0; border: 2px solid var(--border-strong); border-radius: 999px;
  padding: 8px 14px; background: var(--paper2); font: 700 12px var(--font-body); color: var(--ink);
}

.vis-card { padding: 6px 16px; }
.vis-row { display: flex; gap: 12px; padding: 13px 0; cursor: pointer; align-items: flex-start; }
.vis-row:last-child { border-bottom: none; }
.ring {
  width: 22px; height: 22px; flex: none; border-radius: 999px; border: 2px solid;
  display: flex; align-items: center; justify-content: center; margin-top: 1px;
}
.dot { width: 11px; height: 11px; border-radius: 999px; }
.vt { font: 800 14px var(--font-body); }
.vd { font: 600 12px var(--font-body); margin-top: 2px; }

.exceptions-card { padding: 14px 16px; }
.exc-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; }
.exc-name { font: 700 14px var(--font-body); }
.exc-badge {
  padding: 3px 12px; border-radius: 999px; font: 800 11px var(--font-body);
  background: var(--track); color: var(--ink-soft);
}
.exc-badge.visible { background: var(--sage-chip); color: var(--sage-chip-text); }

.warn-box {
  background: var(--warn-bg); border: 2px solid var(--warn-border); border-radius: 18px;
  padding: 12px 16px; font: 700 12px var(--font-body); color: var(--warn-text); line-height: 1.5;
}
.foot { text-align: center; font: 600 11px var(--font-body); }
</style>
