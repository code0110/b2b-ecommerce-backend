import { adminApi } from '@/services/http'

export async function fetchReceiptBooks(params = {}) {
  const { data } = await adminApi.get('/receipt-books', { params })
  return data
}

export async function createReceiptBook(payload) {
  const { data } = await adminApi.post('/receipt-books', payload)
  return data
}

export async function updateReceiptBook(id, payload) {
  const { data } = await adminApi.put(`/receipt-books/${id}`, payload)
  return data
}

export async function deleteReceiptBook(id) {
  await adminApi.delete(`/receipt-books/${id}`)
}

export async function fetchAgents() {
  const { data } = await adminApi.get('/receipt-books-agents')
  return data
}
