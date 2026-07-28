<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import client from '../api/client'

const router = useRouter()
const data = ref(null)

async function load() {
  const { data: res } = await client.get('/home')
  data.value = res
}
onMounted(load)

function goModule(m) {
  if (m.active && m.route) router.push({ name: m.route })
}
</script>

<template>
  <div v-if="data" class="screen">
    <div class="header-row">
      <div>
        <div class="h-display greeting">Bonsoir,<br>{{ data.user.name }}</div>
        <div class="subline muted">{{ data.household.name }} · {{ data.today_label }}</div>
      </div>
      <div class="avatars">
        <div
          v-for="m in data.members" :key="m.id" class="avatar" :title="m.name"
          :style="{ background: m.color, marginLeft: '-10px', border: '3px solid var(--paper)', width: '40px', height: '40px', fontSize: '15px' }"
        >{{ m.initial }}</div>
      </div>
    </div>

    <div v-if="data.ce_soir" class="hero" @click="router.push({ name: 'a-voir' })">
      <div class="eyebrow">CE SOIR, SUR LE CANAPÉ</div>
      <div class="headline h-display">{{ data.ce_soir.headline }}</div>
      <div v-if="data.ce_soir.detail" class="detail">{{ data.ce_soir.detail }}</div>
    </div>

    <div>
      <div class="section-title h-display">La maison</div>
      <div class="modules">
        <div
          v-for="m in data.modules" :key="m.key" class="module"
          :class="{ active: m.active }" @click="goModule(m)"
        >
          <div class="pic">{{ m.pic }}</div>
          <div>
            <div class="mtitle h-display">{{ m.title }}</div>
            <div class="minfo">{{ m.info }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="data.last_session" class="card last-session">
      <div class="eyebrow">HIER SOIR</div>
      <div class="row" @click="router.push({ name: 'groupe', params: { id: data.last_session.group_id } })">
        <div class="icon">📺</div>
        <div class="text">{{ data.last_session.text }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 8px); gap: 18px; }
.header-row { display: flex; align-items: center; justify-content: space-between; }
.greeting { font-size: 30px; line-height: 1.1; }
.subline { font: 700 13px var(--font-body); margin-top: 6px; }
.avatars { display: flex; }

.hero {
  background: var(--sage);
  color: var(--sage-dark);
  border-radius: 22px;
  padding: 18px 20px;
  cursor: pointer;
  background-image: radial-gradient(circle at 90% 0%, #C4DAB8 0 70px, transparent 71px);
}
.hero:hover { background-color: var(--sage-hover); }
.eyebrow { font: 800 12px var(--font-body); opacity: 0.85; }
.headline { font-size: 21px; margin-top: 6px; }
.detail { font: 700 13px var(--font-body); margin-top: 4px; opacity: 0.9; }

.section-title { font-size: 15px; margin-bottom: 10px; }
.modules { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.module {
  background: var(--paper2);
  color: var(--ink-soft);
  border-radius: 20px;
  padding: 16px;
  min-height: 104px;
  cursor: default;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  opacity: 0.75;
  transition: transform 0.15s ease;
}
.module.active {
  background: var(--terra-chip);
  color: var(--terra-dark);
  cursor: pointer;
  opacity: 1;
}
.module.active:hover { transform: translateY(-2px); }
.pic { font-size: 24px; }
.mtitle { font-size: 16px; }
.minfo { font: 700 11px var(--font-body); margin-top: 2px; opacity: 0.85; }

.last-session { padding: 14px 16px; }
.row { display: flex; gap: 10px; align-items: center; cursor: pointer; margin-top: 8px; }
.row:hover { color: var(--terra-dark); }
.icon {
  width: 34px; height: 34px; border-radius: 12px; background: var(--terra-chip);
  display: flex; align-items: center; justify-content: center; font-size: 16px; flex: none;
}
.text { font: 700 13px var(--font-body); line-height: 1.4; }
</style>
