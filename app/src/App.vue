<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from './store/ui'
import { useAuthStore } from './store/auth'
import ModuleBadge from './components/ModuleBadge.vue'
import TabBar from './components/TabBar.vue'
import Toast from './components/Toast.vue'

const route = useRoute()
const ui = useUiStore()
const auth = useAuthStore()

const showModuleBadge = computed(() => route.meta.module === 'series')
const showTabs = computed(() => route.name !== 'onboarding')

onMounted(() => {
  if (auth.isAuthenticated && !auth.user) auth.fetchMe()
})
</script>

<template>
  <div class="app-shell">
    <div class="app-frame">
      <ModuleBadge v-if="showModuleBadge" />
      <RouterView />
      <TabBar v-if="showTabs" />
      <Toast v-if="ui.toast" :text="ui.toast" />
    </div>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100dvh;
  background: var(--paper);
  display: flex;
  justify-content: center;
}
.app-frame {
  width: 100%;
  max-width: 480px;
  min-height: 100dvh;
  background: var(--paper);
  display: flex;
  flex-direction: column;
  position: relative;
}
@media (min-width: 480px) {
  .app-shell { background: var(--border-strong); }
  .app-frame {
    box-shadow: 0 24px 60px rgba(84, 60, 38, 0.18);
    margin: 24px 0;
    min-height: calc(100dvh - 48px);
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid var(--border-strong);
  }
}
</style>
