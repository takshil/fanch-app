<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const inSeries = computed(() => route.meta.module === 'series')

const hubTabs = [
  { key: 'accueil', label: 'Accueil', pic: '🏡' },
  { key: 'reglages', label: 'Qui voit quoi', pic: '🔒' },
]
const seriesTabs = [
  { key: 'a-voir', label: 'À voir', pic: '🛋️' },
  { key: 'calendrier', label: 'Bientôt', pic: '🗓️' },
  { key: 'recherche', label: 'Chercher', pic: '🔍' },
  { key: 'watchlist', label: 'Plus tard', pic: '💭' },
]

const activeKey = computed(() => (inSeries.value ? route.meta.tab : route.name))
const tabs = computed(() => (inSeries.value ? seriesTabs : hubTabs))

function goTab(key) {
  router.push({ name: key })
}
</script>

<template>
  <div class="tabbar">
    <button class="f-btn" :class="{ home: !inSeries }" title="Accueil Fanch" @click="router.push('/')">f</button>
    <button
      v-for="t in tabs" :key="t.key"
      class="tab" :class="{ active: t.key === activeKey }"
      @click="goTab(t.key)"
    >
      <div class="pic">{{ t.pic }}</div>
      <div class="label">{{ t.label }}</div>
    </button>
  </div>
</template>

<style scoped>
.tabbar {
  flex: none;
  padding: 10px 14px calc(env(safe-area-inset-bottom, 0px) + 14px);
  background: var(--paper2);
  border-top: 2px solid var(--border);
  display: flex;
  gap: 6px;
  align-items: center;
}
.f-btn {
  width: 46px;
  height: 46px;
  flex: none;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background: var(--track);
  color: var(--ink);
  font: 800 19px var(--font-display);
}
.f-btn.home {
  background: var(--terra);
  color: var(--terra-text);
}
.f-btn:hover {
  background: var(--terra-hover);
  color: var(--terra-text);
}
.tab {
  flex: 1;
  padding: 7px 2px;
  text-align: center;
  cursor: pointer;
  border-radius: 14px;
  background: transparent;
}
.tab:hover { background: var(--terra-chip); }
.tab.active { background: var(--terra-chip); }
.pic { font-size: 17px; line-height: 1; }
.label {
  font: 800 10px var(--font-body);
  color: var(--ink-soft);
  margin-top: 3px;
}
.tab.active .label { color: var(--terra-dark); }
</style>
