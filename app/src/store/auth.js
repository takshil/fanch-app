import { defineStore } from 'pinia'
import client from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('fanch_token') || null,
    user: null,
    household: null,
    households: [],
    currentHouseholdId: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    setSession(data) {
      this.token = data.token ?? this.token
      this.user = data.user
      this.household = data.household
      this.households = data.households ?? []
      if (data.current_household_id !== undefined) this.currentHouseholdId = data.current_household_id
      else if (data.household) this.currentHouseholdId = data.household.id
      if (data.token) localStorage.setItem('fanch_token', data.token)
    },
    async register({ prenom, email, foyer }) {
      const { data } = await client.post('/auth/register', { prenom, email, foyer })
      this.setSession(data)
    },
    async join({ prenom, email, code }) {
      const { data } = await client.post('/auth/join', { prenom, email, code })
      this.setSession(data)
    },
    async fetchMe() {
      const { data } = await client.get('/me')
      this.setSession(data)
    },
    async createHousehold(name) {
      const { data } = await client.post('/households', { name })
      this.setSession(data)
    },
    async joinHousehold(code) {
      const { data } = await client.post('/households/join', { code })
      this.setSession(data)
    },
    async switchHousehold(householdId) {
      const { data } = await client.post(`/households/${householdId}/switch`)
      this.setSession(data)
      return data
    },
    async leaveHousehold(householdId) {
      const { data } = await client.delete(`/households/${householdId}/leave`)
      this.setSession(data)
    },
    logout() {
      this.token = null
      this.user = null
      this.household = null
      this.households = []
      this.currentHouseholdId = null
      localStorage.removeItem('fanch_token')
    },
  },
})
