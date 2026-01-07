<template>
  <div class="flex flex-col h-screen bg-gray-100 font-sans overflow-hidden text-sm md:text-base relative">
    
    <!-- HEADER (Material Design Blue) -->
    <header class="bg-blue-600 text-white shadow-md z-30 flex-shrink-0 flex flex-col">
        <!-- Top Bar -->
        <div class="flex items-center justify-between px-4 h-14">
            <div class="flex items-center gap-3 overflow-hidden">
                <button @click="router.back()" class="p-1 -ml-2 rounded-full hover:bg-blue-700 transition-colors">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="min-w-0 flex flex-col">
                    <h1 class="text-lg font-bold leading-none truncate">Comandă nouă</h1>
                    <p class="text-xs text-blue-100 truncate font-medium mt-0.5">{{ selectedCustomer ? selectedCustomer.name : 'Selectează Client' }}</p>
                </div>
            </div>
            
            <!-- Header Actions -->
            <div class="flex items-center gap-1">
                 <button v-if="activeTab === 'products'" @click="showFilters = true" class="p-2 rounded-full hover:bg-blue-700 transition-colors relative">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span v-if="activeFiltersCount > 0" class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border border-blue-600"></span>
                 </button>
                 
                 <button @click="submitOrder" :disabled="items.length === 0 || loadingSubmit" class="p-2 rounded-full hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                 </button>
                 
                 <button @click="showPreview = true" class="p-2 rounded-full hover:bg-blue-700 transition-colors" title="Previzualizare comandă">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                 </button>
            </div>
        </div>

        <!-- Material Tabs -->
        <div class="flex text-sm font-bold uppercase tracking-wide overflow-x-auto scrollbar-hide">
            <button @click="activeTab = 'antet'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'antet' ? 'border-white text-white' : 'border-transparent text-blue-200 hover:text-white'">
                ANTET
            </button>
            <button @click="activeTab = 'products'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'products' ? 'border-white text-white' : 'border-transparent text-blue-200 hover:text-white'">
                PRODUSE
            </button>
            <button @click="activeTab = 'promotions'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'promotions' ? 'border-white text-white' : 'border-transparent text-blue-200 hover:text-white'">
                PROMOȚII
            </button>
            <button @click="activeTab = 'cart'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'cart' ? 'border-white text-white' : 'border-transparent text-blue-200 hover:text-white'">
                COȘ <span v-if="items.length > 0">({{ items.length }})</span>
            </button>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto relative bg-gray-100" ref="mainScroll">
        
        <!-- TAB 1: ANTET (Header Info) -->
        <div v-show="activeTab === 'antet'" class="pb-20">
            <div v-if="!selectedCustomer" class="p-4 bg-white m-4 rounded-lg shadow-sm space-y-3">
                <div class="p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
                    <div class="font-bold text-sm mb-1">Nu există vizită activă</div>
                    <div class="text-xs mb-2">Dacă ai o vizită programată, începe vizita pentru a prelua automat clientul.</div>
                    <button @click="goToPlanning" class="text-xs bg-yellow-100 px-2 py-1 rounded border border-yellow-300 hover:bg-yellow-200 font-medium">
                        Mergi la Planificare
                    </button>
                </div>
                <CustomerSelector @select="selectCustomer" />
            </div>
            
            <div v-else class="space-y-0.5 animate-fade-in">
                <!-- Client Details Card -->
                <div class="bg-white p-4 mb-4 shadow-sm border-l-4 border-blue-600">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ selectedCustomer.name }}</h3>
                            <p class="text-sm text-gray-500 font-mono">{{ selectedCustomer.fiscal_code || 'CUI: -' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ selectedCustomer.reg_com || '-' }}</p>
                        </div>
                        <div class="text-right">
                             <span class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded font-bold mb-1">
                                {{ selectedCustomer.currency || 'RON' }}
                             </span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between text-xs">
                        <div>
                            <span class="text-gray-400 block">Agent Asignat</span>
                            <span class="font-bold text-gray-700">{{ selectedCustomer.agent_name || 'Nespecificat' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-gray-400 block">Discount</span>
                            <span class="font-bold text-green-600">{{ selectedCustomer.discount_percent || 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Order Details List -->
                 <!-- Order Details List -->
                 <div class="bg-white p-4 flex items-center gap-4 border-b border-gray-100">
                     <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                         <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                     </div>
                     <div class="flex-1">
                         <label class="text-xs text-gray-500 block mb-1">Dată livrare</label>
                         <input type="date" v-model="orderDetails.deliveryDate" class="w-full font-medium text-gray-900 border-b border-gray-200 focus:border-blue-500 outline-none pb-1 bg-transparent">
                     </div>
                 </div>

                 <div class="bg-white p-4 flex items-center gap-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                         <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 block mb-1">Tip plată</label>
                        <select v-model="orderDetails.paymentMethod" class="w-full font-medium text-gray-900 border-b border-gray-200 focus:border-blue-500 outline-none pb-1 bg-transparent">
                            <option value="CHS">CHS - Numerar</option>
                            <option value="BO">BO - Bilet la Ordin</option>
                            <option value="CEC">CEC</option>
                            <option value="OP">OP - Ordin de Plată</option>
                        </select>
                    </div>
                 </div>

                 <div v-if="orderDetails.paymentMethod !== 'CHS'" class="bg-white p-4 flex items-center gap-4 border-b border-gray-100 animate-fade-in">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                         <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 block mb-1">Document plată (Nr./Serie)</label>
                        <input type="text" v-model="orderDetails.paymentDocument" placeholder="ex: BO-12345" class="w-full font-medium text-gray-900 border-b border-gray-200 focus:border-blue-500 outline-none pb-1 bg-transparent">
                    </div>
                 </div>

                 <!-- Global Discount Input (Only if allowed) -->
                 <div v-if="selectedCustomer && (selectedCustomer.allow_global_discount || canOverride)" class="bg-white p-4 flex items-center gap-4 border-b border-gray-100 animate-fade-in">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                         <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 011 12V7a4 4 0 014-4z" /></svg>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 block mb-1">Discount Financiar (Global) %</label>
                        <input type="number" v-model.number="orderDetails.globalDiscount" min="0" :max="discountRules.max_discount" class="w-full font-medium text-gray-900 border-b border-gray-200 focus:border-blue-500 outline-none pb-1 bg-transparent">
                        <div v-if="orderDetails.globalDiscount > discountRules.approval_threshold" class="text-xs text-amber-600 font-bold mt-1">
                            ⚠️ Necesită aprobare
                        </div>
                    </div>
                 </div>

                 <div class="bg-white p-4 flex items-center justify-between">
                     <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600">
                             <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Depozit *</p>
                            <p class="font-medium text-gray-900">Metalrom</p>
                        </div>
                     </div>
                     <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                 </div>

                 <div class="bg-white p-4 flex items-center justify-between">
                     <div class="flex items-center gap-4 w-full">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                             <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 block mb-1">Comentariu</label>
                            <input type="text" v-model="orderDetails.internalNote" placeholder="Adaugă comentariu..." class="w-full text-sm outline-none bg-transparent placeholder-gray-400 text-gray-900 border-b border-gray-200 pb-1">
                        </div>
                     </div>
                 </div>

                 <!-- Client Financial Info Summary -->
                 <div class="mt-4 p-4 bg-blue-50 border-t border-b border-blue-100">
                     <h3 class="text-xs font-bold text-blue-800 uppercase mb-3">Informații Financiare</h3>
                     <div class="grid grid-cols-2 gap-4">
                         <div>
                             <p class="text-[10px] text-blue-500 uppercase">Sold Curent</p>
                             <p class="font-mono font-bold text-gray-900">{{ formatPrice(selectedCustomer.balance) }}</p>
                         </div>
                         <div>
                             <p class="text-[10px] text-blue-500 uppercase">Limită Credit</p>
                             <p class="font-mono font-bold text-gray-900">{{ formatPrice(selectedCustomer.credit_limit || 0) }}</p>
                         </div>
                     </div>
                 </div>
                 
                 <div class="p-4">
                    <button @click="selectCustomer(null)" class="w-full py-3 text-red-600 text-sm font-bold bg-white border border-red-200 rounded-lg shadow-sm">
                        Schimbă Client
                    </button>
                 </div>
             </div>
        </div>

        <!-- TAB 2: PRODUCTS (Main) -->
        <div v-show="activeTab === 'products'" class="flex flex-col h-full bg-white">
            <!-- Search Bar -->
            <div class="sticky top-0 bg-white z-20 px-3 py-2 border-b border-gray-100">
                <div class="relative">
                    <input 
                        v-model="searchQuery"
                        @input="onSearchInput"
                        type="text" 
                        placeholder="Caută produse..." 
                        class="w-full pl-9 pr-4 py-2 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    >
                    <svg class="absolute left-2.5 top-2.5 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <!-- Product List -->
            <div class="flex-1 overflow-y-auto">
                 <div v-if="loadingProducts && products.length === 0" class="flex justify-center py-20">
                     <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                 </div>

                 <div v-else-if="products.length === 0" class="text-center py-20 text-gray-400">
                     <p>Nu am găsit produse.</p>
                     <button @click="resetFilters" class="mt-4 text-blue-600 font-bold">Resetează Filtre</button>
                 </div>

                 <div v-else class="divide-y divide-gray-100">
                     <div v-for="product in products" :key="product.id" class="p-3 bg-white flex items-center justify-between gap-3 hover:bg-gray-50">
                         <!-- Product Info -->
                         <div class="flex-1 min-w-0">
                             <div class="font-bold text-gray-900 text-sm mb-1 leading-tight line-clamp-2">
                                {{ product.name }}
                             </div>
                             
                             <div class="flex items-center gap-3 text-xs">
                                 <span class="font-bold text-gray-900">{{ formatPrice(product.list_price || product.price) }}</span>
                                 <span :class="product.stock_qty > 0 ? 'text-green-600' : 'text-red-500'">
                                     Stoc: {{ product.stock_qty > 0 ? `${product.stock_qty}/${product.stock_qty}` : '0/0' }}
                                 </span>
                             </div>
                         </div>

                         <!-- Quantity Input (Right Side) -->
                         <div class="flex-shrink-0 flex items-center bg-gray-100 rounded-lg border border-gray-200 shadow-sm">
                             <button @click="updateProductQuantity(product, -1)" class="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-200 rounded-l-lg font-bold text-lg transition-colors">
                                −
                             </button>
                             <div class="relative flex flex-col items-center justify-center w-14 bg-white h-10 border-x border-gray-200">
                                 <input 
                                    type="number" 
                                    :value="getCartQuantity(product.id)" 
                                    @focus="$event.target.select()"
                                    @change="(e) => setCartQuantity(product, parseInt(e.target.value) || 0)"
                                    class="w-full text-center font-bold text-gray-900 outline-none text-base p-0 border-none focus:ring-0 h-5"
                                    min="0"
                                 >
                                <button @click="toggleItemUnit(product)" class="text-[10px] font-bold text-blue-600 uppercase hover:text-blue-800 leading-none">
                                    {{ getItemUnit(product) }}
                                </button>
                             </div>
                             <button @click="updateProductQuantity(product, 1)" class="w-10 h-10 flex items-center justify-center text-blue-600 hover:bg-blue-50 rounded-r-lg font-bold text-lg transition-colors">
                                +
                             </button>
                         </div>
                         
                         <button @click="openProductDetails(product)" 
                                class="ml-2 w-10 h-10 flex flex-col items-center justify-center rounded-lg transition-colors border"
                                :class="getItemDiscountPercent(product) > 0 ? 'bg-orange-50 border-orange-200 text-orange-600' : 'bg-white border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-300'"
                                title="Editează detalii și discount"
                        >
                            <span v-if="getItemDiscountPercent(product) > 0" class="text-xs font-bold leading-none">-{{ getItemDiscountPercent(product) }}%</span>
                            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                     </div>
                     
                     <!-- Load More -->
                     <div v-if="hasMorePages" class="p-4">
                         <button @click="loadMore" :disabled="loadingMore" class="w-full py-2 bg-gray-50 text-gray-600 text-sm font-bold rounded border border-gray-200">
                             {{ loadingMore ? '...' : 'Mai multe' }}
                         </button>
                     </div>
                 </div>
            </div>
            
            <!-- Summary Footer (Collapsible) -->
            <div class="bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-20">
                <div @click="showSummary = !showSummary" class="p-3 flex justify-between items-center bg-gray-50 cursor-pointer border-b border-gray-100">
                    <span class="text-sm text-gray-500 font-medium">{{ totalProducts }} produse</span>
                    <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="showSummary ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>
                
                <div v-if="showSummary" class="p-4 bg-white text-sm space-y-2 animate-slide-up">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Valoare inițială</span>
                        <span class="font-mono">{{ formatPrice(totals.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
            <span class="text-gray-500">Total Reduceri</span>
            <span class="font-mono text-green-600">-{{ formatPrice(cartDiscount) }}</span>
          </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Valoare TVA</span>
                        <span class="font-mono">{{ formatPrice(totals.vat) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-100 font-bold text-base">
                        <span>Valoare totală</span>
                        <span class="text-blue-600">{{ formatPrice(totals.total) }}</span>
                    </div>
                    
                    <div class="mt-4 pt-2 border-t border-gray-100 grid grid-cols-2 gap-4 text-xs">
                         <div>
                             <p class="text-gray-400">Credit inițial:</p>
                             <p class="font-bold">{{ selectedCustomer ? formatPrice(selectedCustomer.credit_limit) : '-' }}</p>
                         </div>
                         <div class="text-right">
                             <p class="text-gray-400">Credit curent:</p>
                             <p class="font-bold">{{ selectedCustomer ? formatPrice(selectedCustomer.balance) : '-' }}</p>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: PROMOTIONS -->
        <div v-show="activeTab === 'promotions'" class="p-4 space-y-3">
             <div v-if="availablePromotions.length > 0" class="space-y-3">
                 <div v-for="promo in availablePromotions" :key="promo.id" class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
                     <div class="flex justify-between items-start">
                         <h3 class="font-bold text-gray-900">{{ promo.name }}</h3>
                         <span v-if="promo.discount_percent" class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded">-{{ promo.discount_percent }}%</span>
                         <span v-else-if="promo.discount_value" class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded">-{{ formatPrice(promo.discount_value) }}</span>
                     </div>
                     <p class="text-sm text-gray-500 mt-1">{{ promo.description }}</p>
                     <button class="mt-3 text-blue-600 text-sm font-bold" @click="applyPromoToCart(promo)">Aplică Promoția</button>
                </div>
            </div>
            <div v-else class="text-center py-10 text-gray-400">
                Nu sunt promoții active.
            </div>
       </div>

       <!-- TAB 4: CART (Details) -->
        <div v-show="activeTab === 'cart'" class="p-4 space-y-3 pb-24">
             <div v-if="items.length === 0" class="text-center py-10 text-gray-400">
                 Coșul este gol.
             </div>
             <div v-else v-for="(item, index) in items" :key="index" class="bg-white p-3 rounded-lg shadow-sm">
            <div class="flex justify-between items-center">
              <div>
                <h4 class="font-bold text-sm line-clamp-1">{{ item.product_name }}</h4>
                <p class="text-xs text-gray-500">{{ item.sku }}</p>
                <p class="text-sm font-bold text-blue-600">{{ formatPrice(effectiveLineTotal(item)) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="updateQuantity(index, -1)" class="w-8 h-8 bg-gray-100 rounded font-bold text-gray-600">-</button>
                <span class="font-bold w-6 text-center">{{ item.quantity }}</span>
                <button @click="updateQuantity(index, 1)" class="w-8 h-8 bg-blue-100 rounded font-bold text-blue-600">+</button>
              </div>
            </div>
            <!-- Promotions Display -->
             <div v-if="item.applied_promotions && item.applied_promotions.length" class="mt-2 space-y-1">
               <div v-for="promo in item.applied_promotions" :key="promo.id" class="text-xs text-green-600 flex items-center gap-1">
                 <i class="bi bi-tag-fill"></i>
                 <span>{{ promo.name }} <span v-if="promo.discount_amount">(-{{ formatPrice(promo.discount_amount) }})</span></span>
               </div>
             </div>
          </div>
        </div>
        
    </main>

    <!-- FILTER DRAWER -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showFilters" class="fixed inset-0 z-50 flex justify-end" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showFilters = false"></div>
            <div class="relative w-full max-w-xs bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300 animate-slide-in-right">
                <!-- Header Blue -->
                <div class="p-4 bg-blue-600 text-white flex justify-between items-center">
                    <h3 class="font-bold text-lg">Filtrare produse</h3>
                    <button @click="showFilters = false"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-6 bg-gray-50">
                    <!-- Filters Content (Same as before but styled cleaner) -->
                    <div>
                        <h4 class="text-blue-600 font-bold uppercase text-xs mb-3">Sortare</h4>
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                             <label v-for="option in sortOptions" :key="option.value" class="flex items-center justify-between p-3 border-b border-gray-100 last:border-0 cursor-pointer">
                                 <span class="text-sm font-medium text-gray-700">{{ option.label }}</span>
                                 <input type="radio" :value="option.value" v-model="selectedSort" class="text-blue-600 focus:ring-blue-500">
                             </label>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-blue-600 font-bold uppercase text-xs mb-3">Categorii</h4>
                         <div class="bg-white rounded-lg border border-gray-200 overflow-hidden max-h-80 overflow-y-auto">
                             <label class="flex items-center justify-between p-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50">
                                 <span class="text-sm font-medium">Toate</span>
                                 <input type="radio" :value="null" v-model="selectedCategory" @change="onCategoryChange" class="text-blue-600">
                             </label>
                             
                             <template v-for="cat in rootCategories" :key="cat.id">
                                 <label class="flex items-center justify-between p-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50" :class="selectedCategory?.id === cat.id ? 'bg-blue-50' : ''">
                                     <span class="text-sm font-medium">{{ cat.name }}</span>
                                     <input type="radio" :value="cat" v-model="selectedCategory" @change="onCategoryChange" class="text-blue-600">
                                 </label>
                                 
                                 <!-- Nested Subcategories -->
                                 <div v-if="selectedCategory?.id === cat.id" class="bg-gray-50 border-b border-gray-100 pl-8 transition-all duration-200">
                                     <label class="flex items-center justify-between p-2 border-b border-gray-100 last:border-0 cursor-pointer hover:bg-gray-100">
                                         <span class="text-xs font-bold text-gray-500 uppercase">Toate din {{ cat.name }}</span>
                                         <input type="radio" :value="null" v-model="selectedSubcategory" class="text-blue-600">
                                     </label>
                                     <label v-for="sub in getSubcategories(cat.id)" :key="sub.id" class="flex items-center justify-between p-2 border-b border-gray-100 last:border-0 cursor-pointer hover:bg-gray-100">
                                         <span class="text-sm text-gray-700">{{ sub.name }}</span>
                                         <input type="radio" :value="sub" v-model="selectedSubcategory" class="text-blue-600">
                                     </label>
                                 </div>
                             </template>
                         </div>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-gray-200">
                    <button @click="resetFilters" class="w-full py-3 bg-blue-600 text-white font-bold rounded shadow-lg uppercase tracking-wide">Resetează</button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- PREVIEW MODAL -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showPreview" class="fixed inset-0 bg-black/40 z-40 flex items-end md:items-center md:justify-center">
        <div class="bg-white w-full md:w-[700px] rounded-t-2xl md:rounded-2xl shadow-xl">
          <div class="p-4 border-b flex justify-between items-center">
            <div class="font-bold">Previzualizare comandă</div>
            <button @click="showPreview = false" class="text-gray-500 hover:text-gray-700">✕</button>
          </div>
          <div class="p-4 space-y-3 text-sm max-h-[70vh] overflow-y-auto">
            <div v-if="items.length === 0" class="text-center text-gray-400 py-10">Coșul este gol.</div>
            <div v-else>
              <div v-for="(item, idx) in items" :key="idx" class="flex justify-between items-start py-2 border-b">
                <div class="min-w-0">
                  <div class="font-bold line-clamp-1">{{ item.product_name }}</div>
                  <div class="text-xs text-gray-500">Cod: {{ item.sku }} • UM: {{ item.unit || 'buc' }}</div>
                  <div v-if="item.discount_percent > 0" class="text-xs text-green-700 mt-1">Discount: -{{ item.discount_percent }}%</div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-gray-500">Cant: {{ item.quantity }}</div>
                  <div class="font-bold">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                </div>
              </div>
              <div class="pt-3">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-mono">{{ formatPrice(totals.subtotal) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">TVA</span><span class="font-mono">{{ formatPrice(totals.vat) }}</span></div>
                <div class="flex justify-between font-bold text-base border-t pt-2"><span>Total</span><span class="text-blue-600">{{ formatPrice(totals.total) }}</span></div>
              </div>
            </div>
          </div>
          <div class="p-4 border-t flex justify-end gap-2">
            <button @click="showPreview = false" class="px-3 py-2 rounded border">Închide</button>
            <button @click="submitOrder" :disabled="items.length === 0 || loadingSubmit" class="px-4 py-2 rounded bg-blue-600 text-white disabled:opacity-50">Plasează</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- PRODUCT DETAILS SHEET -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="productSheet.show" class="fixed inset-0 bg-black/40 z-40 flex items-end">
        <div class="bg-white w-full rounded-t-2xl shadow-xl">
          <div class="p-4 border-b flex justify-between items-center">
            <div>
              <div class="text-xs text-blue-600 font-bold uppercase tracking-wide">Editare & Discount</div>
              <div class="font-bold text-gray-900">{{ productSheet.product?.name }}</div>
              <div class="text-xs text-gray-400">Cod: {{ productSheet.product?.internal_code || productSheet.product?.sku }}</div>
            </div>
            <button @click="productSheet.show = false" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100">✕</button>
          </div>
          <div class="px-4 pt-3">
            <div class="flex gap-4 text-xs font-bold text-gray-500">
              <button @click="productSheet.tab='general'" :class="productSheet.tab==='general' ? 'text-blue-600' : ''">GENERAL</button>
              <button @click="productSheet.tab='prices'" :class="productSheet.tab==='prices' ? 'text-blue-600' : ''">PREȚURI</button>
              <button @click="productSheet.tab='others'" :class="productSheet.tab==='others' ? 'text-blue-600' : ''">ALTELE</button>
            </div>
          </div>
          <div class="p-4">
            <div v-show="productSheet.tab==='general'" class="space-y-4">
              <div>
                <div class="text-xs text-gray-500 mb-1">Unitate de măsură</div>
                <div class="flex gap-2">
                  <button v-for="u in unitsOfMeasure" :key="u.value" @click="setItemUnit(productSheet.product, u.value)" class="px-2 py-1 rounded border text-xs" :class="getItemUnit(productSheet.product) === u.value ? 'bg-blue-600 text-white border-blue-600' : 'bg-white'">
                    {{ u.label }}
                  </button>
                </div>
              </div>
              <div>
                <div class="text-xs text-gray-500 mb-2">DISCOUNT COMERCIAL (LINIE)</div>
                
                <!-- Manual Discount -->
                        <div v-if="selectedCustomer && (selectedCustomer.allow_line_discount || canOverride)" class="mb-3 pb-3 border-b border-gray-100">
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Discount Comercial (%)
                                <span class="text-[10px] font-normal ml-1" :class="discountRules.apply_to_total ? 'text-orange-500' : 'text-blue-500'">
                                    ({{ discountRules.apply_to_total ? 'Limită la Total' : 'Limită doar Manual' }})
                                </span>
                            </label>
                            <div class="flex gap-2">
                                <input 
                                    type="number" 
                                    min="0" 
                                    :max="discountRules.max_discount" 
                                    placeholder="0"
                                    :value="getItemDiscountPercent(productSheet.product)"
                                    @input="(e) => setItemDiscountPercent(productSheet.product, parseFloat(e.target.value))"
                                    class="w-24 p-2 border rounded text-center font-bold"
                                >
                                <span class="text-xs text-gray-400 self-center">Max {{ discountRules.max_discount }}% (peste {{ discountRules.approval_threshold }}% necesită aprobare)</span>
                            </div>
                            <div v-if="getItemDiscountPercent(productSheet.product) > discountRules.approval_threshold" class="text-xs text-amber-600 font-bold mt-1 text-center">
                                ⚠️ Necesită derogare
                            </div>
                        </div>

                <div class="space-y-2">
                  <label v-for="opt in getDiscountOptions(productSheet.product)" :key="opt.code" class="flex items-center justify-between p-2 border rounded">
                    <div>
                      <div class="font-bold text-sm">{{ opt.label }}</div>
                      <div class="text-xs text-gray-500">{{ opt.description }}</div>
                    </div>
                    <input type="checkbox" :checked="isDiscountApplied(productSheet.product, opt)" @change="toggleDiscount(productSheet.product, opt)">
                  </label>
                </div>
              </div>
            </div>
            <div v-show="productSheet.tab==='prices'" class="text-sm text-gray-700">
              <div class="flex justify-between"><span>Preț listă</span><span class="font-mono">{{ formatPrice(productSheet.product?.list_price || productSheet.product?.price || 0) }}</span></div>
            </div>
            <div v-show="productSheet.tab==='others'" class="text-sm text-gray-700">
              <div class="text-xs text-gray-500">Informații suplimentare</div>
              <div>Stoc: {{ productSheet.product?.stock_qty ?? '-' }}</div>
            </div>
          </div>
          <div class="p-4 border-t flex justify-end">
            <button @click="productSheet.show = false" class="px-3 py-2 rounded border">Închide</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- PREVIEW MODAL -->
    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
      <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white w-full max-w-lg rounded-lg shadow-xl max-h-[90vh] flex flex-col overflow-hidden">
          <div class="p-4 border-b flex justify-between items-center bg-blue-600 text-white">
            <h3 class="font-bold text-lg">Previzualizare Comandă</h3>
            <button @click="showPreview = false" class="text-blue-100 hover:text-white">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <div class="flex-1 overflow-y-auto p-4 space-y-4">
             <div class="bg-gray-50 p-3 rounded text-sm space-y-1">
                 <div class="font-bold text-gray-900">{{ selectedCustomer?.name }}</div>
                 <div class="text-gray-500">Dată livrare: <span class="text-gray-900 font-medium">{{ orderDetails.deliveryDate }}</span></div>
                 <div class="text-gray-500">Metodă plată: <span class="text-gray-900 font-medium">{{ orderDetails.paymentMethod }}</span></div>
                 <div v-if="orderDetails.internalNote" class="text-gray-500">Notă: <span class="text-gray-900 italic">{{ orderDetails.internalNote }}</span></div>
             </div>
             
             <div class="space-y-2">
                 <h4 class="font-bold text-gray-700 text-xs uppercase">Produse ({{ items.length }})</h4>
                 <div v-for="item in items" :key="item.product_id" class="flex justify-between items-start text-sm border-b border-gray-100 pb-2">
                     <div class="flex-1">
                         <div class="font-medium text-gray-900">{{ item.name }}</div>
                         <div class="text-xs text-gray-500">{{ item.quantity }} {{ item.unit }} x {{ formatPrice(effectiveUnitPrice(item)) }}</div>
                         <div v-if="item.applied_promotions && item.applied_promotions.length" class="text-xs text-green-600 mt-1">
                              <div v-for="promo in item.applied_promotions" :key="promo.id">
                                  <i class="bi bi-tag-fill"></i> {{ promo.name }} <span v-if="promo.discount_amount">(-{{ formatPrice(promo.discount_amount) }})</span>
                              </div>
                          </div>
                     </div>
                     <div class="font-bold text-gray-900">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                 </div>
             </div>
             
             <div class="border-t pt-4 space-y-2 text-sm">
                 <div class="flex justify-between">
                     <span class="text-gray-600">Subtotal</span>
                     <span class="font-medium">{{ formatPrice(cartSubtotal) }}</span>
                 </div>
                 <div class="flex justify-between text-green-600" v-if="cartDiscount > 0">
                     <span>Total Reduceri</span>
                     <span>-{{ formatPrice(cartDiscount) }}</span>
                 </div>
                 <div class="flex justify-between text-purple-600" v-if="orderDetails.globalDiscount > 0">
                     <span>Discount Financiar ({{ orderDetails.globalDiscount }}%)</span>
                     <span>-{{ formatPrice(globalDiscountValue) }}</span>
                 </div>
                 <div class="flex justify-between text-lg font-bold text-blue-900 border-t pt-2 mt-2">
                     <span>Total</span>
                     <span>{{ formatPrice(finalTotal) }}</span>
                 </div>
             </div>
          </div>
          <div class="p-4 border-t bg-gray-50">
            <button @click="submitOrder" :disabled="loadingSubmit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold shadow hover:bg-green-700 disabled:opacity-50">
                {{ loadingSubmit ? 'Se trimite...' : 'Trimite Comanda' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- TOAST -->
    <Transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="toast.show" class="fixed bottom-20 left-4 right-4 z-50 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center justify-between">
            <span class="text-sm font-bold">{{ toast.message }}</span>
            <button @click="toast.show = false" class="text-gray-400 hover:text-white">✕</button>
        </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api, { adminApi } from '@/services/http';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import { useVisitStore } from '@/store/visit';

// Simple debounce
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      fn(...args);
    }, delay);
  };
};

const router = useRouter();
const visitStore = useVisitStore();

// UI State
const activeTab = ref('antet'); // Default to ANTET
const showFilters = ref(false);
const loadingSubmit = ref(false);
const mainScroll = ref(null);
const showSummary = ref(true); // Collapsible footer
const showPreview = ref(false);
const productSheet = ref({ show: false, product: null, tab: 'general' });

// Products State
const products = ref([]);
const loadingProducts = ref(false);
const loadingMore = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const totalProducts = ref(0);

// Filters State
const allCategories = ref([]);
const selectedCategory = ref(null);
const selectedSubcategory = ref(null);
const selectedSort = ref('default');

const sortOptions = [
    { label: 'Implicit', value: 'default' },
    { label: 'Nume (A-Z)', value: 'name_asc' },
    { label: 'Nume (Z-A)', value: 'name_desc' },
    { label: 'Preț (Crescător)', value: 'price_asc' },
    { label: 'Preț (Descrescător)', value: 'price_desc' },
];

// Customer State
const selectedCustomer = ref(null);
const addresses = ref([]);
const shippingMethods = ref([]);

// Cart State
const items = ref([]);
const cartSubtotal = ref(0);
const cartDiscount = ref(0);
const cartTax = ref(0);
const cartTotal = ref(0);

// Promotions State
const availablePromotions = ref([]);
const loadingPromotions = ref(false);

// Discount Rules (fetched from backend)
const discountRules = ref({ max_discount: 20, approval_threshold: 15 });

const orderDetails = reactive({
    deliveryDate: new Date().toISOString().slice(0, 10),
    paymentMethod: 'CHS',
    paymentDocument: '',
    globalDiscount: 0,
    internalNote: '',
    shippingMethodId: null,
    billingAddressId: null,
    shippingAddressId: null
});

// Toast
const toast = ref({ show: false, message: '', type: 'success' });
let toastTimer = null;

const showToast = (msg, type = 'success') => {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = { show: true, message: msg, type };
    toastTimer = setTimeout(() => {
        toast.value.show = false;
    }, 3000);
};

// Computed
const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedCategory.value) count++;
    if (selectedSubcategory.value) count++;
    if (selectedSort.value !== 'default') count++;
    return count;
});

const rootCategories = computed(() => allCategories.value.filter(c => !c.parent_id));

const subcategories = computed(() => {
    if (!selectedCategory.value) return [];
    return allCategories.value.filter(c => c.parent_id === selectedCategory.value.id);
});

const getSubcategories = (parentId) => {
    return allCategories.value.filter(c => c.parent_id === parentId);
};

const hasMorePages = computed(() => currentPage.value < lastPage.value);

const totals = computed(() => {
    // Backend sync check
    if (cartTotal.value > 0) {
        return {
            subtotal: cartSubtotal.value,
            vat: cartTax.value,
            total: cartTotal.value
        };
    }
    const subtotal = items.value.reduce((acc, item) => acc + effectiveLineTotal(item), 0);
    const vat = subtotal * 0.19; 
    return {
        subtotal,
        vat,
        total: subtotal + vat
    };
});

const globalDiscountValue = computed(() => {
    if (!orderDetails.globalDiscount) return 0;
    return totals.value.subtotal * (orderDetails.globalDiscount / 100);
});

const finalTotal = computed(() => {
    if (cartTotal.value > 0) return cartTotal.value;
    return totals.value.subtotal - globalDiscountValue.value;
});

// Helper
const formatPrice = (value) => {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(value);
};

const effectiveUnitPrice = (item) => {
    const base = item.unit_price || 0;
    const percent = item.discount_percent || 0;
    const value = item.discount_value || 0;
    const afterPercent = base * (1 - percent / 100);
    const final = Math.max(afterPercent - value, 0);
    return final;
};

const effectiveLineTotal = (item) => effectiveUnitPrice(item) * (item.quantity || 0);

const unitsOfMeasure = [
  { value: 'buc', label: 'BUC' },
  { value: 'sac', label: 'SAC' },
  { value: 'bax', label: 'BAX' },
  { value: 'palet', label: 'PALET' },
];
// --- DATA FETCHING ---
const fetchCategories = async () => {
    try {
        const { data } = await adminApi.get('/categories');
        allCategories.value = data.data || [];
    } catch (e) {
        console.error('Error fetching categories', e);
    }
};

const fetchProducts = async (page = 1, append = false) => {
    if (page === 1) loadingProducts.value = true;
    else loadingMore.value = true;

    try {
        const params = {
            page,
            per_page: 20,
            search: searchQuery.value,
        };

        if (selectedSort.value && selectedSort.value !== 'default') {
            const [field, dir] = selectedSort.value.split('_');
            params.sort_by = field;
            params.sort_dir = dir;
        }

        if (selectedCategory.value) {
             const catId = selectedSubcategory.value ? selectedSubcategory.value.id : selectedCategory.value.id;
             params.category_id = catId;
        }

        const { data } = await adminApi.get('/products', { params });
        
        const newProducts = data.data || [];
        if (append) {
            products.value = [...products.value, ...newProducts];
        } else {
            products.value = newProducts;
        }

        const meta = data.meta || {};
        currentPage.value = meta.current_page || page;
        lastPage.value = meta.last_page || 1;
        totalProducts.value = meta.total || 0;

    } catch (e) {
        console.error('Error fetching products', e);
        showToast('Eroare la încărcarea produselor', 'error');
    } finally {
        loadingProducts.value = false;
        loadingMore.value = false;
    }
};

const fetchPromotions = async () => {
    if (!selectedCustomer.value) return;
    loadingPromotions.value = true;
    try {
        const { data } = await adminApi.get(`/customers/${selectedCustomer.value.id}/promotions`);
        availablePromotions.value = data.data || [];
    } catch (e) {
        console.error('Error fetching promotions', e);
    } finally {
        loadingPromotions.value = false;
    }
};

const canOverride = ref(false);

const fetchCheckoutData = async () => {
    if (!selectedCustomer.value) return;
    try {
        const { data } = await adminApi.get('/quick-order/checkout-data', {
            params: { customer_id: selectedCustomer.value.id }
        });
        addresses.value = data.addresses || [];
        shippingMethods.value = data.shipping_methods || [];
        canOverride.value = data.can_override || false;
        
        if (data.discount_rules) {
            discountRules.value = data.discount_rules;
        }

        // Defaults
        if (addresses.value.length > 0) {
            const defaultAddr = addresses.value.find(a => a.is_default) || addresses.value[0];
            orderDetails.billingAddressId = defaultAddr.id;
            orderDetails.shippingAddressId = defaultAddr.id;
        }
        if (shippingMethods.value.length > 0) {
            orderDetails.shippingMethodId = shippingMethods.value[0].id;
        }
    } catch (e) {
        console.error('Error fetching checkout data:', e);
    }
};

// --- EVENT HANDLERS ---
const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    if (customer) {
        fetchPromotions();
        fetchCart();
        fetchCheckoutData();
    } else {
        items.value = [];
        cartSubtotal.value = 0;
        cartTotal.value = 0;
        cartDiscount.value = 0;
    }
};

const onSearchInput = debounce(() => {
    fetchProducts(1);
}, 400);

const onCategoryChange = () => {
    selectedSubcategory.value = null;
};

const resetFilters = () => {
    selectedCategory.value = null;
    selectedSubcategory.value = null;
    selectedSort.value = 'default';
    searchQuery.value = '';
    showFilters.value = false;
    fetchProducts(1);
};

const loadMore = () => {
    if (hasMorePages.value) {
        fetchProducts(currentPage.value + 1, true);
    }
};

const applyPromotion = async (promo) => {
    if (!selectedCustomer.value) return;
    try {
        await api.post(`/cart/promotions/${promo.id}`, {}, {
            headers: { 'X-Customer-ID': selectedCustomer.value.id }
        });
        showToast(`Promoția ${promo.name} a fost aplicată!`);
        await fetchCart();
        activeTab.value = 'cart';
    } catch (e) {
        console.error(e);
        showToast('Eroare la aplicarea promoției', 'error');
    }
};

const applyPromoToCart = (promo) => {
    applyPromotion(promo);
};

const fetchCart = async () => {
    if (!selectedCustomer.value) return;
    
    try {
        const { data } = await api.get('/cart', {
            headers: { 'X-Customer-ID': selectedCustomer.value.id }
        });
        
        // Update totals from backend
        cartSubtotal.value = data.subtotal || 0;
        cartTotal.value = data.total || 0;
        cartDiscount.value = data.discount_total || 0;
        // Tax might be included or calculated. Backend sends 'total' (usually inc tax or ex tax depending on config)
        // For now, let's assume total is final.
        
        // Map items
        const backendItems = data.items || [];
        
        // Merge strategy: We want to keep local overrides if possible, but quantity must match backend
        // For simplicity and correctness with backend rules, we'll replace items, 
        // but we could try to preserve manual inputs if we had a stable ID.
        
        items.value = backendItems.map(bi => {
            const unitPrice = parseFloat(bi.unit_base_price || bi.unit_price);
            const finalUnitPrice = parseFloat(bi.unit_final_price || bi.unit_price);
            let discountPercent = 0;
            if (unitPrice > 0 && finalUnitPrice < unitPrice) {
                discountPercent = ((unitPrice - finalUnitPrice) / unitPrice) * 100;
            }

            return {
                product_id: bi.product_id,
                product_name: bi.product_name || bi.product?.name || 'Produs',
                name: bi.product_name || bi.product?.name || 'Produs', // Ensure name is available for Preview Modal
                sku: bi.sku || bi.product?.internal_code || bi.product?.sku,
                unit_price: unitPrice, 
                quantity: bi.quantity,
                max_qty: bi.product?.stock_qty,
                unit: 'buc', // Default
                discount_percent: discountPercent, 
                discount_value: 0,
                cart_item_id: bi.id,
                total: parseFloat(bi.line_final_total || bi.line_total || bi.total),
                applied_promotions: bi.applied_promotions || []
            };
        });
        
    } catch (e) {
        console.error('Error fetching cart:', e);
    }
};

const syncCartWithBackend = debounce(fetchCart, 500);

// --- CART LOGIC ---
const getCartQuantity = (productId) => {
    const item = items.value.find(i => i.product_id === productId);
    return item ? item.quantity : 0;
};

const setCartQuantity = async (product, newQty) => {
    if (newQty < 0) newQty = 0;
    if (!selectedCustomer.value) {
        showToast('Selectează un client mai întâi!', 'error');
        return;
    }

    // Optimistic update (optional, but good for UI responsiveness)
    // We'll skip optimistic update for now to ensure data consistency, 
    // or we could do a local update then fetch.
    
    try {
        const headers = { 'X-Customer-ID': selectedCustomer.value.id };
        
        if (newQty === 0) {
            // Find item to delete
            const item = items.value.find(i => i.product_id === product.id);
            if (item && item.cart_item_id) {
                await api.delete(`/cart/items/${item.cart_item_id}`, { headers });
            }
        } else {
            // Add or Update
            // The backend /cart/items endpoint handles add (and update if logic exists, but CartController has separate update)
            // CartController: POST /items (add), PUT /items/{id} (update)
            // We need to know if it's an add or update.
            
            const existingItem = items.value.find(i => i.product_id === product.id);
            
            if (existingItem && existingItem.cart_item_id) {
                await api.put(`/cart/items/${existingItem.cart_item_id}`, { 
                    quantity: newQty 
                }, { headers });
            } else {
                await api.post('/cart/items', {
                    product_id: product.id,
                    quantity: newQty
                }, { headers });
            }
        }
        
        await fetchCart();
        
    } catch (e) {
        console.error('Error updating cart:', e);
        showToast('Eroare la actualizarea coșului', 'error');
    }
};

const updateProductQuantity = (product, change) => {
    const currentQty = getCartQuantity(product.id);
    setCartQuantity(product, currentQty + change);
};

const updateQuantity = (index, change) => {
    const item = items.value[index];
    if (!item) return;
    const newQty = (item.quantity || 0) + change;
    
    // Use setCartQuantity which now handles backend sync
    setCartQuantity({ id: item.product_id }, newQty);
};

// Units
const getItemUnit = (product) => {
  const item = items.value.find(i => i.product_id === product.id);
  return item ? item.unit : 'buc';
};
const setItemUnit = (product, unit) => {
  const item = items.value.find(i => i.product_id === product.id);
  if (item) item.unit = unit;
};
const toggleItemUnit = (product) => {
  const current = getItemUnit(product);
  const idx = unitsOfMeasure.findIndex(u => u.value === current);
  const next = unitsOfMeasure[(idx + 1) % unitsOfMeasure.length].value;
  setItemUnit(product, next);
};

// Product Details
const openProductDetails = (product) => {
  productSheet.value = { show: true, product, tab: 'general' };
};

// Discounts
const getItemDiscountPercent = (product) => {
    const item = items.value.find(i => i.product_id === product.id);
    return item ? item.discount_percent : 0;
};

const setItemDiscountPercent = (product, percent) => {
    const item = items.value.find(i => i.product_id === product.id);
    if (!item) return;
    
    if (isNaN(percent) || percent < 0) percent = 0;
    const max = discountRules.value.max_discount;
    if (percent > max) percent = max;
    
    item.discount_percent = percent;
    item.discount_value = 0; // Reset value discount if percent is used
};

const getDiscountOptions = (product) => {
  const opts = [];
  (availablePromotions.value || []).forEach(p => {
    // Filter by applicability
    if (p.applies_to === 'products') {
        const applies = p.products?.some(prod => prod.id === product.id);
        if (!applies) return;
    }

    const percent = p.benefit?.discountPercent || p.discount_percent;
    const value = p.benefit?.discountValue || p.discount_value;
    
    if (percent > 0) {
      opts.push({
        code: `PROMO_${p.id}`,
        label: p.name || `Discount ${percent}%`,
        description: 'de linie, modificabil',
        type: 'percent',
        percent
      });
    } else if (value > 0) {
      opts.push({
        code: `PROMO_${p.id}`,
        label: p.name || `Discount ${formatPrice(value)}`,
        description: 'de linie, modificabil',
        type: 'value',
        value
      });
    }
  });
  return opts;
};

const isDiscountApplied = (product, opt) => {
  const item = items.value.find(i => i.product_id === product.id);
  if (!item) return false;
  return opt.type === 'percent' ? item.discount_percent === opt.percent : item.discount_value === opt.value;
};

const toggleDiscount = (product, opt) => {
  const item = items.value.find(i => i.product_id === product.id);
  if (!item) return;
  if (opt.type === 'percent') {
    item.discount_percent = item.discount_percent === opt.percent ? 0 : opt.percent;
    item.discount_value = 0;
  } else {
    item.discount_value = item.discount_value === opt.value ? 0 : opt.value;
    item.discount_percent = 0;
  }
};
const submitOrder = async () => {
    if (!selectedCustomer.value) {
        showToast('Selectează un client!', 'error');
        activeTab.value = 'antet';
        return;
    }
    
    loadingSubmit.value = true;
    try {
        await adminApi.post('/quick-order/create', {
            customer_id: selectedCustomer.value.id,
            customer_visit_id: visitStore.activeVisit?.id || null,
            due_date: orderDetails.deliveryDate,
            payment_method: orderDetails.paymentMethod,
            payment_document: orderDetails.paymentDocument,
            global_discount_percent: orderDetails.globalDiscount,
            internal_note: orderDetails.internalNote,
            shipping_method_id: orderDetails.shippingMethodId,
            billing_address_id: orderDetails.billingAddressId,
            shipping_address_id: orderDetails.shippingAddressId,
            items: items.value.map(i => {
                let priceOverride = null;
                let discountOverride = null;

                if (i.discount_value > 0) {
                    // If value discount, calculate new price and send as override
                    priceOverride = Math.max((i.unit_price || 0) - i.discount_value, 0);
                } else if (i.discount_percent > 0) {
                    discountOverride = i.discount_percent;
                }

                return {
                    product_id: i.product_id,
                    quantity: i.quantity,
                    price_override: priceOverride,
                    discount_override: discountOverride,
                };
            })
        });
        showToast('Comanda a fost plasată cu succes!');
        items.value = [];
        router.push('/admin/orders');
    } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || 'Eroare la plasarea comenzii';
        showToast(msg, 'error');
    } finally {
        loadingSubmit.value = false;
        showPreview.value = false;
    }
};

// Init
onMounted(() => {
    fetchProducts();
    fetchCategories();
    if (visitStore.hasActiveVisit) {
        const v = visitStore.activeVisit;
        if (v && v.customer) {
            selectedCustomer.value = v.customer;
            fetchPromotions();
            fetchCart(); // Fetch cart on mount if customer exists
            fetchCheckoutData(); // Fetch checkout data on mount
        }
    } else {
        visitStore.checkActiveVisit();
    }
});

const hasActiveVisit = computed(() => visitStore.hasActiveVisit);
const goToPlanning = () => router.push({ name: 'account-agent-routes' });
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.animate-slide-up {
    animation: slideUp 0.3s ease-out;
}
@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
