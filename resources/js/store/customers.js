import { defineStore } from 'pinia'
import { 
  fetchCustomers, 
  fetchCustomer, 
  createCustomer, 
  updateCustomer, 
  deleteCustomer 
} from '@/services/admin/customers'

// Helper to map backend data to frontend model
const mapCustomer = (c) => ({
  id: c.id,
  name: c.name,
  legalName: c.legal_name,
  cif: c.cif,
  regCom: c.reg_com,
  iban: c.iban,
  clientType: c.type === 'b2b' ? 'B2B' : 'B2C',
  email: c.email,
  phone: c.phone,
  status: c.is_active ? 'active' : 'blocked',
  isPartner: !!c.is_partner,
  group: c.group?.name || '',
  groupId: c.group_id,
  customerCode: c.cif || c.id.toString(), // Fallback
  erpId: c.reg_com || '',
  paymentTermDays: c.payment_terms_days || 0,
  creditLimit: c.credit_limit || 0,
  currentBalance: c.current_balance || 0,
  overdueBalance: 0, // Backend doesn't provide this yet, assuming 0 or need calculation
  currency: c.currency || 'RON',
  assignedAgent: c.agent ? `${c.agent.first_name} ${c.agent.last_name}` : null,
  assignedAgentId: c.agent_user_id,
  assignedDirector: c.sales_director ? `${c.sales_director.first_name} ${c.sales_director.last_name}` : null,
  assignedDirectorId: c.sales_director_user_id,
  
  // Keep some demo structures if needed or map them if backend supports
  workPoints: c.addresses || [], 
  billingAddresses: c.addresses ? c.addresses.filter(a => a.type === 'billing') : [],
  shippingAddresses: c.addresses ? c.addresses.filter(a => a.type === 'shipping') : [],
  
  // Extra fields that might be needed for UI but not yet in backend
  commercialConditions: {
    paymentTermDays: c.payment_terms_days || 0,
    creditLimit: c.credit_limit || 0
  },
  
  companyUsers: c.users || []
})

export const useCustomersStore = defineStore('customers', {
  state: () => ({
    customers: [],
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0
    }
  }),

  getters: {
    all: (state) => state.customers,
    getById: (state) => (id) => state.customers.find((c) => c.id === Number(id))
  },

  actions: {
    async fetchCustomers(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await fetchCustomers(params)
        // Response structure: { data: [...], meta: { ... }, links: { ... } }
        if (response.data) {
          this.customers = response.data.map(mapCustomer)
          this.pagination = {
            currentPage: response.meta?.current_page || 1,
            lastPage: response.meta?.last_page || 1,
            total: response.meta?.total || 0
          }
        }
      } catch (err) {
        this.error = err.message || 'Failed to fetch customers'
        console.error('Error fetching customers:', err)
      } finally {
        this.loading = false
      }
    },

    async fetchCustomer(id) {
      this.loading = true
      this.error = null
      try {
        const data = await fetchCustomer(id)
        const mapped = mapCustomer(data)
        
        const index = this.customers.findIndex(c => c.id === mapped.id)
        if (index !== -1) {
          this.customers[index] = mapped
        } else {
          this.customers.push(mapped)
        }
        return mapped
      } catch (err) {
        this.error = err.message || 'Failed to fetch customer details'
        console.error('Error fetching customer:', err)
        throw err
      } finally {
        this.loading = false
      }
    },

    async createCustomer(payload) {
      this.loading = true
      try {
        // Map frontend payload to backend structure
        const backendPayload = {
          name: payload.name,
          legal_name: payload.legalName,
          type: payload.clientType === 'B2B' ? 'b2b' : 'b2c',
          cif: payload.cif,
          reg_com: payload.regCom,
          iban: payload.iban,
          email: payload.email,
          phone: payload.phone,
          is_active: payload.status === 'active',
          is_partner: payload.isPartner,
          group_id: payload.groupId,
          payment_terms_days: payload.paymentTermDays,
          credit_limit: payload.creditLimit,
          current_balance: payload.currentBalance,
          currency: payload.currency,
          agent_user_id: payload.assignedAgentId,
          sales_director_user_id: payload.assignedDirectorId,
          // Add other fields as needed
        }

        const data = await createCustomer(backendPayload)
        const mapped = mapCustomer(data)
        this.customers.push(mapped)
        return mapped
      } catch (err) {
        this.error = err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async updateCustomer(id, payload) {
      this.loading = true
      try {
         // Map frontend payload to backend structure
         // Only include fields that are present in payload
        const backendPayload = {}
        if (payload.name !== undefined) backendPayload.name = payload.name
        if (payload.clientType !== undefined) backendPayload.type = payload.clientType === 'B2B' ? 'b2b' : 'b2c'
        if (payload.email !== undefined) backendPayload.email = payload.email
        if (payload.phone !== undefined) backendPayload.phone = payload.phone
        if (payload.status !== undefined) backendPayload.is_active = payload.status === 'active'
        if (payload.isPartner !== undefined) backendPayload.is_partner = payload.isPartner
        if (payload.groupId !== undefined) backendPayload.group_id = payload.groupId
        if (payload.paymentTermDays !== undefined) backendPayload.payment_terms_days = payload.paymentTermDays
        if (payload.creditLimit !== undefined) backendPayload.credit_limit = payload.creditLimit
        if (payload.currentBalance !== undefined) backendPayload.current_balance = payload.currentBalance
        if (payload.currency !== undefined) backendPayload.currency = payload.currency
        if (payload.assignedAgentId !== undefined) backendPayload.agent_user_id = payload.assignedAgentId
        if (payload.assignedDirectorId !== undefined) backendPayload.sales_director_user_id = payload.assignedDirectorId
        
        const data = await updateCustomer(id, backendPayload)
        const mapped = mapCustomer(data)
        
        const index = this.customers.findIndex(c => c.id === id)
        if (index !== -1) {
          this.customers[index] = mapped
        }
        return mapped
      } catch (err) {
        this.error = err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async deleteCustomer(id) {
      this.loading = true
      try {
        await deleteCustomer(id)
        this.customers = this.customers.filter(c => c.id !== id)
      } catch (err) {
        this.error = err.message
        throw err
      } finally {
        this.loading = false
      }
    }
  }
})
