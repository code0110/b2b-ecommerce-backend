import http from './http';

export const fetchWishlists = () => http.get('/wishlists');

export const fetchWishlist = (id) => http.get(`/wishlists/${id}`);

export const createWishlist = (data) => http.post('/wishlists', data);

export const updateWishlist = (id, data) => http.put(`/wishlists/${id}`, data);

export const deleteWishlist = (id) => http.delete(`/wishlists/${id}`);

export const toggleWishlistItem = (productId, wishlistId = null) => 
  http.post('/wishlists/toggle', { product_id: productId, wishlist_id: wishlistId });

export const mergeWishlist = (items) => http.post('/wishlists/merge', { items });

export const getSharedWishlist = (token) => http.get(`/wishlists/shared/${token}`);
