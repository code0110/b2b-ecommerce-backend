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

    <!-- HEADER (Material Design Blue) -->
    <header class="bg-[#0060aa] text-white shadow-md z-30 flex-shrink-0 flex flex-col">
        <!-- Top Bar -->
        <div class="flex items-center justify-between px-4 h-14">
            <div class="flex items-center gap-3 overflow-hidden">
                <button @click="router.back()" class="p-1 -ml-2 rounded-full hover:bg-[#004d88] transition-colors">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="min-w-0 flex flex-col">
                    <h1 class="text-lg font-bold leading-none truncate">Comandă nouă</h1>
                    <p class="text-xs text-white opacity-90 truncate font-medium mt-0.5">{{ selectedCustomer ? selectedCustomer.name : 'Selectează Client' }}</p>
                </div>
            </div>
            
            <!-- Header Actions -->
            <div class="flex items-center gap-1">
                 <button v-if="activeTab === 'products'" @click="showFilters = true" class="p-2 rounded-full hover:bg-[#004d88] transition-colors relative">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span v-if="activeFiltersCount > 0" class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border border-[#0060aa]"></span>
                 </button>
                 
                 <button @click="submitOrder" :disabled="items.length === 0 || loadingSubmit" class="p-2 rounded-full hover:bg-[#004d88] transition-colors disabled:opacity-50">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                 </button>
                 
                 <button @click="showPreview = true" class="p-2 rounded-full hover:bg-[#004d88] transition-colors" title="Previzualizare comandă">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                 </button>
            </div>
        </div>
        
        <!-- Material Tabs -->
        <div class="flex text-sm font-bold uppercase tracking-wide overflow-x-auto scrollbar-hide">
            <button @click="activeTab = 'antet'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'antet' ? 'border-white text-white' : 'border-transparent text-white opacity-70 hover:text-white'">
                ANTET
            </button>
            <button @click="activeTab = 'products'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'products' ? 'border-white text-white' : 'border-transparent text-white opacity-70 hover:text-white'">
                PRODUSE
            </button>
            <button @click="activeTab = 'promotions'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'promotions' ? 'border-white text-white' : 'border-transparent text-white opacity-70 hover:text-white'">
                PROMOȚII
            </button>
            <button @click="activeTab = 'cart'" class="flex-1 min-w-[80px] py-3 text-center border-b-2 transition-colors relative" :class="activeTab === 'cart' ? 'border-white text-white' : 'border-transparent text-white opacity-70 hover:text-white'">
                COȘ <span v-if="items.length > 0">({{ items.length }})</span>
            </button>
        </div>
    </header>

    <!-- MAIN LAYOUT -->
    <div class="flex-1 flex overflow-hidden relative">

      <!-- MAIN CONTENT AREA -->
      <main class="flex-1 flex flex-col bg-gray-50 min-w-0 relative">
        
        <!-- TAB 1: ANTET (Header Info) -->
        <div v-show="activeTab === 'antet'" class="pb-20 overflow-y-auto h-full">
            <div v-if="!selectedCustomer" class="p-4 bg-white m-4 rounded-lg shadow-sm space-y-3">
                <div v-if="isSalesAgentOrDirector && !isCustomer" class="p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
                    <div class="font-bold text-sm mb-1">Nu există vizită activă</div>
                    <div class="text-xs mb-2">Dacă ai o vizită programată, începe vizita pentru a prelua automat clientul.</div>
                    <button @click="goToPlanning" class="text-xs bg-yellow-100 px-2 py-1 rounded border border-yellow-300 hover:bg-yellow-200 font-medium">
                        Mergi la Planificare
                    </button>
                </div>
                
                <div v-if="!isCustomer">
                    <CustomerSelector @select="selectCustomer" />
                </div>

                <div v-if="isCustomer" class="text-center py-8">
                    <div class="w-8 h-8 border-4 border-[#ff7900] border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                    <p class="text-xs text-gray-500 font-medium">Se încarcă datele clientului...</p>
                </div>
            </div>
            
            <div v-else class="space-y-0.5 animate-fade-in">
                <!-- Client Details Card -->
                <div class="bg-white p-4 mb-4 shadow-sm border-l-4 border-[#0060aa]">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ selectedCustomer.name }}</h3>
                            <p class="text-sm text-gray-500 font-mono">{{ selectedCustomer.cif || selectedCustomer.fiscal_code || 'CUI: -' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ selectedCustomer.reg_com || '-' }}</p>
                        </div>
                        <div class="text-right">
                             <span class="inline-block bg-[#e6f0fa] text-[#0060aa] text-xs px-2 py-1 rounded font-bold mb-1">
                                {{ selectedCustomer.currency || 'RON' }}
                             </span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between text-xs">
                        <div>
                            <span class="text-gray-400 block">Agent Asignat</span>
                            <span class="font-bold text-gray-700">{{ selectedCustomer.agent_name || (selectedCustomer.agent ? (selectedCustomer.agent.first_name + ' ' + selectedCustomer.agent.last_name) : 'Nespecificat') }}</span>
                        </div>

                        <!-- Title & Meta -->
                        <div class="mb-3 lg:mb-0 lg:flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-1 lg:mb-0.5">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Risk Alert -->
                    <div v-if="financialRisk && financialRisk.status !== 'ok'" class="mt-4 p-3 rounded-lg border flex items-start" :class="isOrderBlocked ? 'bg-red-50 border-red-200' : 'bg-orange-50 border-orange-200'">
                        <div class="shrink-0 mr-3 mt-0.5">
                            <svg v-if="isOrderBlocked" class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1">
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

                <!-- Order Details Form -->
                <div class="bg-white p-4 shadow-sm border-t border-gray-100">
                    <h4 class="font-bold text-gray-800 mb-4">Detalii Livrare & Plată</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Livrare</label>
                            <input type="date" v-model="orderDetails.deliveryDate" class="w-full bg-gray-50 border border-gray-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Metodă Plată</label>
                            <select v-model="orderDetails.paymentMethod" class="w-full bg-gray-50 border border-gray-200 rounded-lg text-sm">
                                <option value="La termen">La termen (OP)</option>
                                <option value="Numerar">Numerar</option>
                                <option value="Card">Card</option>
                                <option value="CEC">CEC</option>
                                <option value="BO">Bilet la Ordin</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Notă Internă</label>
                            <textarea v-model="orderDetails.internalNote" rows="2" class="w-full bg-gray-50 border border-gray-200 rounded-lg text-sm" placeholder="Observații pentru depozit/contabilitate..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: PRODUSE (Product Grid) -->
        <div v-show="activeTab === 'products'" class="flex flex-col h-full">
            <!-- Mobile Sticky Header (Search) -->
            <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm">
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
                            
                            <div class="flex-1 min-w-0 cursor-pointer" @click="openProductDetails(product)">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ product.sku || product.code || '-' }}</span>
                                    <span v-if="product.stock > 0" class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">STOC</span>
                                    <span v-else class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-bold">LIPSA</span>
                                </div>
                                <h3 class="font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition-colors line-clamp-2">{{ product.name }}</h3>
                            </div>

                            <!-- Mobile Image (Right) -->
                            <div class="lg:hidden absolute top-3 right-3 w-10 h-10 bg-white rounded border border-gray-100 p-1">
                                <img v-if="product.image_url" :src="product.image_url" class="w-full h-full object-contain">
                            </div>

                            <!-- Controls -->
                            <div class="mt-3 lg:mt-0 flex items-center justify-between lg:justify-end gap-4">
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">{{ formatPrice(product.price) }}</div>
                                    <div class="text-[10px] text-gray-400">fără TVA</div>
                                </div>

                                <div class="flex items-center bg-gray-100 rounded-lg p-0.5 h-9">
                                    <button 
                                        class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-blue-600 hover:bg-white rounded-md transition-all font-bold disabled:opacity-50"
                                        @click="updateProductQuantity(product, -1)"
                                        :disabled="getCartQuantity(product.id) === 0"
                                    >−</button>
                                    <input 
                                        type="number" 
                                        :value="getCartQuantity(product.id)" 
                                        @change="(e) => setCartQuantity(product, parseInt(e.target.value))"
                                        class="w-10 h-full bg-transparent border-none text-center text-sm font-bold text-gray-900 focus:ring-0 p-0 appearance-none"
                                    >
                                    <button 
                                        class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-blue-600 hover:bg-white rounded-md transition-all font-bold"
                                        @click="updateProductQuantity(product, 1)"
                                    >+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Load More -->
                    <div v-if="hasMorePages" class="p-4 text-center">
                        <button 
                            @click="loadMore" 
                            :disabled="loadingMore"
                            class="px-6 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-sm font-bold shadow-sm hover:bg-gray-50 transition-all disabled:opacity-50"
                        >
                            {{ loadingMore ? 'Se încarcă...' : 'Vezi mai multe produse' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: PROMOȚII (Promotions Grid) -->
        <div v-show="activeTab === 'promotions'" class="flex flex-col h-full bg-gray-50 overflow-y-auto custom-scrollbar">
            <!-- Promotions Search Header -->
            <div class="bg-purple-50 p-4 border-b border-purple-100 sticky top-0 z-10">
                <div class="relative max-w-2xl mx-auto">
                    <input 
                        v-model="promoSearchQuery"
                        type="text" 
                        placeholder="Caută în promoții (nume, produse)..." 
                        class="w-full pl-10 pr-4 py-3 bg-white border-purple-100 rounded-xl shadow-sm text-purple-900 placeholder-purple-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all"
                    >
                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <div class="p-4 grid gap-6 max-w-4xl mx-auto w-full pb-24">
                <div v-if="loadingPromotions" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                </div>
                
                <div v-else-if="filteredPromotions.length === 0" class="text-center py-12 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                    <p class="font-medium">Nu există promoții active</p>
                </div>

                <div v-else v-for="promo in filteredPromotions" :key="promo.id" class="bg-white rounded-xl shadow-md overflow-hidden border border-purple-100 transition-all hover:shadow-lg">
                    <!-- Promotion Header with Add to Cart -->
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
                                <!-- Quantity Controls -->
                                <div class="flex items-center bg-white rounded-lg border border-purple-200 h-9">
                                    <button @click="promo.selected_quantity = Math.max(1, (promo.selected_quantity || 1) - 1)" class="w-8 h-full flex items-center justify-center text-purple-500 hover:text-purple-700 font-bold">-</button>
                                    <input type="number" v-model="promo.selected_quantity" class="w-12 text-center bg-transparent border-none text-sm font-bold text-purple-900 focus:ring-0 p-0" min="1">
                                    <button @click="promo.selected_quantity = (promo.selected_quantity || 1) + 1" class="w-8 h-full flex items-center justify-center text-purple-500 hover:text-purple-700 font-bold">+</button>
                                </div>
                                <!-- Add Button -->
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

                    <!-- Products List in Promotion -->
                    <div class="divide-y divide-gray-50">
                         <div v-for="prod in promo.products" :key="prod.id" class="p-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                            <div class="w-10 h-10 bg-white rounded border border-gray-100 flex items-center justify-center shrink-0">
                                <img v-if="prod.image_path" :src="prod.image_path" class="w-8 h-8 object-contain">
                                <svg v-else class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="font-bold text-gray-800 text-sm truncate">{{ prod.name }}</p>
                                    <div class="text-right ml-2">
                                        <div class="font-bold text-purple-700">{{ formatPrice(prod.promo_price) }}</div>
                                        <div class="text-[10px] text-gray-400 line-through">{{ formatPrice(prod.base_price) }}</div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end mt-1">
                                    <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-bold">
                                        -{{ prod.discount_percent }}%
                                    </span>
                                    
                                    <!-- Individual Product Control in Promo (Iterative) -->
                                    <div v-if="promo.is_iterative" class="flex items-center gap-2">
                                        <button @click="decreasePromoQty(prod, promo)" class="text-gray-400 hover:text-red-500 font-bold px-1">-</button>
                                        <span class="text-sm font-bold w-6 text-center">{{ getCartQuantity(prod.id) }}</span>
                                        <button @click="increasePromoQty(prod, promo)" class="text-gray-400 hover:text-green-500 font-bold px-1">+</button>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">
                                        Include {{ promo.min_qty }} x
                                    </div>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: COS (Cart Summary) -->
        <div v-show="activeTab === 'cart'" class="flex flex-col h-full bg-white">
            <div v-if="items.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                <p>Coșul este gol</p>
                <button @click="activeTab = 'products'" class="mt-4 text-blue-600 font-bold hover:underline">Adaugă Produse</button>
            </div>
            
            <div v-else class="flex-1 flex flex-col">
                 <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-3">
                     <div v-for="(item, index) in items" :key="item.product_id" class="flex gap-3 bg-white p-3 rounded-xl border border-gray-100 shadow-sm relative group">
                        <!-- Remove Button -->
                        <button @click="updateQuantity(index, -item.quantity)" class="absolute -top-2 -right-2 bg-white text-red-500 rounded-full p-1 shadow border border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>

                        <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center shrink-0">
                            <span class="font-bold text-gray-400 text-xs">{{ index + 1 }}</span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-gray-800 text-sm truncate pr-4">{{ item.product_name }}</h4>
                            <div class="flex justify-between items-end mt-1">
                                <div class="flex items-center gap-2">
                                     <div class="flex items-center bg-gray-100 rounded-lg h-7">
                                        <button @click="updateQuantity(index, -1)" class="px-2 text-gray-500 hover:text-blue-600 font-bold">-</button>
                                        <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                        <button @click="updateQuantity(index, 1)" class="px-2 text-gray-500 hover:text-blue-600 font-bold">+</button>
                                     </div>
                                     <span class="text-xs text-gray-500">{{ item.unit }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">{{ formatPrice(effectiveLineTotal(item)) }}</div>
                                    <div class="text-[10px] text-gray-400">{{ formatPrice(effectiveUnitPrice(item)) }} / buc</div>
                                </div>
                            </div>
                        </div>
                     </div>
                 </div>

                 <!-- Cart Footer -->
                 <div class="p-4 bg-gray-50 border-t border-gray-200">
                     <div class="space-y-2 mb-4">
                         <div class="flex justify-between text-sm">
                             <span class="text-gray-500">Subtotal</span>
                             <span class="font-bold text-gray-900">{{ formatPrice(totals.subtotal) }}</span>
                         </div>
                         <div class="flex justify-between text-sm">
                             <span class="text-gray-500">TVA (19%)</span>
                             <span class="font-bold text-gray-900">{{ formatPrice(totals.vat) }}</span>
                         </div>
                         <div class="flex justify-between text-lg pt-2 border-t border-gray-200">
                             <span class="font-bold text-gray-900">Total</span>
                             <span class="font-bold text-blue-600">{{ formatPrice(totals.total) }}</span>
                         </div>
                     </div>
                     
                     <button 
                        @click="submitOrder" 
                        :disabled="loadingSubmit || isOrderBlocked || (showDirectorAck && !directorAckRisk)"
                        class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loadingSubmit" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        {{ requiresApproval ? 'Trimite spre Aprobare' : 'Trimite Comanda' }}
                     </button>
                 </div>
            </div>
        </div>

      </main>
    </div>

    <!-- PREVIEW MODAL -->
    <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
      <div v-if="showPreview" class="fixed inset-0 z-[999999] flex items-center justify-center p-4" style="z-index: 999999 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreview = false"></div>
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl relative z-10 flex flex-col max-h-[90vh]">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Sumar Comandă</h2>
            <p class="text-sm text-gray-500">{{ selectedCustomer?.name }}</p>
          </div>
          
          <div class="p-6 overflow-y-auto custom-scrollbar">
            <!-- Items Table -->
            <table class="w-full text-sm mb-6">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="py-2 px-3 text-left rounded-l-lg">Produs</th>
                        <th class="py-2 px-3 text-center">Cant.</th>
                        <th class="py-2 px-3 text-right rounded-r-lg">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in items" :key="item.product_id">
                        <td class="py-3 px-3">
                            <div class="font-bold text-gray-800">{{ item.product_name }}</div>
                            <div class="text-xs text-gray-400">{{ item.sku }}</div>
                        </td>
                        <td class="py-3 px-3 text-center font-bold">{{ item.quantity }} {{ item.unit }}</td>
                        <td class="py-3 px-3 text-right font-bold">{{ formatPrice(effectiveLineTotal(item)) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Financial Risk Info in Preview -->
            <div v-if="financialRisk && financialRisk.status !== 'ok'" class="bg-gray-50 rounded-xl p-4 border border-gray-200 mb-6">
                <div class="flex items-start">
                    <div class="shrink-0 mr-3">
                        <svg v-if="isOrderBlocked" class="h-6 w-6 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="h-6 w-6 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
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
const activeTab = ref('products'); // 'products', 'cart', 'info', 'promotions'
const showFilters = ref(false);
const showPreview = ref(false);
const activePromotions = ref([]);
const promoSearchQuery = ref('');
const loadingPromotions = ref(false);

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
const isImpersonating = computed(() => !!authStore.impersonatedCustomer || !!localStorage.getItem('impersonated_client_id'));

const isCustomer = computed(() => {
    if (!authStore.user) return false;
    
    // 1. Explicit checks for customer indicators
    if (authStore.user.customer_id || authStore.user.customer) return true;
    if (isImpersonating.value) return true;

    // Role checks
    const currentRole = String(authStore.role || '').toLowerCase();
    if (['customer', 'customer_b2b', 'customer_b2c', 'b2b', 'b2c'].includes(currentRole)) return true;
    if (currentRole.includes('customer')) return true;

    const roles = (authStore.user.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
    if (roles.some(r => r.includes('customer') || r === 'b2b' || r === 'b2c')) return true;

    // 2. Check for staff roles
    const hasStaffRole = roles.some(r => ['admin', 'sales_agent', 'sales_director', 'operator', 'manager'].includes(r));
    if (hasStaffRole) return false;

    // 3. Fallback
    return true;
});

const isSalesAgentOrDirector = computed(() => {
    if (!authStore.user) return false;
    if (isCustomer.value) return false;
    if (authStore.user.customer_id || authStore.user.customer) return false;
    if (isImpersonating.value) return false;

    const roles = (authStore.user.roles || []).map(r => (r.slug || r.code || '').toLowerCase());
    return roles.includes('sales_agent') || roles.includes('sales_director');
});

const isDirector = computed(() => authStore.hasRole(['sales_director', 'admin', 'owner']));

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
    return isSalesAgentOrDirector.value && !trackingStore.isShiftActive;
});

const showStartVisitOverlay = computed(() => {
    return isSalesAgentOrDirector.value && trackingStore.isShiftActive && !visitStore.hasActiveVisit;
});

const rootCategories = computed(() => categories.value.filter(c => !c.parent_id));

const cartSubtotal = computed(() => {
    return items.value.reduce((acc, item) => acc + (effectiveUnitPrice(item) * item.quantity), 0);
});

const totals = ref({
    subtotal: 0,
    vat: 0,
    total: 0
});

// Watch for cart changes to update local totals (estimation) until backend calculation
watch(cartSubtotal, (newSubtotal) => {
    if (newSubtotal > 0 && totals.value.subtotal !== newSubtotal) {
         totals.value.subtotal = newSubtotal;
         totals.value.vat = newSubtotal * 0.19;
         totals.value.total = newSubtotal * 1.19;
    }
});

const hasMorePages = computed(() => currentPage.value < lastPage.value);
const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedCategory.value) count++;
    if (selectedSubcategory.value) count++;
    if (selectedSort.value !== 'relevance') count++;
    return count;
});

// --- METHODS ---

// Start Program
const startProgram = async () => {
    try {
        await trackingStore.startDay();
        await visitStore.checkActiveVisit();
    } catch (e) {
        console.error('Failed to start program', e);
        alert('Eroare la pornirea programului: ' + (e.response?.data?.message || e.message));
    }
};

// Start Visit Logic
const startVisitWithCustomer = async (customer) => {
    if (!customer) return;
    try {
        await visitStore.startVisit(customer.id);
        selectedCustomer.value = customer;
        await fetchCustomerData(customer.id);
        await fetchPromotions(customer.id);
    } catch (e) {
        console.error('Failed to start visit', e);
        alert('Nu am putut începe vizita. Verifică conexiunea sau încearcă din nou.');
    }
};

const onSearchInput = _.debounce(() => {
    currentPage.value = 1;
    fetchProducts();
}, 500);

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
        
        promotions.forEach(promo => {
            promo.selected_quantity = promo.min_qty || 1;
            if (promo.products) {
                promo.products.forEach(prod => {
                    prod.selected_quantity = promo.min_qty || 1;
                });
            }
        });
        
        activePromotions.value = promotions;
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
            unit: product.selected_unit?.unit || product.unit_of_measure || 'buc',
            unit_price: product.price,
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

const addPromotionToCart = (promo) => {
    const multiplier = promo.selected_quantity || 1;
    if (promo.products) {
        let addedCount = 0;
        promo.products.forEach(prod => {
            // Use the promo price as base price if available
            // However, selectProduct logic usually takes base price.
            // But here we might want to ensure the promo price is respected?
            // Actually the backend calculates the price based on promotion.
            // We just add the product to cart.
            
            if (multiplier > 0) {
                updateProductQuantity(prod, multiplier);
                addedCount++;
            }
        });
        
        if (addedCount > 0) {
            // Switch to cart tab to show user
            // activeTab.value = 'cart'; 
            // Or just show notification
        }
    }
};

const increasePromoQty = (prod, promo) => {
    if (promo.is_iterative) {
        updateProductQuantity(prod, 1);
    }
};

const decreasePromoQty = (prod, promo) => {
    if (promo.is_iterative) {
        updateProductQuantity(prod, -1);
    }
};

const openProductDetails = (product) => {
    productSheet.product = product;
    productSheet.show = true;
};

const getProductUnits = (product) => {
    if (!product) return [];
    if (product.units && product.units.length > 0) {
        return product.units;
    }
    return [{ unit: product.unit_of_measure || 'buc', conversion_factor: 1 }];
};

const setItemUnit = (product, unitName) => {
    const item = items.value.find(i => i.product_id === product.id);
    if (item) {
        item.unit = unitName;
        debouncedCalculate();
    }
    
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
        
        items.value = items.value.map(item => {
            const calculated = data.items.find(ci => ci.product_id === item.product_id);
            if (calculated) {
                return { ...item, ...calculated };
            }
            return item;
        });
        
        totals.value.subtotal = data.subtotal;
        totals.value.vat = data.total - data.subtotal;
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
            customer_visit_id: visitStore.activeVisit?.id,
            items: items.value,
            ...orderDetails
        };
        
        const { data } = await adminApi.post('/quick-order/create', payload);
        
        alert(`Comanda ${data.order_number} a fost creată cu succes!`);
        items.value = [];
        showPreview.value = false;
        
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
    
    // Check Agent Status
    if (isSalesAgentOrDirector.value) {
        await trackingStore.checkStatus();
        await visitStore.checkActiveVisit();
        
        if (visitStore.hasActiveVisit && visitStore.activeVisit.customer) {
            selectedCustomer.value = visitStore.activeVisit.customer;
            await fetchCustomerData(selectedCustomer.value.id);
            await fetchPromotions(selectedCustomer.value.id);
        }
    }
    
    // Check Impersonation
    if (isImpersonating.value) {
        const imp = authStore.impersonatedCustomer;
        if (imp && imp.id) {
            adminApi.get(`/customers/${imp.id}`)
                .then(({ data }) => selectCustomer(data))
                .catch(err => console.error("Failed to load impersonated customer", err));
        }
    } else if (isCustomer.value) {
        if (authStore.user?.customer) {
             selectCustomer(authStore.user.customer);
        } else if (authStore.user?.customer_id) {
            adminApi.get(`/customers/${authStore.user.customer_id}`)
                .then(({ data }) => selectCustomer(data))
                .catch(err => console.error(err));
        }
    }
});

// Watch for role changes
watch(isSalesAgentOrDirector, (newVal) => {
    if (newVal) {
        trackingStore.checkStatus();
    }
});

// Watch for active visit changes
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
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
