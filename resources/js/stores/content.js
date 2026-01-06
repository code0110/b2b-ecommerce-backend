import { defineStore } from 'pinia';
import api from '@/services/http';

export const useContentStore = defineStore('content', {
    state: () => ({
        blocks: {},
        loading: false,
        error: null,
    }),

    getters: {
        footerBlocks: (state) => {
            // Filter blocks that start with 'footer_'
            const footerData = {};
            for (const key in state.blocks) {
                if (key.startsWith('footer_')) {
                    footerData[key] = state.blocks[key];
                }
            }
            return footerData;
        },
        homeBlocks: (state) => {
            const homeData = {};
            for (const key in state.blocks) {
                if (key.startsWith('home_')) {
                    homeData[key] = state.blocks[key];
                }
            }
            return homeData;
        },
        getBlock: (state) => (key) => state.blocks[key] || null,
    },

    actions: {
        async fetchBlocks(group = null) {
            this.loading = true;
            try {
                const params = group ? { group } : {};
                const { data } = await api.get('/content-blocks', { params });
                
                // Merge new blocks with existing ones
                this.blocks = { ...this.blocks, ...data };
            } catch (error) {
                console.error('Failed to fetch content blocks:', error);
                this.error = error;
            } finally {
                this.loading = false;
            }
        },
    },
});
