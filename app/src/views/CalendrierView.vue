<script setup>
import { ref, onMounted } from 'vue'
import client from '../api/client'

const agenda = ref(null)

onMounted(async () => {
  const { data } = await client.get('/calendrier')
  agenda.value = data.agenda
})
</script>

<template>
  <div class="screen">
    <div class="screen-title">Bientôt à l'antenne</div>

    <template v-if="agenda && agenda.length">
      <div v-for="day in agenda" :key="day.jour">
        <div class="jour">{{ day.jour }}</div>
        <div class="card items-card">
          <div v-for="(it, i) in day.items" :key="i" class="item-row divider-dashed">
            <div class="code-chip">{{ it.code }}</div>
            <div class="mid">
              <div class="serie h-display">{{ it.serie }}</div>
              <div class="plat faint">{{ it.plateforme }}</div>
              <div v-if="it.note" class="note">🤫 {{ it.note }}</div>
            </div>
          </div>
        </div>
      </div>
    </template>
    <div v-else-if="agenda" class="empty muted">Rien de prévu pour l'instant.</div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); gap: 16px; }
.jour { font: 800 13px var(--font-body); color: var(--terra-dark); margin-bottom: 8px; }
.items-card { padding: 4px 16px; }
.item-row { display: flex; gap: 12px; align-items: center; padding: 11px 0; }
.item-row:last-child { border-bottom: none; }
.code-chip {
  background: var(--terra-chip); color: var(--terra-dark); border-radius: 10px;
  padding: 6px 10px; font: 800 12px var(--font-body); flex: none;
}
.mid { flex: 1; min-width: 0; }
.serie { font-size: 14px; }
.plat { font: 600 12px var(--font-body); }
.note { font: 700 11px var(--font-body); color: var(--sage-chip-text); margin-top: 2px; }
.empty { text-align: center; padding: 24px 12px; font: 700 13px var(--font-body); }
</style>
