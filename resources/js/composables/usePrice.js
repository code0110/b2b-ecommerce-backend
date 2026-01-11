import { usePreferencesStore } from '@/store/preferences';
import { computed } from 'vue';

export function usePrice() {
  const preferences = usePreferencesStore();

  const calculatePrice = (value, vatRate = 19, isVatIncluded = false) => {
    if (value === null || value === undefined) return 0;

    let finalPrice = Number(value);
    const rate = Number(vatRate) || 19;

    if (preferences.showVat) {
        if (!isVatIncluded) {
            finalPrice = finalPrice * (1 + (rate / 100));
        }
    } else {
        if (isVatIncluded) {
            finalPrice = finalPrice / (1 + (rate / 100));
        }
    }
    return finalPrice;
  };

  const formatPrice = (value, vatRate = 19, isVatIncluded = false) => {
    const price = calculatePrice(value, vatRate, isVatIncluded);
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON' }).format(price);
  };

  return {
    formatPrice,
    calculatePrice,
    showVat: computed(() => preferences.showVat)
  };
}
