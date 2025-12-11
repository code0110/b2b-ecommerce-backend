import { defineStore } from 'pinia'

/**
 * Store demo pentru solicitări "Devino partener".
 */

const demoRequests = [
  {
    id: 1,
    companyName: 'SC Demo Construct SRL',
    cui: 'RO12345678',
    regCom: 'J40/1234/2020',
    contactPerson: 'Popescu Mihai',
    email: 'office@democonstruct.ro',
    phone: '+40 723 000 010',
    region: 'Sud / București - Ilfov',
    activityType: 'Distribuitor materiale construcții',
    status: 'pending', // pending | approved | rejected
    assignedAgent: 'Agent vânzări 1',
    createdAt: '2025-02-01T10:00:00'
  }
]

export const usePartnerRequestsStore = defineStore('partnerRequests', {
  state: () => ({
    requests: [...demoRequests],
    lastId: demoRequests.length
  }),
  getters: {
    all: (state) => state.requests
  },
  actions: {
    submitRequest(payload) {
      this.lastId += 1
      const now = new Date().toISOString()
      const request = {
        id: this.lastId,
        status: 'pending',
        assignedAgent: null,
        createdAt: now,
        ...payload
      }
      this.requests.push(request)
      return request
    },
    updateStatus(id, status) {
      const req = this.requests.find((r) => r.id === Number(id))
      if (!req) return
      req.status = status
    },
    assignAgent(id, agentName) {
      const req = this.requests.find((r) => r.id === Number(id))
      if (!req) return
      req.assignedAgent = agentName
    }
  }
})
