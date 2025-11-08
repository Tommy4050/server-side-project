// app.js — debug változat: részletes hibajelzés, fallback inline adatok, JS marquee, modal, kosár
const productGrid = document.getElementById('productGrid');
const tpl = document.getElementById('productTpl');
const cartBtn = document.getElementById('cartBtn');
const cartPanel = document.getElementById('cartPanel');
const cartItemsEl = document.getElementById('cartItems');
const cartCountEl = document.getElementById('cartCount');
const cartTotalEl = document.getElementById('cartTotal');
const closeCartBtn = document.getElementById('closeCart');
const checkoutBtn = document.getElementById('checkoutBtn');
const headerSearch = document.getElementById('headerSearch');
const sortEl = document.getElementById('sort');

const marqueeTrack = document.getElementById('marqueeTrack');
const marqueeContainer = document.getElementById('marqueeContainer');

const modal = document.getElementById('productModal');
const modalImg = document.getElementById('modalImg');
const modalTitle = document.getElementById('modalTitle');
const modalPrice = document.getElementById('modalPrice');
const modalDesc = document.getElementById('modalDesc');
const modalMeta = document.getElementById('modalMeta');
const modalAdd = document.getElementById('modalAdd');
const closeModalBtn = document.querySelector('.close-modal');

let products = [];
let cart = JSON.parse(localStorage.getItem('gamebay_cart') || '{}');

// ---- INLINE FALLBACK ADAT (ha a fetch nem működik) ----
const SAMPLE_FALLBACK = [
  {"id":"p1","title":"Cyber Runner","price":6990,"image":"https://via.placeholder.com/400x200.png?text=Cyber+Runner","description":"Sebesség platformer.","publisher":"NeonWorks","year":2024},
  {"id":"p2","title":"Neon Knights","price":9990,"image":"https://via.placeholder.com/400x200.png?text=Neon+Knights","description":"Akció-RPG.","publisher":"Arcadia","year":2023},
  {"id":"p1","title":"Cyber Runner","price":6990,"image":"https://via.placeholder.com/400x200.png?text=Cyber+Runner","description":"Sebesség platformer.","publisher":"NeonWorks","year":2024},
  {"id":"p2","title":"Neon Knights","price":9990,"image":"https://via.placeholder.com/400x200.png?text=Neon+Knights","description":"Akció-RPG.","publisher":"Arcadia","year":2023},
  {"id":"p1","title":"Cyber Runner","price":6990,"image":"https://via.placeholder.com/400x200.png?text=Cyber+Runner","description":"Sebesség platformer.","publisher":"NeonWorks","year":2024},
  {"id":"p2","title":"Neon Knights","price":9990,"image":"https://via.placeholder.com/400x200.png?text=Neon+Knights","description":"Akció-RPG.","publisher":"Arcadia","year":2023}
];

// ---- betöltés (diagnosztikával) ----
async function loadProducts(){
  try {
    // QOL: clear previous errors
    productGrid.innerHTML = '<p>Termékek betöltése…</p>';

    const res = await fetch('./products.json', {cache: "no-store"});
    console.log('[loadProducts] fetch status:', res.status, 'ok:', res.ok);
    console.log('[loadProducts] content-type:', res.headers.get('content-type'));

    if(!res.ok){
      throw new Error(`Hálózati hiba: ${res.status} ${res.statusText}`);
    }

    const raw = await res.text();
    console.log('[loadProducts] products.json raw first 300 chars:', raw.slice(0,300));

    // Eltávolítjuk esetleges BOM-ot
    const cleaned = raw.replace(/^\uFEFF/, '');

    try {
      products = JSON.parse(cleaned);
      if(!Array.isArray(products)) throw new Error('A JSON nem tömb (expected array).');
      console.log('[loadProducts] JSON parsed, items:', products.length);
    } catch(parseErr){
      console.error('[loadProducts] JSON parse error:', parseErr);
      productGrid.innerHTML = `<p>Hiba: products.json feldolgozása sikertelen. Nézd meg a konzolt (F12).</p>`;
      // fallbackként használhatjuk az inline mintát:
      products = SAMPLE_FALLBACK;
      console.warn('[loadProducts] Using SAMPLE_FALLBACK as fallback.');
    }

    renderProducts(products);
    renderMarquee(products);
    updateCartUI();
    startMarquee();
  } catch (err) {
    console.error('[loadProducts] fetch failed:', err);
    // ha fetch vagy parse nem ment, fallback:
    productGrid.innerHTML = `<p>Nem sikerült betölteni a products.json-t: ${err.message}. Próbáljuk a helyi fallbacket.</p>`;
    products = SAMPLE_FALLBACK;
    renderProducts(products);
    renderMarquee(products);
    updateCartUI();
    startMarquee();
  }
}

// ---- a többi funkció (render, cart, modal, marquee) ----
function renderProducts(list){
  productGrid.innerHTML = '';
  list.forEach(p => {
    const node = tpl.content.cloneNode(true);
    const img = node.querySelector('.product-img');
    const title = node.querySelector('.product-title');
    img.src = p.image;
    img.alt = p.title;
    title.textContent = p.title;
    node.querySelector('.product-price').textContent = p.price + ' Ft';
    const btn = node.querySelector('.add-to-cart');
    btn.addEventListener('click', () => addToCart(p.id));

    img.addEventListener('click', ()=> showProductModal(p));
    title.addEventListener('click', ()=> showProductModal(p));

    productGrid.appendChild(node);
  });
}

