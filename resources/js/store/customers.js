import { defineStore } from 'pinia'

const demoCustomers = [
  {
    id: 1,
    name: 'SC Demo Construct SRL',
    clientType: 'B2B', // B2B / B2C / Agent / Director / Operator
    email: 'contact@democonstruct.ro',
    phone: '+40 721 000 001',
    status: 'active', // active / blocked
    group: 'Distribuitori',
    erpId: 'CUST-B2B-001',
    customerCode: 'DEMOCONSTRUCT',
    paymentTermDays: 30,
    creditLimit: 50000,
    currentBalance: 12000,
    overdueBalance: 2000,
    currency: 'RON',
    targetAnnual: 250000,
    bonusScheme: 'Bonus 3% la atingerea targetului anual.',
    assignedAgent: 'Ion Popescu',
    assignedDirector: 'Maria Ionescu',
    workPoints: [
      {
        id: 1,
        name: 'Depozit principal',
        address: 'Str. Depozitelor nr. 10, București',
        contact: 'Depozit',
        isMain: true
      },
      {
        id: 2,
        name: 'Punct lucru Brașov',
        address: 'Str. Constructorilor nr. 5, Brașov',
        contact: 'Șef punct de lucru',
        isMain: false
      }
    ],
    billingAddresses: [
      {
        id: 1,
        label: 'Sediu social',
        address: 'Str. Exemplu nr. 1, București',
        isDefault: true
      }
    ],
    shippingAddresses: [
      {
        id: 1,
        label: 'Depozit principal',
        address: 'Str. Depozitelor nr. 10, București',
        isDefault: true
      },
      {
        id: 2,
        label: 'Punct lucru Brașov',
        address: 'Str. Constructorilor nr. 5, Brașov',
        isDefault: false
      }
    ],
    commercialConditions: {
      standardDiscount: 5,
      customRules: 'Discount suplimentar 2% pentru comenzile peste 20.000 RON.',
      paymentTermDays: 30,
      creditLimit: 50000
    },
    personalizedPricing: {
      hasContractPrices: true,
      notes: 'Prețuri contractuale pe game de produse.',
      productRules: []
    },
    dedicatedPromotions: [1], // IDs din promotions store
    companyUsers: [
      {
        id: 1,
        name: 'Admin companie',
        email: 'admin@democonstruct.ro',
        role: 'admin', // admin / order / approval
        needsApproval: false
      },
      {
        id: 2,
        name: 'Utilizator comenzi',
        email: 'comenzi@democonstruct.ro',
        role: 'order',
        needsApproval: true
      }
    ]
  },
  {
    id: 2,
    name: 'Ionescu Andrei',
    clientType: 'B2C',
    email: 'andrei.ionescu@example.com',
    phone: '+40 724 000 002',
    status: 'active',
    group: 'Clienți VIP',
    erpId: 'CUST-B2C-001',
    customerCode: 'ANDREII',
    paymentTermDays: 0,
    creditLimit: 0,
    currentBalance: 0,
    overdueBalance: 0,
    currency: 'RON',
    targetAnnual: null,
    bonusScheme: '',
    assignedAgent: null,
    assignedDirector: null,
    workPoints: [],
    billingAddresses: [
      {
        id: 1,
        label: 'Adresă facturare',
        address: 'Str. Exemplu nr. 20, Cluj-Napoca',
        isDefault: true
      }
    ],
    shippingAddresses: [
      {
        id: 1,
        label: 'Adresă livrare',
        address: 'Str. Exemplu nr. 21, Cluj-Napoca',
        isDefault: true
      }
    ],
    commercialConditions: {
      standardDiscount: 0,
      customRules: '',
      paymentTermDays: 0,
      creditLimit: 0
    },
    personalizedPricing: {
      hasContractPrices: false,
      notes: '',
      productRules: []
    },
    dedicatedPromotions: [],
    companyUsers: []
  }
]

export const useCustomersStore = defineStore('customers', {
  state: () => ({
    customers: [...demoCustomers],
    lastId: demoCustomers.length
  }),
  getters: {
    all: (state) => state.customers,
    getById: (state) => (id) => state.customers.find((c) => c.id === Number(id))
  },
  actions: {
    saveCustomer(payload) {
      if (payload.id) {
        const index = this.customers.findIndex((c) => c.id === payload.id)
        if (index !== -1) {
          this.customers[index] = { ...this.customers[index], ...payload }
        }
      } else {
        this.lastId += 1
        const newCustomer = { ...payload, id: this.lastId }
        this.customers.push(newCustomer)
      }
    }
  }
})
