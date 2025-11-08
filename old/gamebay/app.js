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

async function loadProducts() {
  try {
    const res = await fetch('http://localhost/gamebay/products.php', { cache: "no-store" });
    if(!res.ok) throw new Error('Hálózati hiba: ' + res.status);
    products = await res.json();
    renderProducts(products);
    renderMarquee(products);
    updateCartUI();
    startMarquee();
  } catch (err) {
    console.error('Hiba a termékek betöltésekor:', err);
    productGrid.innerHTML = `<p>Nem sikerült betölteni a termékeket.</p>`;
  }
}

// --- render funkciók ---
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
    node.querySelector('.add-to-cart').addEventListener('click', ()=> addToCart(p.id));
    img.addEventListener('click', ()=> showProductModal(p));
    title.addEventListener('click', ()=> showProductModal(p));
    productGrid.appendChild(node);
  });
}

// --- kosár ---
function addToCart(id){ cart[id] = (cart[id]||0)+1; saveCart(); updateCartUI(); }
function saveCart(){ localStorage.setItem('gamebay_cart', JSON.stringify(cart)); }

function updateCartUI(){
  const count = Object.values(cart).reduce((s,n)=>s+n,0);
  cartCountEl.textContent = count;
  cartItemsEl.innerHTML = '';
  let total = 0;
  Object.entries(cart).forEach(([id, qty])=>{
    const product = products.find(p=>p.id==id);
    if(!product) return;
    total += product.price*qty;
    const div = document.createElement('div');
    div.className='cart-item';
    div.innerHTML = `
      <img src="${product.image}" alt="${product.title}">
      <div style="flex:1">
        <div>${product.title}</div>
        <div>${product.price} Ft × ${qty}</div>
      </div>
      <div>
        <button data-id="${id}" class="dec">−</button>
        <button data-id="${id}" class="inc">+</button>
        <button data-id="${id}" class="rm">×</button>
      </div>`;
    cartItemsEl.appendChild(div);
  });
  cartTotalEl.textContent = total;

  cartItemsEl.querySelectorAll('button.dec').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id=b.dataset.id; if(cart[id]>1) cart[id]--; else delete cart[id];
      saveCart(); updateCartUI();
    });
  });
  cartItemsEl.querySelectorAll('button.inc').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id=b.dataset.id; cart[id]=(cart[id]||0)+1;
      saveCart(); updateCartUI();
    });
  });
  cartItemsEl.querySelectorAll('button.rm').forEach(b=>{
    b.addEventListener('click', ()=> {
      const id=b.dataset.id; delete cart[id];
      saveCart(); updateCartUI();
    });
  });
}

// --- modal ---
function showProductModal(p){
  modalImg.src=p.image;
  modalTitle.textContent=p.title;
  modalPrice.textContent=p.price+' Ft';
  modalDesc.textContent=p.description;
  modalMeta.textContent=`${p.publisher ? 'Kiadó: '+p.publisher+' • ' : ''}${p.year || ''}`;
  modalAdd.onclick=()=>{ addToCart(p.id); };
  modal.classList.remove('hidden'); modal.setAttribute('aria-hidden','false');
}
function closeModal(){ modal.classList.add('hidden'); modal.setAttribute('aria-hidden','true'); }
closeModalBtn.addEventListener('click', closeModal);
modal.addEventListener('click', e=> { if(e.target===modal) closeModal(); });
document.addEventListener('keydown', e=> { if(e.key==='Escape') closeModal(); });

// --- keresés és rendezés ---
headerSearch.addEventListener('input', ()=>{
  const q = headerSearch.value.trim().toLowerCase();
  renderProducts(products.filter(p=>p.title.toLowerCase().includes(q)));
});
sortEl.addEventListener('change', ()=>{
  const val=sortEl.value; let sorted=[...products];
  if(val==='price-asc') sorted.sort((a,b)=>a.price-b.price);
  if(val==='price-desc') sorted.sort((a,b)=>b.price-a.price);
  renderProducts(sorted);
});

// --- kosár panel ---
cartBtn.addEventListener('click', ()=> cartPanel.classList.toggle('hidden'));
closeCartBtn.addEventListener('click', ()=> cartPanel.classList.add('hidden'));
checkoutBtn.addEventListener('click', ()=> alert('Checkout demo.'));

// --- marquee ---
let marqueePos=0,marqueeLast=null,marqueeAniFrame=null,marqueeHalfWidth=0;
const MARQUEE_SPEED=40;
function renderMarquee(list){
  const itemsHTML=list.map(p=>`<a href="#" class="marquee-item" data-id="${p.id}"><img src="${p.image}" alt="${p.title}"></a>`).join('');
  marqueeTrack.innerHTML=itemsHTML+itemsHTML;
  marqueeTrack.onclick=e=>{
    const a=e.target.closest('a[data-id]');
    if(!a) return; e.preventDefault();
    const p=products.find(p=>p.id===a.dataset.id);
    if(p) showProductModal(p);
  };
}
function startMarquee(){
  cancelAnimationFrame(marqueeAniFrame); marqueePos=0; marqueeLast=null;
  setTimeout(()=>{
    marqueeHalfWidth=marqueeTrack.scrollWidth/2;
    marqueeLoop();
  },120);
}
function marqueeLoop(ts){
  if(!marqueeLast) marqueeLast=ts;
  const dt=(ts-marqueeLast)/1000; marqueeLast=ts;
  marqueePos+=MARQUEE_SPEED*dt;
  if(marqueeHalfWidth>0 && marqueePos>=marqueeHalfWidth) marqueePos-=marqueeHalfWidth;
  marqueeTrack.style.transform=`translateX(${-marqueePos}px)`;
  marqueeAniFrame=requestAnimationFrame(marqueeLoop);
}
marqueeContainer.addEventListener('mouseenter', ()=> cancelAnimationFrame(marqueeAniFrame));
marqueeContainer.addEventListener('mouseleave', ()=> { marqueeLast=null; marqueeLoop(performance.now()); });

// --- init ---
loadProducts();
