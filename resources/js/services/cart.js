// resources/js/services/cart.js
import api from './http';

/**
 * GET /api/cart
 */
export async function getCart() {
  const { data } = await api.get('/cart');
  return data;
}

/**
 * POST /api/cart/items
 * payload: { product_id, product_variant_id?, quantity }
 */
export async function addCartItem(payload) {
  const { data } = await api.post('/cart/items', payload);
  return data;
}

/**
 * PUT /api/cart/items/{id}
 *
 * Poate fi apelată în două moduri:
 *  - updateCartItem(id, 3) -> quantity = 3
 *  - updateCartItem(id, { quantity: 3 })
 */
export async function updateCartItem(itemId, data) {
  const payload =
    typeof data === 'number'
      ? { quantity: data }
      : data;

  const { data: res } = await api.put(`/cart/items/${itemId}`, payload);
  return res;
}

/**
 * DELETE /api/cart/items/{id}
 */
export async function removeCartItem(itemId) {
  const { data } = await api.delete(`/cart/items/${itemId}`);
  return data;
}

/**
 * DELETE /api/cart
 */
export async function clearCart() {
  const { data } = await api.delete('/cart');
  return data;
}

/**
 * POST /api/cart/promotions/{id}
 * Add all products from a promotion to the cart.
 */
export async function addPromotionToCart(promotionId) {
  const { data } = await api.post(`/cart/promotions/${promotionId}`);
  return data;
}

/**
 * GET /api/checkout/summary
 * (roută protejată de auth:sanctum în backend)
 */
export async function getCheckoutSummary() {
  const { data } = await api.get('/checkout/summary');
  return data;
}

/**
 * POST /api/checkout/place-order
 * payload:
 *  - shipping_method_id
 *  - billing_address_id
 *  - shipping_address_id
 *  - payment_method: 'card' | 'op' | 'b2b_terms'
 */
export async function placeOrder(payload) {
  const { data } = await api.post('/checkout/place-order', payload);
  return data;
}
