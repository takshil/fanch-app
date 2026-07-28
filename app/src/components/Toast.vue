<script setup>
defineProps({ text: { type: String, required: true } })
</script>

<template>
  <div class="toast">{{ text }}</div>
</template>

<style scoped>
.toast {
  position: absolute;
  left: 20px;
  right: 20px;
  bottom: 96px;
  background: var(--toast-bg);
  color: var(--toast-text);
  padding: 14px 18px;
  font: 800 13px var(--font-body);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(64, 52, 42, 0.35);
  z-index: 20;
}
</style>
