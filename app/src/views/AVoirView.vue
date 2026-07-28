<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import client from '../api/client'

const router = useRouter()
const items = ref(null)

async function load() {
  const { data } = await client.get('/a-voir')
  items.value = data.items
}
onMounted(load)

function open(item) {
  if (item.group_id) router.push({ name: 'groupe', params: { id: item.group_id } })
  else router.push({ name: 'serie', params: { slug: item.show.slug } })
}

async function toggle(item) {
  item.vu = !item.vu
  await client.post(`/episodes/${item.episode.id}/toggle`)
}
</script>

<template>
  <div class="screen">
    <div class="screen-title">À voir ce soir</div>

    <template v-if="items && items.length">
      <div v-for="item in items" :key="item.id" class="card row">
        <div class="poster" :style="{ background: item.show.color }" @click="open(item)">{{ item.show.initiale }}</div>
        <div class="info" @click="open(item)">
          <div class="title-row">
            <div class="serie h-display">{{ item.show.title }}</div>
            <span v-if="item.badge" class="badge">👥 {{ item.badge }}</span>
          </div>
          <div class="ep">{{ item.episode.code }} — {{ item.episode.title }}</div>
          <div class="meta muted">{{ item.show.info }}</div>
        </div>
        <div
          class="check-circle" title="Marquer vu"
          :style="{ background: item.vu ? 'var(--sage)' : 'transparent', borderColor: item.vu ? 'var(--sage)' : 'var(--ring-off)', border: '2px solid', width: '34px', height: '34px' }"
          @click="toggle(item)"
        >{{ item.vu ? '✓' : '' }}</div>
      </div>
      <div class="foot faint">Cocher ici ne compte que pour vous — jamais pour le groupe 💛</div>
    </template>
    <div v-else-if="items" class="empty muted">Rien de nouveau à voir pour l'instant — direction Recherche pour démarrer une série.</div>
  </div>
</template>

<style scoped>
.row {
  padding: 12px;
  display: flex;
  gap: 12px;
  align-items: center;
  box-shadow: 0 3px 0 var(--track);
}
.poster {
  width: 58px; height: 80px; flex: none; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: var(--terra-text); font: 800 26px var(--font-display); cursor: pointer;
}
.info { flex: 1; cursor: pointer; min-width: 0; }
.title-row { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.serie { font-size: 16px; }
.badge {
  background: var(--sage-chip); color: var(--sage-chip-text);
  padding: 2px 10px; border-radius: 999px; font: 800 11px var(--font-body);
}
.ep { font: 800 14px var(--font-body); color: var(--terra-dark); margin-top: 3px; }
.meta { font: 700 12px var(--font-body); margin-top: 2px; }
.foot { text-align: center; font: 600 12px var(--font-body); }
.empty { text-align: center; padding: 32px 12px; font: 700 13px var(--font-body); line-height: 1.5; }
</style>
