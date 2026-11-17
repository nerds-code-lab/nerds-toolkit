/* Native scrolling: no autoplay, clones, dependencies or focus interception. */
(() => {
  const mounted = new WeakSet();
  const mount = root => {
    if (mounted.has(root)) return;
    const track = root.querySelector('ul.products');
    if (!track || !track.children.length) return;
    mounted.add(root);
    const controls = root.querySelector('.nl-carousel-controls');
    const previous = root.querySelector('.nl-carousel-prev');
    const next = root.querySelector('.nl-carousel-next');
    const status = root.querySelector('.nl-carousel-status');
    track.tabIndex = 0;
    track.setAttribute('aria-label', root.getAttribute('aria-label'));
    const update = () => {
      if (!root.isConnected) { resize.disconnect(); return; }
      const max = track.scrollWidth - track.clientWidth;
      controls.hidden = max < 2;
      previous.disabled = track.scrollLeft < 2;
      next.disabled = track.scrollLeft >= max - 2;
      const bounds = track.getBoundingClientRect();
      // The next-card hint is not a readable product. Count majority-visible cards.
      const visible = [...track.children].map((card, i) => ({i, rect:card.getBoundingClientRect()})).filter(({rect}) => {
        const overlap = Math.max(0, Math.min(rect.right, bounds.right) - Math.max(rect.left, bounds.left));
        return overlap >= rect.width / 2;
      });
      if (visible.length) {
        const first = visible[0].i + 1;
        const last = visible.at(-1).i + 1;
        status.textContent = `${first === last ? first : `${first}–${last}`} / ${track.children.length}`;
      }
    };
    const behavior = () => matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth';
    const move = direction => {
      const width = track.firstElementChild.getBoundingClientRect().width;
      const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      track.scrollBy({left:direction * (width + gap), behavior:behavior()});
    };
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('keydown', event => {
      if (event.target !== track) return;
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault(); move(event.key === 'ArrowRight' ? 1 : -1);
      } else if (event.key === 'Home' || event.key === 'End') {
        event.preventDefault(); track.scrollTo({left:event.key === 'Home' ? 0 : track.scrollWidth, behavior:behavior()});
      }
    });
    let timer;
    track.addEventListener('scroll', () => { clearTimeout(timer); timer = setTimeout(update, 100); }, {passive:true});
    const resize = new ResizeObserver(update);
    resize.observe(track);
    update();
  };
  const scan = node => {
    if (!(node instanceof Element)) return;
    if (node.matches('.nl-product-carousel')) mount(node);
    node.querySelectorAll('.nl-product-carousel').forEach(mount);
  };
  scan(document.documentElement);
  // Elementor replaces widget markup in-place after changing native controls.
  new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(scan)))
    .observe(document.body, {childList:true, subtree:true});
})();
