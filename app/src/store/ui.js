import { defineStore } from 'pinia'

let timer = null

export const useUiStore = defineStore('ui', {
  state: () => ({
    toast: null,
  }),
  actions: {
    showToast(text, ms = 3500) {
      clearTimeout(timer)
      this.toast = text
      timer = setTimeout(() => { this.toast = null }, ms)
    },
  },
})
