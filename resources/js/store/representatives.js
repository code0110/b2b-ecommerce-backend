import { defineStore } from 'pinia'

/**
 * Store demo pentru reprezentanți de vânzări.
 */

const demoRepresentatives = [
  {
    id: 1,
    name: 'Ion Popescu',
    phone: '+40 723 000 001',
    email: 'ion.popescu@example.com',
    region: 'Sud / București - Ilfov',
    counties: ['București', 'Ilfov'],
    areas: ['București', 'Popești-Leordeni', 'Otopeni']
  },
  {
    id: 2,
    name: 'Maria Ionescu',
    phone: '+40 723 000 002',
    email: 'maria.ionescu@example.com',
    region: 'Transilvania',
    counties: ['Cluj', 'Alba', 'Sibiu'],
    areas: ['Cluj-Napoca', 'Turda', 'Alba Iulia', 'Sibiu']
  },
  {
    id: 3,
    name: 'Andrei Georgescu',
    phone: '+40 723 000 003',
    email: 'andrei.georgescu@example.com',
    region: 'Moldova',
    counties: ['Iași', 'Bacău'],
    areas: ['Iași', 'Pașcani', 'Bacău']
  }
]

export const useRepresentativesStore = defineStore('representatives', {
  state: () => ({
    reps: [...demoRepresentatives]
  }),
  getters: {
    all: (state) => state.reps
  }
})
