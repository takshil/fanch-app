<script setup>
</script>

<template>
  <div class="module-badge">
    <span class="chip">📺 Séries</span>
  </div>
</template>

<style scoped>
.module-badge {
  flex: none;
  display: flex;
  justify-content: center;
  padding: calc(env(safe-area-inset-top, 0px) + 10px) 20px 6px;
}
.chip {
  background: var(--terra-chip);
  color: var(--terra-dark);
  padding: 4px 14px;
  border-radius: 999px;
  font: 800 11px var(--font-body);
}
</style>
