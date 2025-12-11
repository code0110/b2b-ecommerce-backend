import { defineStore } from 'pinia'

/**
 * Store demo pentru sistemul de tichete (suport / mesaje).
 */

const demoTickets = [
  {
    id: 1,
    code: 'TCK-0001',
    subject: 'Întrebare despre status comandă BC-100001',
    category: 'Comenzi',
    customerName: 'Ionescu Andrei',
    customerEmail: 'andrei.ionescu@example.com',
    customerType: 'B2C',
    createdAt: '2025-02-10T10:15:00',
    status: 'open', // open | in_progress | resolved
    priority: 'normal', // low | normal | high
    assignedTo: 'Operator suport 1',
    relatedOrderId: 1,
    messages: [
      {
        id: 11,
        from: 'client',
        author: 'Ionescu Andrei',
        createdAt: '2025-02-10T10:15:00',
        message: 'Bună ziua, care este statusul comenzii mele BC-100001?'
      },
      {
        id: 12,
        from: 'support',
        author: 'Operator suport 1',
        createdAt: '2025-02-10T11:00:00',
        message: 'Comanda este în curs de livrare. AWB-ul va fi generat în cursul zilei de azi.'
      }
    ]
  },
  {
    id: 2,
    code: 'TCK-0002',
    subject: 'Solicitare ofertă proiect - materiale gips-carton',
    category: 'Ofertare',
    customerName: 'SC Demo Construct SRL',
    customerEmail: 'office@democonstruct.ro',
    customerType: 'B2B',
    createdAt: '2025-02-12T09:30:00',
    status: 'in_progress',
    priority: 'high',
    assignedTo: 'Agent vânzări 1',
    relatedOrderId: null,
    messages: [
      {
        id: 21,
        from: 'client',
        author: 'Popescu Mihai',
        createdAt: '2025-02-12T09:30:00',
        message:
          'Salut, avem un proiect nou și avem nevoie de o ofertă pentru sistem complet gips-carton.'
      },
      {
        id: 22,
        from: 'support',
        author: 'Agent vânzări 1',
        createdAt: '2025-02-12T10:10:00',
        message: 'Mulțumim pentru solicitare, pregătim oferta și revenim în cursul zilei.'
      }
    ]
  },
  {
    id: 3,
    code: 'TCK-0003',
    subject: 'Factură neprimita pe email',
    category: 'Facturare',
    customerName: 'Ionescu Andrei',
    customerEmail: 'andrei.ionescu@example.com',
    customerType: 'B2C',
    createdAt: '2025-02-15T08:45:00',
    status: 'resolved',
    priority: 'low',
    assignedTo: 'Operator suport 2',
    relatedOrderId: 3,
    messages: [
      {
        id: 31,
        from: 'client',
        author: 'Ionescu Andrei',
        createdAt: '2025-02-15T08:45:00',
        message: 'Nu am primit factura pe email pentru comanda BC-100002.'
      },
      {
        id: 32,
        from: 'support',
        author: 'Operator suport 2',
        createdAt: '2025-02-15T09:15:00',
        message: 'Am retrimis factura pe email. Te rog verifică și folderul Spam.'
      },
      {
        id: 33,
        from: 'client',
        author: 'Ionescu Andrei',
        createdAt: '2025-02-15T09:30:00',
        message: 'Am primit acum, mulțumesc!'
      }
    ]
  }
]

export const useTicketsStore = defineStore('tickets', {
  state: () => ({
    tickets: [...demoTickets]
  }),
  getters: {
    all: (state) => state.tickets,
    getById: (state) => (id) => state.tickets.find((t) => t.id === Number(id))
  },
  actions: {
    addInternalNote(ticketId, note) {
      const ticket = this.tickets.find((t) => t.id === Number(ticketId))
      if (!ticket) return
      const nextId = ticket.messages.length
        ? Math.max(...ticket.messages.map((m) => m.id)) + 1
        : 1
      ticket.messages.push({
        id: nextId,
        from: 'support',
        author: 'Operator curent',
        createdAt: new Date().toISOString(),
        message: note
      })
    },
    addCustomerMessage(ticketId, message, payload = {}) {
      const ticket = this.tickets.find((t) => t.id === Number(ticketId))
      if (!ticket) return
      const nextId = ticket.messages.length
        ? Math.max(...ticket.messages.map((m) => m.id)) + 1
        : 1
      const authorName = payload.authorName || ''
      const authorEmail = payload.authorEmail || ''
      ticket.messages.push({
        id: nextId,
        from: 'client',
        author: authorName || authorEmail || 'Client',
        createdAt: new Date().toISOString(),
        message
      })
    },
    createTicket(payload) {
      const now = new Date().toISOString()
      const nextId = this.tickets.length
        ? Math.max(...this.tickets.map((t) => t.id)) + 1
        : 1
      const ticket = {
        id: nextId,
        code: 'TCK-' + String(nextId).padStart(4, '0'),
        subject: payload.subject,
        category: payload.category || 'General',
        customerName: payload.customerName || '',
        customerEmail: payload.customerEmail || '',
        customerType: payload.customerType || 'B2C',
        createdAt: now,
        status: 'open',
        priority: payload.priority || 'normal',
        assignedTo: null,
        relatedOrderId: payload.relatedOrderId || null,
        messages: [
          {
            id: 1,
            from: 'client',
            author: payload.customerName || payload.customerEmail || 'Client',
            createdAt: now,
            message: payload.message || ''
          }
        ]
      }
      this.tickets.push(ticket)
      return ticket
    }
  }
})
