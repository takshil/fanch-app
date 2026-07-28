import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  { path: '/onboarding', name: 'onboarding', component: () => import('../views/OnboardingView.vue'), meta: { module: 'hub', public: true } },
  { path: '/', name: 'accueil', component: () => import('../views/AccueilView.vue'), meta: { module: 'hub' } },
  { path: '/reglages', name: 'reglages', component: () => import('../views/ReglagesView.vue'), meta: { module: 'hub' } },

  { path: '/a-voir', name: 'a-voir', component: () => import('../views/AVoirView.vue'), meta: { module: 'series', tab: 'a-voir' } },
  { path: '/serie/:slug', name: 'serie', component: () => import('../views/SerieView.vue'), meta: { module: 'series', tab: 'a-voir' } },
  { path: '/groupe/:id', name: 'groupe', component: () => import('../views/GroupeView.vue'), meta: { module: 'series', tab: 'a-voir' } },
  { path: '/groupe/:id/seance', name: 'seance', component: () => import('../views/SeanceView.vue'), meta: { module: 'series', tab: 'a-voir' } },
  { path: '/recherche', name: 'recherche', component: () => import('../views/RechercheView.vue'), meta: { module: 'series', tab: 'recherche' } },
  { path: '/calendrier', name: 'calendrier', component: () => import('../views/CalendrierView.vue'), meta: { module: 'series', tab: 'calendrier' } },
  { path: '/watchlist', name: 'watchlist', component: () => import('../views/WatchlistView.vue'), meta: { module: 'series', tab: 'watchlist' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!auth.isAuthenticated && !to.meta.public) {
    return { name: 'onboarding' }
  }
  if (auth.isAuthenticated && to.name === 'onboarding') {
    return { name: 'accueil' }
  }
  return true
})

export default router
