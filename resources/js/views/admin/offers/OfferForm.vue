<template>
  <div class="container-fluid py-4 bg-gray-50">
    <!-- Header -->
    <div class="row mb-4 align-items-center justify-content-between g-3">
       <!-- Title & Back -->
       <div class="col-12 col-md-auto d-flex align-items-center gap-3">
          <button class="btn btn-light rounded-circle shadow-sm" @click="router.back()">
             <i class="bi bi-arrow-left"></i>
          </button>
          <div>
             <h1 class="h3 fw-bold mb-1">{{ isEdit ? `Editare Ofertă #${route.params.id}` : 'Ofertă Nouă' }}</h1>
             <div class="d-flex align-items-center gap-2 text-muted small">
                <i class="bi bi-calendar3"></i>
                <span>Creat: {{ formatDate(new Date()) }}</span>
                <span v-if="currentStatus" class="badge rounded-pill ms-2" :class="statusBadge(currentStatus)">
                    {{ statusLabel(currentStatus) }}
                </span>
             </div>
          </div>
       </div>

       <!-- Actions -->
       <div class="col-12 col-md-auto d-flex gap-2 flex-wrap justify-content-end">
           <!-- Status Actions (if Edit) -->
           <template v-if="isEdit">
               <button v-if="currentStatus === 'draft'" class="btn btn-primary" @click="changeStatus('sent')">
                    <i class="bi bi-send-fill me-2"></i> Trimite la Client
               </button>

               <div v-if="currentStatus === 'pending_approval' && canApprove" class="btn-group">
                    <button class="btn btn-success" @click="changeStatus('approved')">
                        <i class="bi bi-check-lg me-2"></i> Aprobă
                    </button>
                    <button class="btn btn-danger" @click="changeStatus('rejected')">
                        <i class="bi bi-x-lg me-2"></i> Respinge
                    </button>
               </div>

               <button v-if="['sent', 'approved', 'accepted'].includes(currentStatus)" class="btn btn-success" @click="convertToOrder">
                    <i class="bi bi-cart-check me-2"></i> Comandă
               </button>

               <div v-if="['sent', 'negotiation', 'approved'].includes(currentStatus)" class="btn-group">
                    <button class="btn btn-outline-primary bg-white" @click="changeStatus('completed')">
                        <i class="bi bi-check-circle me-2"></i> Finalizează
                    </button>
                    <button class="btn btn-outline-danger bg-white" @click="changeStatus('rejected')">
                        Anulează
                    </button>
               </div>
           </template>
           
           <button class="btn btn-primary px-4 shadow-sm" @click="saveOffer" :disabled="saving || !isValid">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-save me-2" v-else></i>
                {{ isEdit ? 'Salvează' : 'Creează Ofertă' }}
           </button>
       </div>
    </div>

    <div class="row g-4">
        <!-- Main Content (Products & Details) -->
        <div class="col-lg-8">
            <!-- Customer Section -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-8 p-4 border-end">
                             <h6 class="text-uppercase text-muted fw-bold small mb-3">
                                <i class="bi bi-building me-2"></i>Client & Facturare
                             </h6>
                             
                             <div v-if="!form.customer" class="text-center py-4 bg-light rounded border border-dashed cursor-pointer" @click="$refs.customerSelectorRef?.$el.querySelector('input')?.focus()">
                                <i class="bi bi-person-plus fs-3 text-muted mb-2"></i>
                                <p class="mb-0 text-muted">Selectează un client pentru a începe</p>
                                <div class="mt-3 w-75 mx-auto">
                                     <CustomerSelector @select="selectCustomer" ref="customerSelectorRef" :disabled="isCustomerLocked" />
                                </div>
                             </div>

                             <div v-else class="d-flex align-items-start gap-3 animate__animated animate__fadeIn">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <span class="fw-bold fs-4">{{ form.customer.name.charAt(0) }}</span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="fw-bold mb-1">{{ form.customer.name }}</h5>
                                        <button v-if="!isCustomerLocked" class="btn btn-link btn-sm p-0 text-muted" @click="form.customer = null; form.customer_id = null">
                                            <i class="bi bi-pencil"></i> Modifică
                                        </button>
                                    </div>
                                    <p class="text-muted mb-1"><i class="bi bi-card-text me-2"></i>{{ form.customer.cif }}</p>
                                    <p class="text-muted mb-1"><i class="bi bi-geo-alt me-2"></i>{{ form.customer.address || form.customer.city || 'Adresă nespecificată' }}</p>
                                    
                                    <div class="d-flex gap-2 mt-2">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-cash-stack me-1"></i> Sold: {{ formatPrice(form.customer.current_balance || 0) }}</span>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-hourglass-split me-1"></i> Termen: {{ form.customer.payment_terms_days || 0 }} zile</span>
                                    </div>
                                </div>
                             </div>
                        </div>
                        <div class="col-md-4 p-4 bg-light">
                             <h6 class="text-uppercase text-muted fw-bold small mb-3">
                                <i class="bi bi-info-circle me-2"></i>Detalii Ofertă
                             </h6>
                             
                             <div class="mb-3">
                                <label class="form-label small text-muted mb-1">Valabilitate</label>
                                <input type="date" class="form-control bg-white border-0 shadow-sm" v-model="form.valid_until">
                             </div>
                             
                             <div class="mb-0">
                                <label class="form-label small text-muted mb-1">Referință / Note</label>
                                <textarea class="form-control bg-white border-0 shadow-sm" rows="3" v-model="form.notes" placeholder="Note interne sau mesaj pentru client..."></textarea>
                             </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <!-- Sidebar (Summary & Chat) -->
        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-3 opacity-10">
                    <i class="bi bi-receipt fs-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <h5 class="fw-bold mb-4 border-bottom border-white border-opacity-25 pb-3">Total Ofertă</h5>
                    
                    <div class="d-flex justify-content-between mb-2 opacity-75">
                        <span>Valoare Brută:</span>
                        <span>{{ formatPrice(totals.gross) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-warning">
                        <span><i class="bi bi-stars me-1"></i>Discount Total:</span>
                        <span>-{{ formatPrice(totals.discount) }}</span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-white border-opacity-25">
                        <span class="h5 mb-0">Total Net:</span>
                        <span class="h2 mb-0 fw-bold">{{ formatPrice(totals.net) }}</span>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-center small opacity-75">
                        Prețurile includ TVA conform legislației în vigoare.
                    </div>
                </div>
            </div>
            
            <div v-if="requiresDerogation" class="alert alert-warning border-start border-warning border-4 shadow-sm mb-4">
                <div class="d-flex">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Necesită Aprobare</h6>
                        <p class="mb-0 small">Această ofertă conține discount-uri peste limita de {{ config.approvalThreshold }}% și va necesita aprobarea Directorului de Vânzări înainte de a putea fi trimisă.</p>
                    </div>
                </div>
            </div>

            <!-- Action History / Chat -->
            <div class="card shadow-sm border-0" v-if="isEdit" style="height: 500px; display: flex; flex-direction: column;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-chat-dots me-2"></i>Istoric & Discuții</h6>
                </div>
                <div class="card-body bg-light p-0 d-flex flex-column" style="flex: 1; overflow: hidden;">
                     <div class="flex-grow-1 p-3 overflow-auto" ref="messagesContainer">
                        <div v-if="messages.length === 0" class="text-center text-muted py-5">
                            <i class="bi bi-chat-square-text fs-1 mb-2 opacity-50"></i>
                            <p class="small">Nu există mesaje sau note.</p>
                        </div>
                        <div v-for="msg in sortedMessages" :key="msg.id" class="mb-3 d-flex" :class="isMe(msg.user_id) ? 'justify-content-end' : 'justify-content-start'">
                            <div class="d-flex flex-column" :class="isMe(msg.user_id) ? 'align-items-end' : 'align-items-start'" style="max-width: 85%;">
                                <div class="p-3 shadow-sm" 
                                     :class="[
                                        isMe(msg.user_id) ? 'bg-primary text-white rounded-top-left rounded-bottom' : 'bg-white text-dark rounded-top-right rounded-bottom border',
                                        msg.is_internal ? 'border-warning border-2' : ''
                                     ]"
                                     style="border-radius: 1rem;">
                                    <div class="small fw-bold mb-1 opacity-75" v-if="!isMe(msg.user_id)">{{ msg.user?.name }}</div>
                                    <div class="text-break">{{ msg.message }}</div>
                                </div>
                                 <div class="small text-muted mt-1 d-flex align-items-center gap-1">
                                    <span v-if="msg.is_internal" class="badge bg-warning text-dark" style="font-size: 0.6rem;">INTERN</span>
                                    <span style="font-size: 0.7rem;">{{ formatDate(msg.created_at, true) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-white border-top">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" @click="isInternalMessage = !isInternalMessage" :class="{'text-warning border-warning bg-warning bg-opacity-10': isInternalMessage}" title="Mesaj Intern (invizibil clientului)">
                                <i class="bi" :class="isInternalMessage ? 'bi-eye-slash-fill' : 'bi-eye'"></i>
                            </button>
                            <input type="text" class="form-control border-secondary border-opacity-25" placeholder="Scrie un mesaj..." v-model="newMessage" @keydown.enter="sendMessage">
                            <button class="btn btn-primary" type="button" @click="sendMessage" :disabled="!newMessage.trim()">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between mt-2 px-1">
                            <span class="small" :class="isInternalMessage ? 'text-warning fw-bold' : 'text-muted'">
                                <i class="bi" :class="isInternalMessage ? 'bi-lock-fill' : 'bi-globe'"></i>
                                {{ isInternalMessage ? 'Mesaj Intern (Privat)' : 'Mesaj Vizibil Clientului' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section Full Width -->
    <div class="row g-4">
        <div class="col-12">
            <!-- Products Section -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1">Produse & Servicii</h5>
                        <p class="text-muted small mb-0">Gestionează lista de produse pentru această ofertă</p>
                    </div>
                    
                    <div class="d-flex gap-2">
                         <button class="btn btn-light btn-sm text-primary border" @click="recalculateAllPrices" :disabled="form.items.length === 0 || calculating">
                            <i class="bi bi-arrow-clockwise" :class="{'spinner-border spinner-border-sm': calculating}"></i>
                            <span class="d-none d-md-inline ms-1">Actualizează Prețuri</span>
                        </button>
                    </div>
                </div>
                
                <div class="p-4 bg-light border-bottom">
                     <div class="text-center py-3">
                        <button class="btn btn-outline-primary w-100 py-4 border-dashed shadow-sm hover-shadow-md transition-all" @click="showProductModal = true">
                            <i class="bi bi-cart-plus display-6 mb-2 d-block text-primary"></i>
                            <span class="h5 fw-bold d-block mb-1">Adaugă Produse</span>
                            <span class="text-muted small">Deschide catalogul pentru a selecta produsele dorite</span>
                        </button>
                     </div>
                </div>

                <!-- Product Modal Overlay -->
                <div v-if="showProductModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-0 p-md-4" style="z-index: 2000; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
                    <div class="bg-white rounded-0 rounded-md-3 shadow-lg w-100 h-100 d-flex flex-column overflow-hidden animate__animated animate__zoomIn" style="max-width: 1400px; max-height: 100%;">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
                            <div>
                                <h5 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Catalog Produse</h5>
                                <small class="text-muted">Selectează produsele pentru ofertă</small>
                            </div>
                            <button class="btn btn-close" @click="showProductModal = false"></button>
                        </div>
                        <div class="flex-grow-1 overflow-hidden position-relative bg-light">
                             <ProductSelector @select="onProductSelect" @check-promotions="onCheckPromotions" />
                        </div>
                        <div class="p-3 border-top bg-white d-flex justify-content-end">
                            <button class="btn btn-secondary px-4" @click="showProductModal = false">Închide</button>
                        </div>
                    </div>
                </div>

                <!-- Promotions Modal -->
                <div v-if="showPromotionsModal" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-0 p-md-4" style="z-index: 2100; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
                    <div class="bg-white rounded-3 shadow-lg w-100 h-auto d-flex flex-column overflow-hidden animate__animated animate__fadeInUp" style="max-width: 600px; max-height: 90%;">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
                            <div>
                                <h5 class="mb-0 fw-bold"><i class="bi bi-tags-fill me-2 text-danger"></i>Promoții Disponibile</h5>
                                <small class="text-muted" v-if="currentPromoProduct">{{ currentPromoProduct.name }}</small>
                            </div>
                            <button class="btn btn-close" @click="closePromotionsModal"></button>
                        </div>
                        
                        <div class="flex-grow-1 overflow-y-auto p-4 bg-light">
                             <div v-if="loadingPromotions" class="text-center py-5">
                                <div class="spinner-border text-primary mb-2" role="status"></div>
                                <p class="text-muted small">Căutăm promoții...</p>
                             </div>
                             
                             <div v-else-if="currentProductPromotions.length === 0" class="text-center py-5">
                                <i class="bi bi-emoji-frown display-4 text-muted mb-3 d-block"></i>
                                <h6 class="fw-bold text-muted">Nu există promoții active</h6>
                                <p class="small text-muted mb-0">Acest produs nu beneficiază de promoții speciale momentan.</p>
                             </div>

                             <div v-else class="d-flex flex-column gap-3">
                                <div v-for="promo in currentProductPromotions" :key="promo.id" class="card border-0 shadow-sm border-start border-4 border-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0 text-danger">{{ promo.name }}</h6>
                                            <span class="badge bg-danger">-{{ promo.calculated_discount_percent }}%</span>
                                        </div>
                                        <p class="text-muted small mb-3">{{ promo.description || 'Fără descriere' }}</p>
                                        
                                        <div class="d-flex align-items-end justify-content-between bg-light p-2 rounded">
                                            <div>
                                                <div class="small text-muted">Preț Promoțional:</div>
                                                <div class="fw-bold fs-5 text-dark">{{ formatPrice(promo.promo_price) }}</div>
                                                <div class="small text-decoration-line-through text-muted">{{ formatPrice(currentPromoBasePrice) }}</div>
                                            </div>
                                            <button class="btn btn-sm btn-danger px-3" @click="applyPromotion(promo)">
                                                <i class="bi bi-plus-lg me-1"></i> Aplică
                                            </button>
                                        </div>
                                        
                                        <div v-if="promo.min_qty > 1" class="mt-2 text-xs text-warning">
                                            <i class="bi bi-info-circle me-1"></i>Minim {{ promo.min_qty }} bucăți
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </div>
                        
                        <div class="p-3 border-top bg-white d-flex justify-content-end">
                            <button class="btn btn-secondary px-4" @click="closePromotionsModal">Închide</button>
                        </div>
                    </div>
                </div>

                <!-- Product List (Responsive Grid/Cards) -->
                <div class="product-list">
                    <!-- Header (Desktop Only) -->
                    <div class="d-none d-lg-block bg-light border-bottom">
                        <div class="row g-0 py-3">
                            <div class="col-4 px-4 text-muted small text-uppercase font-monospace">Produs</div>
                            <div class="col-8">
                                <div class="row g-3">
                                    <div class="col-3 text-center text-muted small text-uppercase font-monospace">Cantitate</div>
                                    <div class="col-3 text-end text-muted small text-uppercase font-monospace">Preț (RON)</div>
                                    <div class="col-3 text-center text-muted small text-uppercase font-monospace">Discount</div>
                                    <div class="col-3 d-flex justify-content-end align-items-center pe-4 text-muted small text-uppercase font-monospace">
                                        <span class="me-3">Total</span>
                                        <div style="width: 40px"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="form.items.length === 0" class="text-center py-5 border-bottom">
                        <div class="py-4 opacity-50">
                            <i class="bi bi-cart-x display-1 text-muted mb-3 d-block"></i>
                            <h6 class="text-muted fw-bold">Niciun produs adăugat</h6>
                            <p class="small text-muted mb-0">Folosește căutarea de mai sus pentru a adăuga produse în ofertă.</p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div v-for="(item, index) in form.items" :key="index" class="group-item-row border-bottom bg-white hover-bg-light transition-all">
                        <div class="row g-0 align-items-center p-3 p-lg-0 py-lg-3">
                            
                            <!-- Product Info -->
                            <div class="col-12 col-lg-4 px-lg-4 mb-3 mb-lg-0">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 flex-shrink-0">
                                            <div class="bg-light rounded border d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
                                            <i class="bi bi-box-seam fs-5"></i>
                                            </div>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate" :title="item.product_name">{{ item.product_name }}</div>
                                        <div class="small text-muted font-monospace"><i class="bi bi-upc-scan me-1"></i>{{ item.product_code }}</div>
                                    </div>
                                    <!-- Mobile Remove Button (Top Right) -->
                                    <button class="btn btn-link text-danger p-0 ms-auto d-lg-none" @click="removeItem(index)">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Controls Wrapper for Mobile -->
                            <div class="col-12 col-lg-8 mt-2 mt-lg-0">
                                <div class="row g-3 align-items-center">
                                    
                                    <!-- Quantity -->
                                    <div class="col-6 col-lg-3 text-center">
                                        <div class="d-block d-lg-none small text-muted mb-1 font-weight-bold">Cantitate</div>
                                        <div class="input-group input-group-sm shadow-sm mx-auto w-100">
                                            <button class="btn btn-light border px-2" type="button" @click="item.quantity > 1 ? (item.quantity-- && calculateLine(item)) : null">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" class="form-control text-center border-start-0 border-end-0 fw-bold px-1" v-model.number="item.quantity" min="1" @input="calculateLine(item)">
                                            <button class="btn btn-light border px-2" type="button" @click="item.quantity++ && calculateLine(item)">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-6 col-lg-3 text-end">
                                        <div class="d-block d-lg-none small text-muted mb-1 font-weight-bold">Preț Unitar</div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control text-end border-0 bg-light" v-model.number="item.unit_price" step="0.01" @input="calculateLine(item)">
                                        </div>
                                    </div>

                                    <!-- Discount -->
                                    <div class="col-6 col-lg-3 text-center position-relative mt-3 mt-lg-0">
                                        <div class="d-block d-lg-none small text-muted mb-1 font-weight-bold">Discount</div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control text-end" v-model.number="item.discount_percent" min="0" :max="config.maxDiscount" @input="calculateLine(item)" :class="{'text-danger fw-bold border-danger': item.discount_percent > config.approvalThreshold}">
                                            <span class="input-group-text px-2">%</span>
                                        </div>
                                        <div v-if="item.discount_percent > config.approvalThreshold" class="position-absolute w-100 text-center start-0" style="bottom: -20px; z-index: 5;">
                                                <span class="badge bg-danger text-white scale-up-center shadow-sm" style="font-size: 0.6rem;">Derogare</span>
                                        </div>
                                    </div>

                                    <!-- Total & Desktop Remove -->
                                    <div class="col-6 col-lg-3 d-flex flex-column flex-lg-row align-items-end align-items-lg-center justify-content-center justify-content-lg-end pe-lg-4 mt-3 mt-lg-0 border-top-0">
                                        <div class="d-lg-none small text-muted mb-1 font-weight-bold">Total</div>
                                        <div class="text-end">
                                            <div v-if="item.calculating" class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            <div v-else>
                                                <div class="fw-bold fs-6 text-dark">{{ formatPrice(item.final_total) }}</div>
                                                <div v-if="item.discount_percent > 0" class="text-decoration-line-through text-muted small" style="font-size: 0.75rem;">
                                                    {{ formatPrice(item.quantity * item.unit_price) }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Desktop Remove -->
                                        <div class="d-none d-lg-block ms-3" style="width: 40px; text-align: right;">
                                            <button class="btn btn-link text-danger p-0 opacity-50 hover-opacity-100" @click="removeItem(index)" title="Elimină">
                                                <i class="bi bi-trash-fill fs-5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer / Total -->
                    <div v-if="form.items.length > 0" class="bg-light p-4 border-top">
                         <div class="d-flex justify-content-between justify-content-lg-end align-items-center gap-3">
                            <span class="text-muted text-uppercase small fw-bold">Total Linie</span>
                            <span class="fw-bold fs-5 text-primary">{{ formatPrice(totals.net) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/store/auth';
import { useVisitStore } from '@/store/visit';
import { useTrackingStore } from '@/store/tracking';
import { useToast } from 'vue-toastification';
import api, { adminApi } from '@/services/http';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import ProductSelector from '@/components/admin/ProductSelector.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const visitStore = useVisitStore();
const trackingStore = useTrackingStore();
const toast = useToast();

const isEdit = computed(() => !!route.params.id);
const saving = ref(false);
const showProductModal = ref(false);
const customerSelectorRef = ref(null);
const currentStatus = ref('draft');
const messages = ref([]);
const newMessage = ref('');
const isInternalMessage = ref(false);
const messagesContainer = ref(null);
const calculating = ref(false);
const isCustomerLocked = ref(false);

// Promotions Modal State
const showPromotionsModal = ref(false);
const loadingPromotions = ref(false);
const currentProductPromotions = ref([]);
const currentPromoProduct = ref(null);
const currentPromoBasePrice = ref(0);

// Settings
const config = reactive({
    approvalThreshold: 15,
    maxDiscount: 30
});

const loadConfig = async () => {
    try {
        const { data } = await api.get('/config');
        if (data.offer_discount_threshold_approval) {
            config.approvalThreshold = parseInt(data.offer_discount_threshold_approval);
        }
        if (data.offer_discount_max) {
            config.maxDiscount = parseInt(data.offer_discount_max);
        }
    } catch (e) {
        console.warn('Could not load public config', e);
    }
};

const canApprove = computed(() => ['admin', 'sales_director'].includes(authStore.role));
const isMe = (userId) => authStore.user?.id === userId;



const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
});

const form = reactive({
    customer: null,
    customer_id: null,
    quote_request_id: null,
    valid_until: null,
    notes: '',
    items: []
});

const loadRequestData = async (requestId) => {
    try {
        const { data } = await adminApi.get(`/quotes/${requestId}`);
        if (data) {
            // Set customer
            if (data.customer) {
                selectCustomer(data.customer);
                if (customerSelectorRef.value) {
                    customerSelectorRef.value.searchQuery = data.customer.name;
                }
            }
            
            // Set items
            if (data.items && data.items.length > 0) {
                form.items = data.items.map(item => {
                    const product = item.product || {};
                    const unitPrice = parseFloat(item.list_price || product.list_price || product.price || 0);
                    const qty = parseFloat(item.quantity || 1);
                    
                    return {
                        product_id: item.product_id,
                        product_name: product.name || 'Produs necunoscut',
                        product_code: product.internal_code || '',
                        quantity: qty,
                        unit_price: unitPrice,
                        discount_percent: 0,
                        final_total: qty * unitPrice
                    };
                });
            }
            
            // Set quote request ID
            form.quote_request_id = parseInt(requestId);
            
            // Set notes if available
            if (data.internal_notes) {
                form.notes = `Referință Cerere Ofertă #${requestId}\n\n${data.internal_notes}`;
            } else {
                form.notes = `Referință Cerere Ofertă #${requestId}`;
            }
            
            toast.success('Datele din cererea de ofertă au fost încărcate.');
        }
    } catch (e) {
        console.error('Error loading request data:', e);
        toast.error('Nu am putut încărca datele din cererea de ofertă.');
    }
};

const totals = computed(() => {
    let gross = 0;
    let net = 0;
    
    form.items.forEach(item => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        const discount = parseFloat(item.discount_percent) || 0;
        
        gross += qty * price;
        net += qty * price * (1 - discount / 100);
    });
    
    return {
        gross,
        net,
        discount: gross - net
    };
});

const requiresDerogation = computed(() => {
    return form.items.some(item => item.discount_percent > config.approvalThreshold);
});

const hasErrors = computed(() => {
    return form.items.some(item => item.discount_percent > config.maxDiscount);
});

const isValid = computed(() => {
    return form.customer_id && form.items.length > 0 && !hasErrors.value;
});

const selectCustomer = (customer) => {
    form.customer = customer;
    form.customer_id = customer ? customer.id : null;
};

const onProductSelect = (product) => {
    addProduct(product);
    // Optional: Visual feedback or keep modal open (default behavior)
};

const addProduct = async (product) => {
    // Check if already exists
    const existing = form.items.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
        calculateLine(existing);
        toast.info('Cantitatea produsului a fost actualizată.');
        return;
    }

    const newItem = reactive({
        product_id: product.id,
        product_name: product.name,
        product_code: product.internal_code || product.sku,
        quantity: 1,
        unit_price: parseFloat(product.list_price || product.price || 0), // Base price
        discount_percent: 0,
        system_discount_percent: 0,
        final_total: parseFloat(product.list_price || product.price || 0),
        calculating: false
    });
    
    form.items.push(newItem);
    
    // Calculate system price immediately
    await calculateItemSystemPrice(newItem);
};

const clampItemDiscount = (item) => {
    let discount = parseFloat(item.discount_percent);
    if (!Number.isFinite(discount)) discount = 0;
    if (discount < 0) discount = 0;

    const capped = discount > config.maxDiscount;
    if (capped) {
        discount = config.maxDiscount;
    }

    item.discount_percent = discount;
    return capped;
};

const recalculateItemTotal = (item) => {
    const price = parseFloat(item.unit_price) || 0;
    const qty = parseInt(item.quantity) || 1;
    const discount = parseFloat(item.discount_percent) || 0;
    item.final_total = price * qty * (1 - discount / 100);
};

const calculateItemSystemPrice = async (item) => {
    if (!form.customer_id) return;
    
    try {
        item.calculating = true;
        const payload = {
            customer_id: form.customer_id,
            items: [{
                product_id: item.product_id,
                quantity: item.quantity,
                price_override: null, 
                discount_override: null
            }]
        };
        
        // Use the account route which maps to Admin\QuickOrderController@calculate
        const { data } = await api.post('/account/quick-order/calculate', payload);
        if (data && Array.isArray(data.items)) {
            const result = data.items.find(r => r.product_id === item.product_id);
            if (!result) return;

            item.unit_price = parseFloat(result.unit_price ?? result.unit_base_price ?? item.unit_price) || 0;
            item.system_discount_percent = parseFloat(result.discount_percent ?? 0) || 0;

            // Auto-apply system discount if manual is 0
            if ((parseFloat(item.discount_percent) || 0) === 0 && item.system_discount_percent > 0) {
                item.discount_percent = item.system_discount_percent;
            }

            const capped = clampItemDiscount(item);
            if (capped) {
                toast.warning(`Discount-ul maxim permis este de ${config.maxDiscount}%`);
            }
            recalculateItemTotal(item);
        }
    } catch (e) {
        console.error("Pricing error", e);
    } finally {
        item.calculating = false;
    }
};

const recalculateAllPrices = async () => {
    if (!form.customer_id || form.items.length === 0) return;
    
    if (!confirm('Această acțiune va recalcula toate prețurile conform sistemului și va suprascrie modificările manuale. Continui?')) return;
    
    calculating.value = true;
    try {
        const payload = {
            customer_id: form.customer_id,
            items: form.items.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                price_override: null,
                discount_override: null
            }))
        };
        
        const { data } = await api.post('/account/quick-order/calculate', payload);

        let cappedCount = 0;
        data.items.forEach(resItem => {
            const item = form.items.find(i => i.product_id === resItem.product_id);
            if (!item) return;

            item.unit_price = parseFloat(resItem.unit_price ?? resItem.unit_base_price ?? item.unit_price) || 0;
            item.system_discount_percent = parseFloat(resItem.discount_percent ?? 0) || 0;
            if ((parseFloat(item.discount_percent) || 0) === 0 && item.system_discount_percent > 0) {
                item.discount_percent = item.system_discount_percent;
            }

            const capped = clampItemDiscount(item);
            if (capped) cappedCount++;

            recalculateItemTotal(item);
        });

        if (cappedCount > 0) {
            toast.warning(`Au fost limitate ${cappedCount} discount-uri la ${config.maxDiscount}%.`);
        }
        
        toast.success('Prețurile au fost actualizate conform promoțiilor active.');
    } catch (e) {
        console.error("Bulk pricing error", e);
        toast.error('Eroare la recalcularea prețurilor.');
    } finally {
        calculating.value = false;
    }
};

const calculateLine = (item) => {
    const capped = clampItemDiscount(item);
    if (capped) {
        toast.warning(`Discount-ul maxim permis este de ${config.maxDiscount}%`);
    }
    recalculateItemTotal(item);
};

const applyPromo = (item) => {
    if (item.promo_info) {
        item.discount_percent = item.promo_info.discount;
        calculateLine(item);
        toast.success('Promoție aplicată!');
    }
};

const onCheckPromotions = async (product) => {
    if (!form.customer_id) {
        toast.warning('Selectează mai întâi un client.');
        return;
    }

    currentPromoProduct.value = product;
    showPromotionsModal.value = true;
    loadingPromotions.value = true;
    currentProductPromotions.value = [];
    currentPromoBasePrice.value = 0;

    try {
        const { data } = await api.post('/account/quick-order/available-promotions', {
            customer_id: form.customer_id,
            product_id: product.id
        });

        currentProductPromotions.value = data.promotions || [];
        currentPromoBasePrice.value = parseFloat(data.base_price) || 0;
    } catch (e) {
        console.error('Error fetching promotions:', e);
        toast.error('Nu s-au putut încărca promoțiile.');
    } finally {
        loadingPromotions.value = false;
    }
};

const closePromotionsModal = () => {
    showPromotionsModal.value = false;
    currentPromoProduct.value = null;
    currentProductPromotions.value = [];
};

const applyPromotion = (promo) => {
    if (!currentPromoProduct.value) return;

    // Logic to apply promotion
    // Basically add product with this discount
    const product = currentPromoProduct.value;
    
    // Check if item exists
    const existing = form.items.find(i => i.product_id === product.id);
    
    if (existing) {
        // Update existing item
        existing.discount_percent = parseFloat(promo.calculated_discount_percent) || 0;
        
        // Also ensure quantity meets min_qty if set
        if (promo.min_qty && existing.quantity < promo.min_qty) {
            existing.quantity = promo.min_qty;
            toast.info(`Cantitatea a fost actualizată la minimul de ${promo.min_qty} buc.`);
        }
        
        calculateLine(existing);
        toast.success(`Promoția "${promo.name}" a fost aplicată!`);
    } else {
        // Add new item with promo
        const newItem = reactive({
            product_id: product.id,
            product_name: product.name,
            product_code: product.internal_code || product.sku,
            quantity: promo.min_qty || 1,
            unit_price: parseFloat(currentPromoBasePrice.value || product.list_price || product.price || 0), 
            discount_percent: parseFloat(promo.calculated_discount_percent) || 0,
            system_discount_percent: parseFloat(promo.calculated_discount_percent) || 0,
            final_total: 0,
            calculating: false
        });
        
        recalculateItemTotal(newItem);
        form.items.push(newItem);
        toast.success(`Produsul a fost adăugat cu promoția "${promo.name}"!`);
    }
    
    closePromotionsModal();
    // Also close product modal if open
    // showProductModal.value = false; // Maybe keep it open to add more?
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const formatPrice = (val) => new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(val || 0);
const formatDate = (val, time = false) => {
    if (!val) return '';
    const d = new Date(val);
    return time ? d.toLocaleString('ro-RO') : d.toLocaleDateString('ro-RO');
};

const statusLabel = (s) => {
    const map = {
        'draft': 'Draft',
        'sent': 'Trimisă',
        'pending_approval': 'Așteptare Aprobare',
        'approved': 'Aprobată',
        'negotiation': 'Negociere',
        'rejected': 'Respinsă',
        'accepted': 'Acceptată',
        'completed': 'Finalizată'
    };
    return map[s] || s;
};

const statusBadge = (s) => {
    const map = {
        'draft': 'bg-secondary',
        'sent': 'bg-primary',
        'pending_approval': 'bg-warning text-dark',
        'approved': 'bg-success',
        'negotiation': 'bg-info text-dark',
        'rejected': 'bg-danger',
        'accepted': 'bg-success',
        'completed': 'bg-success'
    };
    return map[s] || 'bg-secondary';
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    try {
        const { data } = await adminApi.post(`/offers/${route.params.id}/messages`, {
            message: newMessage.value,
            is_internal: isInternalMessage.value
        });
        messages.value.push(data);
        newMessage.value = '';
        scrollToBottom();
    } catch (e) {
        toast.error('Eroare la trimiterea mesajului.');
    }
};

const changeStatus = async (status) => {
    if (!confirm('Ești sigur că vrei să schimbi statusul?')) return;
    try {
        await adminApi.post(`/offers/${route.params.id}/status`, { status });
        toast.success('Status actualizat!');
        currentStatus.value = status;
        // Reload to get latest state if needed
    } catch (e) {
        toast.error('Eroare la actualizarea statusului.');
    }
};

const convertToOrder = async () => {
    if (!confirm('Ești sigur că vrei să transformi această ofertă în comandă?')) return;
    try {
        const { data } = await adminApi.post(`/offers/${route.params.id}/convert-to-order`);
        toast.success('Oferta a fost transformată în comandă cu succes!');
        
        if (data.order_id) {
            const routeName = authStore.role === 'admin' ? 'admin-order-details' : 'account-order-details';
            router.push({ name: routeName, params: { id: data.order_id } });
        } else {
            router.push({ name: getRouteName() }); // Back to list
        }
    } catch (e) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Eroare la transformarea în comandă');
    }
};

const deleteOffer = async () => {
    if (!confirm('Ești sigur că vrei să ștergi această ofertă definitv?')) return;
    try {
        await adminApi.delete(`/offers/${route.params.id}`);
        toast.success('Oferta a fost ștearsă.');
        
        // Go back to list
        const routeName = authStore.role === 'admin' ? 'admin-offers' : 'account-offers-list';
        router.push({ name: routeName });
    } catch (e) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Eroare la ștergerea ofertei');
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const getRouteName = (base) => {
    if (authStore.role === 'admin') return `admin-offers${base ? '-' + base : ''}`;
    return `account-offers${base ? '-' + base : '-list'}`;
};

const saveOffer = async () => {
    if (!isValid.value) return;
    
    saving.value = true;
    try {
        const payload = {
            customer_id: form.customer_id,
            customer_visit_id: visitStore.activeVisit?.id,
            quote_request_id: form.quote_request_id,
            valid_until: form.valid_until,
            notes: form.notes,
            items: form.items.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount_percent: i.discount_percent
            }))
        };

        if (isEdit.value) {
            await adminApi.put(`/offers/${route.params.id}`, payload);
            toast.success('Ofertă actualizată cu succes!');
        } else {
            await adminApi.post('/offers', payload);
            toast.success('Ofertă creată cu succes!');
        }
        
        router.push({ name: getRouteName() });
    } catch (e) {
        console.error(e);
        toast.error('Eroare la salvarea ofertei.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadConfig();

    // Check visit restriction for Agents/Directors creating NEW offers
    if (!isEdit.value && ['sales_agent', 'sales_director'].includes(authStore.role)) {
         // Check Shift Status first
         if (!trackingStore.isShiftActive) {
            await trackingStore.checkStatus();
            if (!trackingStore.isShiftActive) {
                 toast.error('Trebuie să începeți programul de lucru pentru a crea oferte!');
                 router.push({ name: 'agent-dashboard' });
                 return;
            }
         }

         if (!visitStore.activeVisit) {
             toast.error('Trebuie să aveți o vizită activă pentru a crea o ofertă!');
             router.push({ name: 'agent-dashboard' });
             return;
         } else {
             // Lock to visited customer
             selectCustomer(visitStore.activeVisit.customer);
             isCustomerLocked.value = true;
             
             nextTick(() => {
                 if (customerSelectorRef.value) {
                     customerSelectorRef.value.searchQuery = visitStore.activeVisit.customer.name;
                 }
             });
         }
    }

    if (isEdit.value) {
        // Load offer data
        try {
            const { data } = await adminApi.get(`/offers/${route.params.id}`);
            form.customer_id = data.customer_id;
            form.customer = data.customer;
            form.valid_until = data.valid_until;
            form.notes = data.notes;
            currentStatus.value = data.status;
            messages.value = data.messages || [];
            scrollToBottom();
            
            // Map items
            form.items = data.items.map(i => ({
                product_id: i.product_id,
                product_name: i.product.name,
                product_code: i.product.internal_code,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount_percent: i.discount_percent,
                final_total: i.final_price * i.quantity
            }));
            
            // Pre-fill customer selector search if needed (optional)
             if (customerSelectorRef.value) {
                 customerSelectorRef.value.searchQuery = data.customer.name;
             }
        } catch (e) {
            toast.error('Nu am putut încărca oferta.');
            router.push({ name: getRouteName() });
        }
    } else {
        if (route.query.customer_id) {
            try {
                const { data } = await adminApi.get(`/customers/${route.query.customer_id}`);
                selectCustomer(data);
            } catch (e) {
                console.error('Error fetching customer:', e);
            }
        }
        
        if (route.query.request_id) {
            await loadRequestData(route.query.request_id);
        }
    }
});
</script>

<style scoped>
.scale-up-center {
    animation: scale-up-center 0.4s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
}

@keyframes scale-up-center {
    0% { transform: scale(0.5) translateY(2px); }
    100% { transform: scale(1) translateY(2px); }
}

.hover-bg-gray:hover {
    background-color: #f8f9fa;
}

.hover-opacity-100:hover {
    opacity: 1 !important;
}

.group-item-row {
    transition: background-color 0.2s;
}

.group-item-row:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.02);
}
</style>
