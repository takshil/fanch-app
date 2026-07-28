<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()

const mode = ref('creer') // 'creer' | 'rejoindre'
const prenom = ref('')
const email = ref('')
const foyer = ref('')
const code = ref('')
const loading = ref(false)
const error = ref(null)

async function submit() {
  error.value = null
  loading.value = true
  try {
    if (mode.value === 'creer') {
      await auth.register({ prenom: prenom.value, email: email.value, foyer: foyer.value })
    } else {
      await auth.join({ prenom: prenom.value, email: email.value, code: code.value })
    }
    router.push({ name: 'accueil' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Une erreur est survenue, réessayez.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="screen">
    <div class="hero">
      <div class="brand h-display">fanch</div>
      <div class="tagline">L'assistant de la maisonnée. Il vit chez vous, pas dans un nuage.</div>
    </div>

    <div class="switch">
      <div class="switch-opt" :class="{ active: mode === 'creer' }" @click="mode = 'creer'">Créer un foyer</div>
      <div class="switch-opt" :class="{ active: mode === 'rejoindre' }" @click="mode = 'rejoindre'">J'ai une invitation</div>
    </div>

    <form class="fields" @submit.prevent="submit">
      <template v-if="mode === 'creer'">
        <label class="field">Votre prénom
          <input v-model="prenom" placeholder="Camille" required>
        </label>
        <label class="field">Email
          <input v-model="email" type="email" placeholder="camille@exemple.fr" required>
        </label>
        <label class="field">Le nom de votre foyer
          <input v-model="foyer" placeholder="Chez les Kerbrat" required>
        </label>
      </template>
      <template v-else>
        <label class="field">Votre prénom
          <input v-model="prenom" placeholder="Camille" required>
        </label>
        <label class="field">Email
          <input v-model="email" type="email" placeholder="camille@exemple.fr" required>
        </label>
        <label class="field">Code d'invitation
          <input v-model="code" placeholder="FANCH-7K2M" required>
        </label>
        <div class="hint">Vous gardez votre propre compte : vos séries vous suivent, même dans deux foyers.</div>
      </template>

      <div v-if="error" class="error">{{ error }}</div>

      <button type="submit" class="btn-primary" :disabled="loading">{{ loading ? 'Un instant…' : "C'est parti !" }}</button>
      <div class="foot faint">Aucun tracker · Vos données restent chez vous</div>
    </form>
  </div>
</template>

<style scoped>
.screen { padding-top: calc(env(safe-area-inset-top, 0px) + 12px); }
.hero {
  background: var(--terra);
  color: var(--terra-text);
  border-radius: 24px;
  padding: 28px 22px;
  background-image:
    radial-gradient(circle at 85% 15%, #F7CBB3 0 60px, transparent 61px),
    radial-gradient(circle at 10% 90%, #E8A483 0 40px, transparent 41px);
}
.brand { font-size: 40px; line-height: 1; }
.tagline { font: 700 15px var(--font-body); margin-top: 10px; line-height: 1.45; }

.switch {
  display: flex;
  background: var(--track);
  border-radius: 999px;
  padding: 4px;
  margin-top: 20px;
}
.switch-opt {
  flex: 1;
  text-align: center;
  padding: 9px;
  border-radius: 999px;
  cursor: pointer;
  font: 800 13px var(--font-body);
  color: var(--ink-soft);
}
.switch-opt.active {
  background: var(--paper2);
  color: var(--terra-dark);
  box-shadow: 0 2px 6px rgba(84, 60, 38, 0.12);
}

.fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 18px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font: 800 12px var(--font-body);
  color: var(--ink-soft);
}
.field input {
  border: 2px solid var(--border-strong);
  border-radius: 14px;
  padding: 12px 14px;
  background: var(--paper2);
  font: 600 15px var(--font-body);
  color: var(--ink);
  width: 100%;
}
.hint { font: 600 13px var(--font-body); color: var(--ink-soft); line-height: 1.5; }
.error { font: 700 13px var(--font-body); color: var(--terra-dark); }
.foot { font: 600 12px var(--font-body); text-align: center; }
</style>
