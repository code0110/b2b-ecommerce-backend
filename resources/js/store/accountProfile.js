import { defineStore } from 'pinia'

/**
 * Store demo pentru profilul de cont (adrese, utilizatori companie, șabloane comenzi recurente).
 * Într-un proiect real, aceste date vin din backend / ERP și sunt filtrate pe utilizatorul logat.
 */

const initialAddresses = [
  {
    id: 1,
    type: 'shipping',
    label: 'Depozit principal',
    contactName: 'Popescu Mihai',
    companyName: 'SC Demo Construct SRL',
    street: 'Str. Depozitului 10',
    city: 'București',
    county: 'București',
    zip: '010101',
    country: 'România',
    isDefault: true
  },
  {
    id: 2,
    type: 'shipping',
    label: 'Șantier 1',
    contactName: 'Popescu Mihai',
    companyName: 'SC Demo Construct SRL',
    street: 'Șos. Șantierului 5',
    city: 'Ploiești',
    county: 'Prahova',
    zip: '100200',
    country: 'România',
    isDefault: false
  },
  {
    id: 3,
    type: 'billing',
    label: 'Sediu social',
    contactName: 'Popescu Mihai',
    companyName: 'SC Demo Construct SRL',
    street: 'Bd. Principal 1',
    city: 'București',
    county: 'București',
    zip: '010102',
    country: 'România',
    isDefault: true
  }
]

const initialCompanyUsers = [
  {
    id: 1,
    name: 'Popescu Mihai',
    email: 'mihai.popescu@democonstruct.ro',
    role: 'admin', // admin / buyer / approver
    mustApprove: false,
    status: 'active', // active / pending / disabled
    invitedAt: '2025-01-10',
    activatedAt: '2025-01-11'
  },
  {
    id: 2,
    name: 'Ionescu Andrei',
    email: 'andrei.ionescu@democonstruct.ro',
    role: 'buyer',
    mustApprove: true,
    status: 'active',
    invitedAt: '2025-01-15',
    activatedAt: '2025-01-16'
  }
]

const initialOrderTemplates = [
  {
    id: 1,
    name: 'Șablon lunar gips-carton',
    description: 'Comandă recurentă pentru lucrările standard de finisaje.',
    lastUsedAt: '2025-02-01',
    totalGross: 3150,
    currency: 'RON',
    lines: [
      { productId: 1, name: 'Placă gips-carton 12.5mm', code: 'PGC-12.5', qty: 100, unit: 'buc' },
      { productId: 2, name: 'Profil metalic UW 50', code: 'UW-50', qty: 40, unit: 'buc' }
    ]
  }
]

export const useAccountProfileStore = defineStore('accountProfile', {
  state: () => ({
    addresses: [...initialAddresses],
    companyUsers: [...initialCompanyUsers],
    orderTemplates: [...initialOrderTemplates],
    lastAddressId: initialAddresses.length,
    lastUserId: initialCompanyUsers.length,
    lastTemplateId: initialOrderTemplates.length,
    companyData: {
      name: 'SC Demo Construct SRL',
      cui: 'RO12345678',
      regCom: 'J40/1234/2020',
      iban: 'RO49AAAA1B31007593840000',
      contactPerson: 'Popescu Mihai',
      email: 'contact@democonstruct.ro',
      phone: '+40 721 000 001'
    }
  }),
  getters: {
    shippingAddresses: (state) => state.addresses.filter((a) => a.type === 'shipping'),
    billingAddresses: (state) => state.addresses.filter((a) => a.type === 'billing'),
    defaultShipping: (state) =>
      state.addresses.find((a) => a.type === 'shipping' && a.isDefault) || null,
    defaultBilling: (state) =>
      state.addresses.find((a) => a.type === 'billing' && a.isDefault) || null,
    activeCompanyUsers: (state) => state.companyUsers.filter((u) => u.status === 'active'),
    allTemplates: (state) => state.orderTemplates
  },
  actions: {
    setDefaultAddress(addressId) {
      const addr = this.addresses.find((a) => a.id === addressId)
      if (!addr) return
      const type = addr.type
      this.addresses.forEach((a) => {
        if (a.type === type) a.isDefault = a.id === addressId
      })
    },
    addAddress(payload) {
      this.lastAddressId += 1
      const addr = {
        id: this.lastAddressId,
        isDefault: false,
        ...payload
      }
      this.addresses.push(addr)
      return addr
    },
    inviteUser(payload) {
      this.lastUserId += 1
      const now = new Date().toISOString().slice(0, 10)
      const user = {
        id: this.lastUserId,
        status: 'pending',
        invitedAt: now,
        activatedAt: null,
        mustApprove: false,
        ...payload
      }
      this.companyUsers.push(user)
      return user
    },
    addOrderTemplate(payload) {
      this.lastTemplateId += 1
      const tpl = {
        id: this.lastTemplateId,
        ...payload
      }
      this.orderTemplates.push(tpl)
      return tpl
    }
  }
})
