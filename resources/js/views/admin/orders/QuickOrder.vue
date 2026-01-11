<template>
  <div 
    class="flex flex-col bg-gray-50 font-sans text-sm relative overflow-hidden transition-all duration-300"
    :class="[
      isFullscreen ? 'h-full w-full' : 'h-[85vh] min-h-[600px] rounded-xl border border-gray-200 shadow-lg my-4'
    ]"
  >
    
    <!-- AGENT BLOCKING OVERLAYS -->
    <Teleport to="body">
    <div v-if="showStartProgramOverlay" class="fixed inset-0 z-[2147483646] bg-white/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center" style="z-index: 2147483646 !important;">
        <div class="bg-white p-8 rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full animate-bounce-in">
            <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Programul nu a început</h2>
            <p class="text-gray-500 mb-6">Pentru a prelua comenzi, trebuie să începi programul de lucru.</p>
            <button @click="startProgram" :disabled="trackingStore.loading" class="w-full py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-600/20 hover:bg-orange-700 transition-all flex items-center justify-center gap-2">
                <span v-if="trackingStore.loading" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                Start Program
            </button>
            <p class="text-xs text-gray-400 mt-4 cursor-pointer hover:underline hover:text-gray-600" @click="checkAgentStatus">
                Verifică status din nou (Debug: {{ trackingStore.isShiftActive ? 'Activ' : 'Inactiv' }})
            </p>
        </div>
    </div>
    </Teleport>

    <Teleport to="body">
    <div v-if="showStartVisitOverlay" class="fixed inset-0 z-[2147483646] bg-white/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center" style="z-index: 2147483646 !important;">
        <div class="bg-white p-8 rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full animate-bounce-in">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Nu ești într-o vizită</h2>
            <p class="text-gray-500 mb-6">Selectează un client pentru a începe vizita și a prelua comanda.</p>
            
            <div class="mb-4 text-left">
                <CustomerSelector @select="startVisitWithCustomer" :compact="false" />
            </div>

            <div v-if="visitStore.loading" class="text-blue-600 font-bold text-sm mt-2 flex items-center justify-center gap-2">
                <span class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent"></span>
                Se începe vizita...
            </div>
            <p class="text-xs text-gray-400 mt-4 cursor-pointer hover:underline hover:text-gray-600" @click="checkAgentStatus">
                Verifică status din nou (Visit: {{ visitStore.hasActiveVisit ? 'Activ' : 'Inactiv' }})
            </p>
        </div>
    </div>
    </Teleport>

    <!-- DESKTOP HEADER -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shrink-0 z-20 shadow-sm">
      <div class="flex items-center gap-4 min-w-0">
        <button @click="router.back()" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors hidden md:block">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </button>
        <div>
          <h1 class="text-lg font-bold text-gray-800 leading-tight">Comandă Rapidă</h1>
          <div class="flex items-center gap-2 text-xs text-gray-500" v-if="selectedCustomer">
            <span class="font-medium text-blue-600">{{ selectedCustomer.name }}</span>
            <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
            <span>{{ selectedCustomer.cif || 'Fără CUI' }}</span>
            <span v-if="visitStore.hasActiveVisit" class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-bold text-[10px] uppercase">Vizită Activă</span>
          </div>
        </div>
      </div>

      <!-- Desktop Search (Center) -->
      <div class="hidden md:flex flex-1 max-w-xl mx-4 relative">
          <input 
              v-model="searchQuery"
              @input="onSearchInput"
              @keyup.enter="fetchProducts"
              type="text" 
              placeholder="Caută produse..." 
              class="w-full pl-9 pr-4 py-2 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all"
          >
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
      </div>

      <div class="flex items-center gap-3">
        <!-- Promotions Button -->
        <button 
            @click="showPromotionsPanel = true"
            class="hidden md:flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
            Promoții
            <span v-if="activePromotions.length" class="bg-white text-purple-600 px-1.5 py-0.5 rounded text-xs font-bold">{{ activePromotions.length }}</span>
        </button>

        <!-- Filter Button (Desktop) -->
        <button @click="showFilters = true" class="hidden md:flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-lg font-bold hover:bg-gray-50 transition-all relative">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filtre
            <span v-if="activeFiltersCount > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full ring-2 ring-white"></span>
        </button>

        <!-- Customer Selector (Compact) - Disabled if in visit -->
        <div v-if="!isCustomer && !visitStore.hasActiveVisit" class="w-64 hidden lg:block">
          <CustomerSelector @select="selectCustomer" :compact="true" />
        </div>
        
        <!-- Mobile/Tablet Actions -->
        <div class="flex lg:hidden items-center gap-2">
           <button @click="showPromotionsPanel = true" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg md:hidden">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
           </button>
           <button @click="showFilters = true" class="p-2 relative text-gray-600 hover:bg-gray-100 rounded-lg md:hidden">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
              <span v-if="activeFiltersCount > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
           </button>
        </div>
      </div>
    </header>

    <!-- MAIN LAYOUT -->
    <div class="flex-1 flex overflow-hidden relative">

      <!-- CENTER PANEL (Product Grid) -->
      <main class="flex-1 flex flex-col bg-gray-50 min-w-0 relative" :class="{'hidden lg:flex': activeTab !== 'products'}">
        
        <!-- Mobile Sticky Header -->
        <div class="lg:hidden sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
             <div class="relative">
                <input 
                    v-model="searchQuery"
                    @input="onSearchInput"
                    @keyup.enter="fetchProducts"
                    type="text" 
                    placeholder="Caută produse..." 
                    class="w-full pl-9 pr-4 py-2.5 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all shadow-inner"
                >
                <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 pb-24 lg:pb-4" ref="mainScroll">
            <!-- Loading Skeleton -->
            <div v-if="loadingProducts && products.length === 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="n in 9" :key="n" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 animate-pulse flex flex-col h-[200px]">
                    <div class="flex justify-between mb-4">
                        <div class="w-20 h-4 bg-gray-100 rounded"></div>
                        <div class="w-16 h-4 bg-gray-100 rounded"></div>
                    </div>
                    <div class="space-y-2 mb-auto">
                        <div class="w-full h-4 bg-gray-100 rounded"></div>
                        <div class="w-2/3 h-4 bg-gray-100 rounded"></div>
                    </div>
                    <div class="flex justify-between items-end mt-4">
                        <div class="w-24 h-6 bg-gray-100 rounded"></div>
                        <div class="w-8 h-8 bg-gray-100 rounded"></div>
                    </div>
                </div>
            </div>

            <div v-else-if="products.length === 0" class="flex flex-col items-center justify-center h-64 text-gray-400">
                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                <p class="font-medium">Nu am găsit produse.</p>
                <button @click="resetFilters" class="mt-2 text-blue-600 font-bold hover:underline">Resetează Filtre</button>
            </div>

            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col divide-y divide-gray-100">
                <div v-for="product in products" :key="product.id" class="group p-3 hover:bg-blue-50/30 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:gap-4">
                        <!-- Desktop Image (Left) -->
                        <div class="hidden lg:flex w-12 h-12 bg-white rounded-lg border border-gray-200 shrink-0 items-center justify-center overflow-hidden p-1 cursor-pointer" @click="openProductDetails(product)">
                            <img v-if="product.image_url" :src="product.image_url" class="w-full h-full object-contain" alt="Product">
                            <svg v-else class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>

                        <!-- Title & Meta -->
                        <div class="mb-3 lg:mb-0 lg:flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-1 lg:mb-0.5">
                                <p class="font-bold text-gray-900 text-xs leading-tight cursor-pointer hover:text-blue-600 line-clamp-2" @click="openProductDetails(product)">
                                    {{ product.name }}
                                </p>
                                <!-- Mobile Info Icon -->
                                <button @click="openProductDetails(product)" class="lg:hidden shrink-0 text-gray-300 hover:text-blue-600 transition-colors" title="Detalii complete">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                <!-- Desktop Info Icon -->
                                <button @click="openProductDetails(product)" class="hidden lg:block text-gray-300 hover:text-blue-600 transition-colors mr-1" title="Detalii complete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-600 border border-gray-200">{{ product.code }}</span>
                                <span :class="product.stock_qty > 0 ? 'text-green-600' : 'text-red-500'" class="font-bold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    {{ product.stock_qty > 0 ? 'Stoc' : 'Indisponibil' }}
                                </span>
                                <span v-if="product.brand" class="text-gray-400 border-l border-gray-200 pl-2">{{ product.brand.name }}</span>
                            </div>
                        </div>

                        <!-- Right Section (Price, Controls, Mobile Image) -->
                        <div class="flex items-center gap-3 lg:gap-6 lg:w-auto lg:shrink-0">
                            <!-- Mobile Image -->
                            <div class="lg:hidden w-12 h-12 bg-white rounded-lg border border-gray-200 shrink-0 flex items-center justify-center overflow-hidden p-1 cursor-pointer" @click="openProductDetails(product)">
                                <img v-if="product.image_url" :src="product.image_url" class="w-full h-full object-contain" alt="Product">
                                <svg v-else class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>

                            <!-- Price -->
                            <div class="flex-1 min-w-0 lg:flex-none lg:text-right lg:w-32">
                                <div class="font-bold text-blue-900 text-lg leading-none">{{ formatPrice(product.list_price || product.price) }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-medium">/ {{ getItemUnit(product) }}</div>
                            </div>

                            <!-- Controls -->
                            <div class="flex items-center gap-2">
                                <!-- Qty -->
                                <div class="flex items-center bg-white rounded-lg border border-gray-300 h-9 w-28 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all shadow-sm">
                                    <button @click="updateProductQuantity(product, -1)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-red-600 rounded-l-lg transition-colors text-lg font-bold pb-1 select-none">−</button>
                                    <input 
                                        type="number" 
                                        v-model.number="product.cart_quantity" 
                                        @input="setCartQuantity(product, typeof product.cart_quantity === 'number' ? product.cart_quantity : 0)"
                                        class="w-full text-center font-bold text-gray-900 text-sm border-none focus:ring-0 p-0 h-full bg-transparent"
                                        placeholder="0"
                                    >
                                    <button @click="updateProductQuantity(product, 1)" class="w-8 h-full flex items-center justify-center text-blue-600 hover:bg-blue-50 rounded-r-lg transition-colors text-lg font-bold pb-1 select-none">+</button>
                                </div>
                                
                                <!-- Unit -->
                                <button 
                                    @click="toggleItemUnit(product)" 
                                    class="h-9 px-2 flex flex-col items-center justify-center text-[10px] font-bold text-gray-600 uppercase border border-gray-300 rounded-lg hover:bg-gray-50 hover:border-blue-300 hover:text-blue-600 transition-all bg-white shadow-sm min-w-[3rem] select-none"
                                    title="Schimbă unitatea"
                                >
                                    <span>{{ getItemUnit(product) }}</span>
                                    <span class="text-[8px] text-gray-400 font-normal leading-none" v-if="getProductUnits(product).find(u => u.unit === getItemUnit(product))?.conversion_factor > 1">
                                        x{{ getProductUnits(product).find(u => u.unit === getItemUnit(product))?.conversion_factor }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Load More -->
            <div v-if="hasMorePages" class="py-6 text-center">
                <button @click="loadMore" :disabled="loadingMore" class="px-6 py-2 bg-white text-gray-600 text-sm font-bold rounded-full border border-gray-200 hover:bg-gray-50 hover:border-blue-300 transition-colors shadow-sm">
                    {{ loadingMore ? 'Se încarcă...' : 'Încarcă mai multe' }}
                </button>
            </div>
        </div>
      </main>

      <!-- RIGHT SIDEBAR (Cart & Checkout) - Desktop Only -->
      <aside class="w-96 bg-white border-l border-gray-200 hidden lg:flex flex-col shrink-0 z-10 shadow-[-4px_0_15px_rgba(0,0,0,0.02)]">
        <div class="p-4 border-b border-gray-200 bg-gray-50/50">
             <div class="flex justify-between items-center mb-4">
                 <h2 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-700 p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg></span>
                    Coșul Curent
                 </h2>
                 <span class="bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full">{{ items.length }}</span>
             </div>

             <!-- Mini Stats -->
             <div class="grid grid-cols-2 gap-3 mb-2">
                 <div class="bg-white p-2 rounded border border-gray-200">
                     <div class="text-[10px] text-gray-400 uppercase font-bold">Sold</div>
                     <div class="text-sm font-mono font-bold text-gray-800">{{ selectedCustomer ? formatPrice(selectedCustomer.balance) : '-' }}</div>
                 </div>
                 <div class="bg-white p-2 rounded border border-gray-200">
                     <div class="text-[10px] text-gray-400 uppercase font-bold">Limită</div>
                     <div class="text-sm font-mono font-bold text-gray-800">{{ selectedCustomer ? formatPrice(selectedCustomer.credit_limit) : '-' }}</div>
                 </div>
             </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-4">
            <div v-if="items.length === 0" class="text-center py-10 text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200 mx-2">
                <p>Coșul este gol.</p>
                <p class="text-xs mt-1">Adaugă produse din listă.</p>
            </div>
            
            <div v-else class="space-y-3">
                <div v-for="(item, index) in items" :key="index" class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm relative group">
                     <button @click="updateQuantity(index, -item.quantity)" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-sm hover:bg-red-200">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                     </button>

                     <div class="flex justify-between items-start mb-2">
                        <div class="flex-1 min-w-0 pr-2">
                            <h4 class="font-bold text-sm text-gray-900 leading-tight line-clamp-2" :title="item.product_name">{{ item.product_name }}</h4>
                            <div class="text-xs text-gray-400 mt-0.5">{{ item.sku }}</div>
                            <!-- Applied Promotions Tags -->
                            <div v-if="item.applied_promotions && item.applied_promotions.length > 0" class="flex flex-wrap gap-1 mt-1">
                                <span v-for="(pName, pIdx) in item.applied_promotions" :key="pIdx" class="text-[10px] text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100 font-bold flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    {{ pName.name || pName }}
                                </span>
                            </div>
                        </div>
                        <div class="font-bold text-blue-600 text-sm whitespace-nowrap">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                     </div>

                     <div class="flex items-center justify-between">
                         <div class="flex items-center gap-2">
                             <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 h-7">
                                <button @click="updateQuantity(index, -1)" class="w-7 h-full flex items-center justify-center text-gray-500 hover:text-red-500 font-bold">-</button>
                                <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                <button @click="updateQuantity(index, 1)" class="w-7 h-full flex items-center justify-center text-gray-500 hover:text-blue-500 font-bold">+</button>
                             </div>
                             <span class="text-[10px] text-gray-400 uppercase font-bold">{{ item.unit }}</span>
                         </div>
                         
                         <div v-if="item.discount_percent > 0" class="text-xs text-green-600 bg-green-50 px-1.5 py-0.5 rounded font-bold">
                            -{{ item.discount_percent }}%
                         </div>
                     </div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.02)]">
            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span>
                    <span>{{ formatPrice(totals.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-green-600 font-bold" v-if="cartDiscount > 0">
                    <span>Discount</span>
                    <span>-{{ formatPrice(cartDiscount) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>TVA</span>
                    <span>{{ formatPrice(totals.vat) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-gray-900 border-t border-gray-100 pt-2">
                    <span>Total</span>
                    <span class="text-blue-600">{{ formatPrice(totals.total) }}</span>
                </div>
            </div>
            
            <button @click="showPreview = true" class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 hover:shadow-blue-600/30 transition-all transform active:scale-[0.98]">
                Continuă spre Comandă
            </button>
        </div>
      </aside>
    
      <!-- MOBILE TAB VIEWS (Replaces Desktop Main/Aside when hidden) -->
      <div v-if="activeTab === 'cart'" class="flex-1 flex flex-col bg-gray-50 lg:hidden overflow-hidden">
         <!-- Mobile Cart View -->
         <div class="flex-1 overflow-y-auto p-4 space-y-3 pb-24">
             <div v-if="items.length === 0" class="text-center py-20 text-gray-400">
                 <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                 Coșul este gol
             </div>
             <div v-else v-for="(item, index) in items" :key="index" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm line-clamp-2">{{ item.product_name }}</h4>
                        <div class="text-xs text-gray-500 mt-1">{{ item.sku }}</div>
                    </div>
                    <div class="font-bold text-blue-600">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                </div>
                <div class="flex items-center justify-between">
                     <div class="flex items-center bg-gray-100 rounded-lg h-9">
                        <button @click="updateQuantity(index, -1)" class="w-10 h-full flex items-center justify-center font-bold text-gray-600">-</button>
                        <span class="w-8 text-center font-bold text-sm">{{ item.quantity }}</span>
                        <button @click="updateQuantity(index, 1)" class="w-10 h-full flex items-center justify-center font-bold text-blue-600">+</button>
                     </div>
                     <button @click="updateQuantity(index, -item.quantity)" class="text-red-500 p-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                     </button>
                </div>
             </div>
         </div>
         <div class="p-4 bg-white border-t border-gray-200 shadow-up mb-16">
            <div class="space-y-1 mb-3 text-xs">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span>
                    <span>{{ formatPrice(totals.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-green-600 font-bold" v-if="cartDiscount > 0">
                    <span>Discount</span>
                    <span>-{{ formatPrice(cartDiscount) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>TVA</span>
                    <span>{{ formatPrice(totals.vat) }}</span>
                </div>
            </div>
            <div class="flex justify-between items-end">
                <div>
                    <div class="text-xs text-gray-500">Total Final</div>
                    <div class="text-xl font-bold text-blue-900">{{ formatPrice(totals.total) }}</div>
                </div>
                <button @click="showPreview = true" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg shadow-lg">Comandă</button>
            </div>
         </div>
      </div>

      <div v-if="activeTab === 'info'" class="flex-1 overflow-y-auto p-4 pb-24 lg:hidden bg-gray-50">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
              <h3 class="font-bold text-gray-900 mb-4 text-lg">Informații Client</h3>
              <div v-if="selectedCustomer" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                      <div class="p-3 bg-blue-50 rounded-lg">
                          <div class="text-xs text-blue-600 font-bold uppercase">Sold Curent</div>
                          <div class="font-mono font-bold text-lg text-gray-900">{{ formatPrice(selectedCustomer.balance) }}</div>
                      </div>
                      <div class="p-3 bg-green-50 rounded-lg">
                          <div class="text-xs text-green-600 font-bold uppercase">Limită Credit</div>
                          <div class="font-mono font-bold text-lg text-gray-900">{{ formatPrice(selectedCustomer.credit_limit) }}</div>
                      </div>
                  </div>
                  
                  <div class="pt-4 border-t border-gray-100">
                      <div class="flex justify-between py-2 border-b border-gray-50">
                          <span class="text-gray-500 text-sm">CUI / CIF</span>
                          <span class="font-medium text-sm">{{ selectedCustomer.cif || '-' }}</span>
                      </div>
                      <div class="flex justify-between py-2 border-b border-gray-50">
                          <span class="text-gray-500 text-sm">Reg. Com.</span>
                          <span class="font-medium text-sm">{{ selectedCustomer.reg_com || '-' }}</span>
                      </div>
                      <div class="flex justify-between py-2">
                          <span class="text-gray-500 text-sm">Agent</span>
                          <span class="font-medium text-sm">{{ selectedCustomer.agent_name || '-' }}</span>
                      </div>
                  </div>
              </div>
              <div v-else class="text-center py-10">
                  <p class="text-gray-500">Selectează un client pentru a vedea informațiile.</p>
                  <div class="mt-4">
                      <CustomerSelector @select="selectCustomer" />
                  </div>
              </div>
          </div>
      </div>

    </div>

    <!-- MOBILE NAVIGATION BAR -->
    <div class="fixed bottom-0 left-0 right-0 lg:hidden bg-white border-t border-gray-200 flex justify-around items-center h-16 z-50 pb-safe shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <button @click="activeTab = 'products'" class="flex flex-col items-center justify-center w-full h-full space-y-1" :class="activeTab === 'products' ? 'text-blue-600' : 'text-gray-400'">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            <span class="text-[10px] font-bold">Produse</span>
        </button>
        <button @click="activeTab = 'cart'" class="flex flex-col items-center justify-center w-full h-full space-y-1 relative" :class="activeTab === 'cart' ? 'text-blue-600' : 'text-gray-400'">
            <div class="relative">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <span v-if="items.length > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[9px] font-bold flex items-center justify-center rounded-full border border-white">{{ items.length }}</span>
            </div>
            <span class="text-[10px] font-bold">Coș</span>
        </button>
        <button @click="activeTab = 'info'" class="flex flex-col items-center justify-center w-full h-full space-y-1" :class="activeTab === 'info' ? 'text-blue-600' : 'text-gray-400'">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="text-[10px] font-bold">Info</span>
        </button>
    </div>

    <!-- PROMOTIONS PANEL DRAWER -->
    <Teleport to="body">
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showPromotionsPanel" class="fixed inset-0 z-[9999] flex justify-end" role="dialog" aria-modal="true" style="z-index: 99999;">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showPromotionsPanel = false"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300 animate-slide-in-right">
                <div class="p-4 bg-purple-600 text-white flex flex-col gap-3">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                            Promoții Active
                        </h3>
                        <button @click="showPromotionsPanel = false"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            v-model="promoSearchQuery"
                            type="text" 
                            placeholder="Caută în promoții..." 
                            class="w-full pl-9 pr-4 py-2 bg-purple-700/50 text-white placeholder-purple-200 border border-purple-500/30 rounded-lg text-sm focus:ring-2 focus:ring-white/50 outline-none transition-all"
                        >
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                    <div v-if="loadingPromotions" class="flex justify-center py-10">
                        <span class="animate-spin rounded-full h-8 w-8 border-2 border-purple-600 border-t-transparent"></span>
                    </div>
                    
                    <div v-else-if="filteredPromotions.length === 0" class="text-center py-10 text-gray-400">
                        <p v-if="promoSearchQuery">Nu am găsit promoții pentru "{{ promoSearchQuery }}".</p>
                        <p v-else>Nu există promoții active pentru clientul selectat.</p>
                    </div>

                    <div v-else v-for="promo in filteredPromotions" :key="promo.id" class="bg-white rounded-xl border border-purple-100 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-100 bg-purple-50">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div>
                                    <h4 class="font-bold text-purple-900">{{ promo.name }}</h4>
                                    <p class="text-sm text-purple-700 mt-1">{{ promo.description }}</p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-white text-purple-600 rounded border border-purple-100">
                                            {{ promo.bonus_type === 'discount' ? 'Discount' : 'Bonus' }}
                                        </span>
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-white text-purple-600 rounded border border-purple-100">
                                            Min: {{ promo.min_qty }} buc
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <div class="flex items-center bg-white rounded-lg border border-purple-200 h-9">
                                        <button @click="promo.selected_quantity = Math.max(1, (promo.selected_quantity || 1) - 1)" class="w-8 h-full flex items-center justify-center text-purple-500 hover:text-purple-700 font-bold">-</button>
                                        <input type="number" v-model="promo.selected_quantity" class="w-12 text-center bg-transparent border-none text-sm font-bold text-purple-900 focus:ring-0 p-0" min="1">
                                        <button @click="promo.selected_quantity = (promo.selected_quantity || 1) + 1" class="w-8 h-full flex items-center justify-center text-purple-500 hover:text-purple-700 font-bold">+</button>
                                    </div>
                                    <button 
                                        @click="addPromotionToCart(promo)"
                                        class="px-3 py-1.5 bg-purple-600 text-white rounded-lg shadow-sm hover:bg-purple-700 font-bold text-sm flex items-center gap-2 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                        Adaugă
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Associated Products -->
                        <div v-if="promo.products && promo.products.length" class="p-2 space-y-2">
                            <div v-for="prod in promo.products" :key="prod.id" class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                <div class="w-12 h-12 bg-white rounded border border-gray-200 flex items-center justify-center shrink-0">
                                     <img v-if="prod.image_path" :src="prod.image_path" class="w-full h-full object-contain">
                                     <span v-else class="text-[8px] text-gray-400">Fără foto</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-gray-900 line-clamp-2">{{ prod.name }}</div>
                                    <div class="text-xs text-gray-500">{{ prod.sku }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-bold text-purple-600">{{ formatPrice(prod.promo_price) }}</span>
                                        <span class="text-[10px] line-through text-gray-400">{{ formatPrice(prod.base_price) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 h-8">
                                        <button @click="decreasePromoQty(prod, promo)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-red-500 font-bold" :disabled="!promo.is_iterative || getCartQuantity(prod.id) <= 0">-</button>
                                        <input type="number" :value="getCartQuantity(prod.id)" @input="e => updatePromoQtyDirectly(prod, promo, e.target.value)" class="w-10 text-center bg-transparent border-none text-xs font-bold focus:ring-0 p-0" :disabled="!promo.is_iterative">
                                        <button @click="increasePromoQty(prod, promo)" class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-blue-500 font-bold" :disabled="!promo.is_iterative">+</button>
                                    </div>
                                    <button 
                                        v-if="getCartQuantity(prod.id) === 0"
                                        @click="addPromoProductToCart(prod)"
                                        class="p-2 bg-purple-600 text-white rounded-lg shadow-md hover:bg-purple-700 transition-colors"
                                        title="Adaugă în coș"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    </button>
                                    <div v-else class="p-2 w-9 h-9 flex items-center justify-center bg-purple-100 text-purple-700 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
    </Teleport>

    <!-- FILTER DRAWER (Mobile/Tablet) -->
    <Teleport to="body">
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showFilters" class="fixed inset-0 z-[9999] flex justify-end lg:hidden" role="dialog" aria-modal="true" style="z-index: 99999;">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showFilters = false"></div>
            <div class="relative w-80 bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300 animate-slide-in-right">
                <div class="p-4 bg-blue-600 text-white flex justify-between items-center">
                    <h3 class="font-bold text-lg">Filtrare</h3>
                    <button @click="showFilters = false"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 space-y-8 bg-white">
                    <div>
                        <h4 class="text-gray-900 font-bold text-lg mb-4">Sortare</h4>
                        <div class="grid grid-cols-1 gap-3">
                             <button 
                                v-for="option in sortOptions" 
                                :key="option.value"
                                @click="selectedSort = option.value"
                                class="flex items-center justify-between p-4 rounded-xl border transition-all text-left group"
                                :class="selectedSort === option.value ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600' : 'border-gray-200 text-gray-700 hover:border-blue-300 hover:bg-gray-50'"
                             >
                                 <span class="font-medium text-sm">{{ option.label }}</span>
                                 <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors"
                                    :class="selectedSort === option.value ? 'border-blue-600 bg-blue-600' : 'border-gray-300 group-hover:border-blue-400'"
                                 >
                                    <svg v-if="selectedSort === option.value" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                 </div>
                             </button>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-gray-900 font-bold text-lg mb-4">Categorii</h4>
                         <div class="space-y-2">
                             <button 
                                @click="() => { selectedCategory = null; onCategoryChange(); }"
                                class="w-full flex items-center justify-between p-4 rounded-xl border transition-all text-left group"
                                :class="!selectedCategory ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600' : 'border-gray-200 text-gray-700 hover:border-blue-300 hover:bg-gray-50'"
                             >
                                 <span class="font-medium text-sm">Toate Categoriile</span>
                                 <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors"
                                    :class="!selectedCategory ? 'border-blue-600 bg-blue-600' : 'border-gray-300 group-hover:border-blue-400'"
                                 >
                                    <svg v-if="!selectedCategory" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                 </div>
                             </button>
                             
                             <button 
                                v-for="cat in rootCategories" 
                                :key="cat.id"
                                @click="() => { selectedCategory = cat; onCategoryChange(); }"
                                class="w-full flex items-center justify-between p-4 rounded-xl border transition-all text-left group"
                                :class="selectedCategory?.id === cat.id ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600' : 'border-gray-200 text-gray-700 hover:border-blue-300 hover:bg-gray-50'"
                             >
                                 <span class="font-medium text-sm">{{ cat.name }}</span>
                                 <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors"
                                    :class="selectedCategory?.id === cat.id ? 'border-blue-600 bg-blue-600' : 'border-gray-300 group-hover:border-blue-400'"
                                 >
                                    <svg v-if="selectedCategory?.id === cat.id" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                 </div>
                             </button>
                         </div>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-gray-200 flex gap-3">
                    <button @click="resetFilters" class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Resetează</button>
                    <button @click="showFilters = false" class="flex-[2] py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-colors">Vezi Rezultate</button>
                </div>
            </div>
        </div>
    </Transition>
    </Teleport>

    <!-- PREVIEW MODAL -->
    <Teleport to="body">
    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
      <div v-if="showPreview" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="z-index: 99999;">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
          <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-lg text-gray-800">Finalizare Comandă</h3>
            <button @click="showPreview = false" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
              <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
             <!-- Customer Info -->
             <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex gap-4 items-start">
                 <div class="p-2 bg-white rounded-lg text-blue-600 shadow-sm">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                 </div>
                 <div>
                     <h4 class="font-bold text-gray-900">{{ selectedCustomer?.name }}</h4>
                     <p class="text-sm text-gray-600">{{ selectedCustomer?.cif }}</p>
                     <p class="text-xs text-gray-400 mt-1">{{ selectedCustomer?.address }}</p>
                 </div>
             </div>

             <!-- Order Details Form -->
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dată livrare</label>
                     <input type="date" v-model="orderDetails.deliveryDate" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                 </div>
                 <div>
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Metodă Plată</label>
                     <select v-model="orderDetails.paymentMethod" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                        <option value="La termen">La termen</option>
                        <option value="CHS">Numerar (CHS)</option>
                        <option value="BO">Bilet la Ordin (BO)</option>
                        <option value="CEC">CEC</option>
                        <option value="OP">Ordin de Plată (OP)</option>
                     </select>
                 </div>
                 <div class="md:col-span-2" v-if="['BO','CEC','OP'].includes(orderDetails.paymentMethod)">
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Document Plată</label>
                     <input type="text" v-model="orderDetails.paymentDocument" placeholder="Serie/Număr document" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                 </div>
                 <div class="md:col-span-2">
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notă internă</label>
                     <textarea v-model="orderDetails.internalNote" rows="2" placeholder="Adaugă un comentariu..." class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 resize-none"></textarea>
                 </div>
             </div>
             
             <!-- Items Summary -->
             <div>
                 <h4 class="font-bold text-gray-900 mb-3 text-sm border-b border-gray-100 pb-2">Sumar Produse ({{ items.length }})</h4>
                 <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                     <div v-for="item in items" :key="item.product_id" class="flex justify-between items-start text-sm py-1">
                         <div class="flex-1 pr-4">
                             <div class="font-medium text-gray-800">{{ item.name }}</div>
                             <div class="text-xs text-gray-500">{{ item.quantity }} {{ item.unit }} x {{ formatPrice(effectiveUnitPrice(item)) }}</div>
                         </div>
                         <div class="font-bold text-gray-900 whitespace-nowrap">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                     </div>
                 </div>
             </div>
             
             <!-- Totals -->
             <div class="bg-gray-50 p-4 rounded-xl space-y-2 text-sm">
                 <div class="flex justify-between">
                     <span class="text-gray-500">Subtotal</span>
                     <span class="font-medium">{{ formatPrice(cartSubtotal) }}</span>
                 </div>
                 <div class="flex justify-between text-green-600" v-if="cartDiscount > 0">
                     <span>Reduceri</span>
                     <span>-{{ formatPrice(cartDiscount) }}</span>
                 </div>
                 <div class="flex justify-between text-gray-500">
                     <span>TVA</span>
                     <span>{{ formatPrice(totals.vat) }}</span>
                 </div>
                 <div class="flex justify-between text-lg font-bold text-blue-900 border-t border-gray-200 pt-3 mt-1">
                     <span>Total Final</span>
                     <span>{{ formatPrice(finalTotal) }}</span>
                 </div>
             </div>

            <!-- Financial Risk Warning -->
            <div v-if="financialRisk && financialRisk.status !== 'safe'" 
                    class="mt-4 p-4 rounded-lg border text-sm"
                    :class="{
                        'bg-red-50 border-red-200 text-red-800': isOrderBlocked,
                        'bg-orange-50 border-orange-200 text-orange-800': !isOrderBlocked
                    }"
            >
                <div class="flex items-start">
                    <div class="shrink-0">
                        <svg v-if="isOrderBlocked" class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium" :class="isOrderBlocked ? 'text-red-800' : 'text-orange-800'">
                            {{ isOrderBlocked ? 'Client Blocat Financiar' : 'Atenționare Financiară' }}
                        </h3>
                        <div class="mt-2 text-sm" :class="isOrderBlocked ? 'text-red-700' : 'text-orange-700'">
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(msg, idx) in financialRisk.messages" :key="idx">{{ msg }}</li>
                            </ul>
                        </div>
                        
                        <!-- Director Acknowledgement -->
                        <div v-if="showDirectorAck && !isOrderBlocked" class="mt-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="directorAckRisk" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-xs uppercase">Am luat la cunoștință și aprob comanda</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
          </div>

          <div class="p-4 border-t border-gray-100 bg-gray-50 flex gap-3">
            <button @click="showPreview = false" class="px-6 py-3 rounded-xl border border-gray-300 font-bold text-gray-600 hover:bg-gray-100 transition-colors">Înapoi</button>
            <button 
                @click="submitOrder" 
                :disabled="loadingSubmit || isOrderBlocked || (showDirectorAck && !directorAckRisk)"
                class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-600/20 hover:bg-blue-700 hover:shadow-blue-600/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
            >
                <svg v-if="loadingSubmit" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ requiresApproval ? 'Trimite spre Aprobare' : 'Trimite Comanda' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
    </Teleport>

    <!-- PRODUCT DETAILS SHEET (Reused logic, updated UI) -->
    <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-10" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-10">
      <div v-if="productSheet.show" class="fixed inset-0 z-[9999999] flex items-end sm:items-center sm:justify-center" style="z-index: 9999999 !important;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="productSheet.show = false"></div>
        <div class="bg-white w-full sm:w-[500px] sm:rounded-2xl rounded-t-2xl shadow-2xl transform transition-all max-h-[85vh] overflow-hidden flex flex-col z-10">
          <div class="p-4 border-b flex justify-between items-start bg-gray-50">
            <div>
              <div class="text-xs text-blue-600 font-bold uppercase tracking-wide mb-1">Editare Produs</div>
              <div class="font-bold text-gray-900 text-lg leading-tight">{{ productSheet.product?.name }}</div>
              <div class="text-xs text-gray-400 mt-1">Cod: {{ productSheet.product?.code }}</div>
            </div>
            <button @click="productSheet.show = false" class="p-1 rounded-full hover:bg-gray-200 text-gray-500"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
          </div>
          
          <div class="px-4 border-b border-gray-100">
            <div class="flex gap-6 text-sm font-bold text-gray-400">
              <button @click="productSheet.tab='general'" class="py-3 border-b-2 transition-colors" :class="productSheet.tab==='general' ? 'text-blue-600 border-blue-600' : 'border-transparent hover:text-gray-600'">General</button>
              <button @click="productSheet.tab='prices'" class="py-3 border-b-2 transition-colors" :class="productSheet.tab==='prices' ? 'text-blue-600 border-blue-600' : 'border-transparent hover:text-gray-600'">Prețuri</button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto">
            <div v-show="productSheet.tab==='general'" class="space-y-6">
              <!-- Units -->
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase mb-2 block">Unitate de măsură</label>
                <div class="flex gap-2">
                  <button v-for="u in getProductUnits(productSheet.product)" :key="u.unit" @click="setItemUnit(productSheet.product, u.unit)" class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all" :class="getItemUnit(productSheet.product) === u.unit ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                    {{ u.unit }} <span v-if="u.conversion_factor > 1" class="text-[10px] opacity-75">(x{{ u.conversion_factor }})</span>
                  </button>
                </div>
              </div>

              <!-- Discount -->
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase mb-2 block">Discount Comercial</label>
                <div v-if="selectedCustomer && (selectedCustomer.allow_line_discount || canOverride)" class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="flex items-center gap-3 mb-2">
                        <input 
                            type="number" 
                            min="0" 
                            :max="discountRules.max_discount" 
                            :value="getItemDiscountPercent(productSheet.product)"
                            @input="(e) => setItemDiscountPercent(productSheet.product, e.target.value)"
                            class="w-20 text-center font-bold text-lg border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                        <span class="font-bold text-gray-600">%</span>
                    </div>
                    <div class="text-xs text-gray-400">
                        Max: {{ discountRules.max_discount }}%
                        <span v-if="discountRules.approval_threshold < discountRules.max_discount" class="text-orange-500 ml-1">
                            (Peste {{ discountRules.approval_threshold }}% necesită aprobare)
                        </span>
                    </div>
                </div>
                <div v-else class="text-gray-500 italic text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                    Acest client nu are permisiunea pentru discounturi de linie.
                </div>
              </div>
            </div>

            <div v-show="productSheet.tab==='prices'" class="space-y-4">
               <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 space-y-2">
                  <div class="flex justify-between text-sm">
                      <span class="text-gray-500">Preț Listă</span>
                      <span class="font-mono">{{ formatPrice(productSheet.product?.list_price || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                      <span class="text-gray-500">Ultimul Preț</span>
                      <span class="font-mono font-bold text-blue-600">{{ formatPrice(productSheet.product?.last_price || 0) }}</span>
                  </div>
               </div>
            </div>
          </div>

          <div class="p-4 border-t border-gray-100 bg-gray-50">
            <button @click="productSheet.show = false" class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-all">Salvează</button>
          </div>
        </div>
      </div>
    </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import _ from 'lodash';
import api, { adminApi } from '@/services/http';
import CustomerSelector from '@/components/admin/CustomerSelector.vue';
import { useVisitStore } from '@/store/visit';
import { useTrackingStore } from '@/store/tracking';
import { useAuthStore } from '@/store/auth';
import { usePrice } from '@/composables/usePrice';

const router = useRouter();
const route = useRoute();
const { formatPrice } = usePrice();
const authStore = useAuthStore();
const visitStore = useVisitStore();
const trackingStore = useTrackingStore();

// --- STATE ---
const isFullscreen = ref(true);
const products = ref([]);
const loadingProducts = ref(false);
const loadingMore = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const searchQuery = ref('');
const activeTab = ref('products'); // 'products', 'cart', 'info'
const showFilters = ref(false);
const showPreview = ref(false);
const showPromotionsPanel = ref(false);
const activePromotions = ref([]);
const promoSearchQuery = ref('');
const loadingPromotions = ref(false);

const filteredPromotions = computed(() => {
    if (!promoSearchQuery.value) return activePromotions.value;
    
    const query = promoSearchQuery.value.toLowerCase();
    
    return activePromotions.value.map(promo => {
        const p = { ...promo };
        const promoMatches = (p.name && p.name.toLowerCase().includes(query)) || 
                             (p.description && p.description.toLowerCase().includes(query));
                             
        if (p.products && p.products.length) {
            const matchingProducts = p.products.filter(prod => {
                return (prod.name && prod.name.toLowerCase().includes(query)) ||
                       (prod.sku && prod.sku.toLowerCase().includes(query)) ||
                       (prod.code && prod.code.toLowerCase().includes(query));
            });
            
            if (!promoMatches) {
                p.products = matchingProducts;
            }
        }
        
        if (promoMatches) return p;
        if (p.products && p.products.length > 0) return p;
        
        return null;
    }).filter(p => p !== null);
});

const sortOptions = [
    { label: 'Relevanță', value: 'relevance' },
    { label: 'Preț (Crescător)', value: 'price_asc' },
    { label: 'Preț (Descrescător)', value: 'price_desc' },
    { label: 'Nume (A-Z)', value: 'name_asc' },
];
const selectedSort = ref('relevance');

const categories = ref([]);
const selectedCategory = ref(null);
const selectedSubcategory = ref(null);

const items = ref([]); // Cart Items
const selectedCustomer = ref(null);
const financialRisk = ref(null);
const directorAckRisk = ref(false);

const discountRules = reactive({
    max_discount: 20,
    approval_threshold: 5,
    apply_to_total: true
});
const canOverride = ref(false);

const orderDetails = reactive({
    deliveryDate: new Date().toISOString().split('T')[0],
    paymentMethod: 'La termen',
    paymentDocument: '',
    internalNote: ''
});

const productSheet = reactive({
    show: false,
    product: null,
    tab: 'general'
});

// --- COMPUTED ---
const isCustomer = computed(() => authStore.hasRole(['customer_b2b', 'customer_b2c']));
const isAgentOrDirector = computed(() => authStore.hasRole(['sales_agent', 'sales_director']));
const isDirector = computed(() => authStore.hasRole(['sales_director', 'admin', 'owner']));

// Risk Computed
const isOrderBlocked = computed(() => {
    return financialRisk.value?.status === 'blocked';
});

const requiresApproval = computed(() => {
    if (isDirector.value) return false;
    return financialRisk.value?.status === 'approval_required';
});

const showDirectorAck = computed(() => {
    if (!isDirector.value) return false;
    return ['warning', 'approval_required'].includes(financialRisk.value?.status);
});

// Blocking Overlays
const showStartProgramOverlay = computed(() => {
    if (trackingStore.loading) return false;
    return isAgentOrDirector.value && !trackingStore.isShiftActive;
});

const showStartVisitOverlay = computed(() => {
    return isAgentOrDirector.value && trackingStore.isShiftActive && !visitStore.hasActiveVisit;
});

const rootCategories = computed(() => categories.value.filter(c => !c.parent_id));

const cartSubtotal = computed(() => {
    return items.value.reduce((acc, item) => acc + (effectiveUnitPrice(item) * item.quantity), 0);
});

const cartDiscount = computed(() => {
    return items.value.reduce((acc, item) => {
        const originalTotal = item.unit_price * item.quantity;
        const finalTotal = effectiveLineTotal(item);
        return acc + (originalTotal - finalTotal);
    }, 0);
});

const totals = ref({
    subtotal: 0,
    vat: 0,
    total: 0
});

// Watch for cart changes to update local totals (estimation) until backend calculation
watch(cartSubtotal, (newSubtotal) => {
    // Only update if we haven't received backend values yet or just for estimation
    // But better to rely on calculateTotals.
    // However, if we want instant feedback:
    if (newSubtotal > 0 && totals.value.subtotal !== newSubtotal) {
         totals.value.subtotal = newSubtotal;
         totals.value.vat = newSubtotal * 0.19;
         totals.value.total = newSubtotal * 1.19;
    }
});

const finalTotal = computed(() => totals.value.total);

const hasMorePages = computed(() => currentPage.value < lastPage.value);

// --- METHODS ---

// Start Program
const startProgram = async () => {
    try {
        await trackingStore.startDay();
        // Force refresh visit status too just in case
        await visitStore.checkActiveVisit();
    } catch (e) {
        console.error('Failed to start program', e);
        // Show error notification
        alert('Eroare la pornirea programului: ' + (e.response?.data?.message || e.message));
    }
};

// Start Visit Logic
const startVisitWithCustomer = async (customer) => {
    if (!customer) return;
    try {
        await visitStore.startVisit(customer.id);
        selectedCustomer.value = customer;
        // After visit starts, fetch data
        await fetchCustomerData(customer.id);
        await fetchPromotions(customer.id);
    } catch (e) {
        console.error('Failed to start visit', e);
        alert('Nu am putut începe vizita. Verifică conexiunea sau încearcă din nou.');
    }
};

const getSubcategories = (parentId) => {
    return categories.value.filter(c => c.parent_id === parentId);
};

const onSearchInput = _.debounce(() => {
    currentPage.value = 1;
    fetchProducts();
}, 500);

const onCategoryChange = () => {
    currentPage.value = 1;
    selectedSubcategory.value = null;
    fetchProducts();
};

const fetchProducts = async () => {
    if (currentPage.value === 1) loadingProducts.value = true;
    else loadingMore.value = true;

    try {
        const params = {
            page: currentPage.value,
            search: searchQuery.value,
            sort: selectedSort.value,
            category_id: selectedSubcategory.value?.id || selectedCategory.value?.id,
            per_page: 24,
            customer_id: selectedCustomer.value?.id
        };

        const { data } = await api.get('/quick-order/search', { params });
        
        // Initialize cart_quantity for UI stability
        const mappedProducts = data.data.map(p => {
            p.cart_quantity = getCartQuantity(p.id);
            return p;
        });
        
        if (currentPage.value === 1) {
            products.value = mappedProducts;
        } else {
            products.value = [...products.value, ...mappedProducts];
        }
        lastPage.value = data.last_page;
    } catch (e) {
        console.error(e);
    } finally {
        loadingProducts.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    if (currentPage.value < lastPage.value) {
        currentPage.value++;
        fetchProducts();
    }
};

const fetchCategories = async () => {
    try {
        const { data } = await api.get('/categories');
        categories.value = data;
    } catch (e) {
        console.error(e);
    }
};

// Customer & Data Loading
const selectCustomer = async (customer) => {
    selectedCustomer.value = customer;
    items.value = []; // Reset cart on customer change
    await fetchCustomerData(customer.id);
    await fetchPromotions(customer.id);
};

const fetchCustomerData = async (customerId) => {
    try {
        const { data } = await adminApi.get('/quick-order/checkout-data', { params: { customer_id: customerId } });
        canOverride.value = data.can_override;
        discountRules.max_discount = data.discount_rules.max_discount;
        discountRules.approval_threshold = data.discount_rules.approval_threshold;
        financialRisk.value = data.financial_risk;
        directorAckRisk.value = false; // Reset ack
    } catch (e) {
        console.error(e);
    }
};

const fetchPromotions = async (customerId) => {
    loadingPromotions.value = true;
    try {
        const { data } = await adminApi.get(`/customers/${customerId}/promotions`);
        const promotions = data.data || [];
        
        // Initialize selected_quantity for each product in each promotion
        promotions.forEach(promo => {
            if (promo.products) {
                promo.products.forEach(prod => {
                    prod.selected_quantity = promo.min_qty || 1;
                });
            }
        });
        
        activePromotions.value = promotions;
        
        // If we have promotions, maybe notify user
        if (activePromotions.value.length > 0 && !showPromotionsPanel.value) {
            // Optional: Auto open or show badge
        }
    } catch (e) {
        console.error('Failed to fetch promotions', e);
    } finally {
        loadingPromotions.value = false;
    }
};

// Cart Logic
const getCartQuantity = (productId) => {
    const item = items.value.find(i => i.product_id === productId);
    return item ? item.quantity : 0;
};

const setCartQuantity = (product, qty) => {
    if (qty < 0) qty = 0;
    
    const index = items.value.findIndex(i => i.product_id === product.id);
    
    if (index === -1 && qty > 0) {
        items.value.push({
            product_id: product.id,
            product_name: product.name,
            sku: product.code,
            quantity: qty,
            unit: product.selected_unit?.unit || product.unit_of_measure || 'buc', // Use selected unit
            unit_price: product.price, // Initial guess
            price_override: null,
            discount_override: null,
            discount_percent: 0
        });
    } else if (index !== -1) {
        if (qty === 0) {
            items.value.splice(index, 1);
        } else {
            items.value[index].quantity = qty;
        }
    }
    
    debouncedCalculate();
};

const updateProductQuantity = (product, delta) => {
    const current = getCartQuantity(product.id);
    setCartQuantity(product, current + delta);
};

const updateQuantity = (index, delta) => {
    const item = items.value[index];
    if (!item) return;
    
    let newQty = item.quantity + delta;
    if (newQty <= 0) {
        items.value.splice(index, 1);
    } else {
        item.quantity = newQty;
    }
    debouncedCalculate();
};

const addPromoProductToCart = (product) => {
    // Add selected units of the promo product (initial add)
    const qty = product.selected_quantity || 1;
    updateProductQuantity(product, qty);
};

const addPromotionToCart = (promo) => {
    const multiplier = promo.selected_quantity || 1;
    if (promo.products) {
        let addedCount = 0;
        promo.products.forEach(prod => {
            // Add 'multiplier' units of each product to the cart
            // If the product is already in cart, this adds TO it.
            if (multiplier > 0) {
                updateProductQuantity(prod, multiplier);
                addedCount++;
            }
        });
        
        if (addedCount > 0) {
            // Optional: Notification could be added here
            console.log(`Added promotion ${promo.name} with multiplier ${multiplier}`);
        }
    }
};

const updatePromoQtyDirectly = (prod, promo, val) => {
    if (!promo.is_iterative) return;
    let qty = parseInt(val);
    if (isNaN(qty)) qty = 0;
    setCartQuantity(prod, qty);
};

const increasePromoQty = (prod, promo) => {
    if (promo.is_iterative) {
        updateProductQuantity(prod, 1);
    }
};

const decreasePromoQty = (prod, promo) => {
    if (promo.is_iterative) {
        // If current qty is already min or 1, and we decrease, it might go to 0 or min-1
        // But users can remove from cart using the main cart controls too.
        // Here we just subtract 1.
        updateProductQuantity(prod, -1);
    }
};

const openProductDetails = (product) => {
    console.log('Opening product details for:', product.name);
    productSheet.product = product;
    productSheet.show = true;
};

const toggleItemUnit = (product) => {
    // 1. Determine available units
    // If no units defined, fallback to base unit
    const units = product.units && product.units.length > 0 
        ? product.units 
        : [{ unit: product.unit_of_measure || 'buc', conversion_factor: 1 }];

    // 2. Determine current unit
    const item = items.value.find(i => i.product_id === product.id);
    const currentUnitStr = item ? item.unit : (product.selected_unit?.unit || product.unit_of_measure || 'buc');
    
    // Find index
    const currentIndex = units.findIndex(u => u.unit === currentUnitStr);
    
    // Calculate next index
    let nextIndex = currentIndex + 1;
    if (nextIndex >= units.length || currentIndex === -1) {
        nextIndex = 0;
    }
    
    const nextUnit = units[nextIndex];
    
    // 3. Apply change
    if (item) {
        // If in cart, update cart item
        item.unit = nextUnit.unit;
        // Optionally update price if unit-specific prices exist, but for now we keep it simple
        // Trigger recalculation
        debouncedCalculate();
    }
    
    // Always update local product state (for display if not in cart, or for next add)
    // We use reactivity on product object
    product.selected_unit = nextUnit;
};

const getProductUnits = (product) => {
    if (!product) return [];
    if (product.units && product.units.length > 0) {
        return product.units;
    }
    return [{ unit: product.unit_of_measure || 'buc', conversion_factor: 1 }];
};

const setItemUnit = (product, unitName) => {
    // Find in cart
    const item = items.value.find(i => i.product_id === product.id);
    if (item) {
        item.unit = unitName;
        debouncedCalculate();
    }
    
    // Update local product state
    const units = getProductUnits(product);
    const unitObj = units.find(u => u.unit === unitName);
    if (unitObj) {
        product.selected_unit = unitObj;
    }
};

const getItemUnit = (product) => {
    const item = items.value.find(i => i.product_id === product.id);
    return item ? item.unit : (product.selected_unit?.unit || product.unit_of_measure || 'buc');
};

const getItemDiscountPercent = (product) => {
    const item = items.value.find(i => i.product_id === product.id);
    return item?.discount_override || 0;
};

const setItemDiscountPercent = (product, val) => {
    const item = items.value.find(i => i.product_id === product.id);
    if (item) {
        item.discount_override = parseFloat(val) || 0;
        debouncedCalculate();
    }
};

const effectiveUnitPrice = (item) => {
    return item.final_unit_price || item.unit_price;
};

const effectiveLineTotal = (item) => {
    return item.line_total || (effectiveUnitPrice(item) * item.quantity);
};

// Calculation
const calculateTotals = async () => {
    if (items.value.length === 0 || !selectedCustomer.value) return;
    
    try {
        const payload = {
            customer_id: selectedCustomer.value.id,
            items: items.value.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity,
                price_override: i.price_override,
                discount_override: i.discount_override
            }))
        };
        
        const { data } = await adminApi.post('/quick-order/calculate', payload);
        
        // Update items with calculated values
        items.value = items.value.map(item => {
            const calculated = data.items.find(ci => ci.product_id === item.product_id);
            if (calculated) {
                return { ...item, ...calculated };
            }
            return item;
        });
        
        totals.value.subtotal = data.subtotal;
        totals.value.vat = data.total - data.subtotal; // Approximation if tax not returned explicitly
        totals.value.total = data.total;
        
    } catch (e) {
        console.error('Calculation error', e);
    }
};

const debouncedCalculate = _.debounce(calculateTotals, 500);

const loadingSubmit = ref(false);
const submitOrder = async () => {
    if (!selectedCustomer.value || items.value.length === 0) return;
    
    loadingSubmit.value = true;
    try {
        const payload = {
            customer_id: selectedCustomer.value.id,
            customer_visit_id: visitStore.activeVisit?.id, // Link to visit
            items: items.value,
            ...orderDetails
        };
        
        const { data } = await adminApi.post('/quick-order/create', payload);
        
        // Success
        alert(`Comanda ${data.order_number} a fost creată cu succes!`);
        items.value = [];
        showPreview.value = false;
        
        // If visit active, maybe we don't clear customer, just cart
        
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Eroare la crearea comenzii');
    } finally {
        loadingSubmit.value = false;
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    selectedSubcategory.value = null;
    selectedSort.value = 'relevance';
    fetchProducts();
};

// Lifecycle
onMounted(async () => {
    fetchCategories();
    fetchProducts();
    
    // Initial check
    checkAgentStatus();
});

const checkAgentStatus = async () => {
    if (isAgentOrDirector.value) {
        console.log('Checking agent status...');
        await trackingStore.checkStatus();
        await visitStore.checkActiveVisit();
        
        if (visitStore.hasActiveVisit && visitStore.activeVisit.customer) {
            // Auto-select customer from visit
            selectedCustomer.value = visitStore.activeVisit.customer;
            await fetchCustomerData(selectedCustomer.value.id);
            await fetchPromotions(selectedCustomer.value.id);
        }
    }
};

// Watch for role changes (e.g. after profile load)
watch(isAgentOrDirector, (newVal) => {
    if (newVal) {
        checkAgentStatus();
    }
});

// Watchers
watch(() => visitStore.activeVisit, (newVisit) => {
    if (newVisit && newVisit.customer) {
        selectedCustomer.value = newVisit.customer;
        fetchCustomerData(newVisit.customer.id);
        fetchPromotions(newVisit.customer.id);
    }
});

// Sync items to products cart_quantity
watch(() => items.value, (newItems) => {
    const qtyMap = new Map(newItems.map(i => [i.product_id, i.quantity]));
    
    products.value.forEach(p => {
         const qty = qtyMap.get(p.id) || 0;
         if (p.cart_quantity !== qty) {
             p.cart_quantity = qty;
         }
    });
}, { deep: true });

</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Animations */
@keyframes slide-in-right {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-slide-in-right {
    animation: slide-in-right 0.3s ease-out forwards;
}

@keyframes bounce-in {
    0% { transform: scale(0.9); opacity: 0; }
    50% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-bounce-in {
    animation: bounce-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
</style>