function addToCart(id){
  cart[id] = (cart[id] || 0) + 1;
  saveCart();
  updateCartUI();
}
function saveCart(){ localStorage.setItem('gamebay_cart', JSON.stringify(cart)); }

function updateCartUI(){
  const count = Object.values(cart).reduce((s,n)=>s+n,0);
  cartCountEl.textContent = count;
  cartItemsEl.innerHTML = '';
  let total = 0;
  Object.entries(cart).forEach(([id, qty]) => {
    const product = products.find(p => p.id === id);
    if(!product) return;
    total += product.price * qty;
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <img src="${product.image}" alt="${product.title}" />
      <div style="flex:1">
        <div>${product.title}</div>
        <div>${product.price} Ft × ${qty}</div>
      </div>
      <div>
        <button data-id="${id}" class="dec">−</button>
        <button data-id="${id}" class="inc">+</button>
        <button data-id="${id}" class="rm">×</button>
      </div>
    `;
    cartItemsEl.appendChild(div);
  });
  cartTotalEl.textContent = total;

  cartItemsEl.querySelectorAll('button.dec').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id = b.dataset.id;
      if(cart[id] > 1) cart[id]--;
      else delete cart[id];
      saveCart(); updateCartUI();
    });
  });
  cartItemsEl.querySelectorAll('button.inc').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id = b.dataset.id;
      cart[id] = (cart[id]||0) + 1;
      saveCart(); updateCartUI();
    });
  });
  cartItemsEl.querySelectorAll('button.rm').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id = b.dataset.id;
      delete cart[id];
      saveCart(); updateCartUI();
    });
  });
}

function showProductModal(p){
  modalImg.src = p.image;
  modalTitle.textContent = p.title;
  modalPrice.textContent = p.price + ' Ft';
  modalDesc.textContent = p.description || 'Nincs leírás.';
  modalMeta.textContent = `${p.publisher ? 'Kiadó: ' + p.publisher + ' • ' : ''}${p.year ? p.year : ''}`;
  modalAdd.onclick = ()=> { addToCart(p.id); };
  modal.classList.remove('hidden');
  modal.setAttribute('aria-hidden','false');
}
function closeModal(){
  modal.classList.add('hidden');
  modal.setAttribute('aria-hidden','true');
}
closeModalBtn.addEventListener('click', closeModal);
modal.addEventListener('click', e=> { if(e.target === modal) closeModal(); });
document.addEventListener('keydown', e=> { if(e.key === 'Escape') closeModal(); });

headerSearch.addEventListener('input', ()=> {
  const q = headerSearch.value.trim().toLowerCase();
  const filtered = products.filter(p => p.title.toLowerCase().includes(q));
  renderProducts(filtered);
});
sortEl.addEventListener('change', ()=> {
  const val = sortEl.value;
  let sorted = [...products];
  if(val === 'price-asc') sorted.sort((a,b)=>a.price-b.price);
  if(val === 'price-desc') sorted.sort((a,b)=>b.price-a.price);
  renderProducts(sorted);
});

cartBtn.addEventListener('click', ()=> {
  cartPanel.classList.toggle('hidden');
  cartPanel.setAttribute('aria-hidden', cartPanel.classList.contains('hidden') ? 'true' : 'false');
});
closeCartBtn.addEventListener('click', ()=> cartPanel.classList.add('hidden'));
checkoutBtn.addEventListener('click', ()=> alert('Checkout demo — demo fizetés.'));

// ---- marquee (ugrásmentes) ----
let marqueePos = 0, marqueeLast = null, marqueeAniFrame = null;
let marqueeHalfWidth = 0;
const MARQUEE_SPEED = 40; // px / sec

function renderMarquee(list){
  // duplikáljuk a sorozatot a folytonossághoz
  const itemsHTML = list.map(p => `
    <a href="#" class="marquee-item" data-id="${p.id}" aria-label="${p.title}">
      <img src="${p.image}" alt="${p.title}">
    </a>`).join('');
  marqueeTrack.innerHTML = itemsHTML + itemsHTML;

  // egyszerű kattintás-delegálás (ne adjon többszörös listener-t)
  marqueeTrack.onclick = function(e){
    const a = e.target.closest('a[data-id]');
    if(!a) return;
    e.preventDefault();
    const id = a.dataset.id;
    const product = products.find(p => p.id === id);
    if(product) showProductModal(product);
  };
}

function startMarquee(){
  cancelAnimationFrame(marqueeAniFrame);
  marqueePos = 0;
  marqueeLast = null;
  // kis késleltetés, hogy a képek betöltődjenek
  setTimeout(()=> {
    marqueeHalfWidth = marqueeTrack.scrollWidth / 2;
    marqueeLoop();
  }, 120);
}

function marqueeLoop(ts){
  if(!marqueeLast) marqueeLast = ts;
  const dt = (ts - marqueeLast) / 1000;
  marqueeLast = ts;
  marqueePos += MARQUEE_SPEED * dt;
  if(marqueeHalfWidth > 0){
    if(marqueePos >= marqueeHalfWidth) marqueePos -= marqueeHalfWidth;
    marqueeTrack.style.transform = `translateX(${-marqueePos}px)`;
  }
  marqueeAniFrame = requestAnimationFrame(marqueeLoop);
}
marqueeContainer.addEventListener('mouseenter', ()=> { cancelAnimationFrame(marqueeAniFrame); });
marqueeContainer.addEventListener('mouseleave', ()=> { marqueeLast = null; marqueeLoop(performance.now()); });

// ---- init ----
loadProducts();
