export function setTitle(title) {
  if (typeof document !== 'undefined') document.title = title || '';
}
export function setMeta(name, content) {
  if (typeof document === 'undefined') return;
  let el = document.querySelector(`meta[name="${name}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute('name', name);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content || '');
}
export function setMetaProperty(property, content) {
  if (typeof document === 'undefined') return;
  let el = document.querySelector(`meta[property="${property}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute('property', property);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content || '');
}
export function setCanonical(url) {
  if (typeof document === 'undefined') return;
  let el = document.querySelector('link[rel="canonical"]');
  if (!el) {
    el = document.createElement('link');
    el.setAttribute('rel', 'canonical');
    document.head.appendChild(el);
  }
  el.setAttribute('href', url || '');
}
export function setJsonLd(obj) {
  if (typeof document === 'undefined') return;
  const json = JSON.stringify(obj);
  let el = document.querySelector('script[type="application/ld+json"]#jsonld-primary');
  if (!el) {
    el = document.createElement('script');
    el.type = 'application/ld+json';
    el.id = 'jsonld-primary';
    document.head.appendChild(el);
  }
  el.textContent = json;
}
