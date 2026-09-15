try {
  const savedTheme = localStorage.getItem('urbanNestTheme');
  if (savedTheme === 'dark') document.documentElement.dataset.theme = 'dark';
} catch (e) {}

document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.app-shell');
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('menuToggle');
  const accountMenu = document.querySelector('.account-menu');
  const accountToggle = document.getElementById('accountMenuToggle');
  const accountDropdown = document.getElementById('accountDropdown');

  const isMobile = () => window.matchMedia('(max-width: 900px)').matches;

  function setMenuButtonState(open) {
    if (!toggle) return;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    toggle.innerHTML = open ? '×' : '☰';
  }

  function closeMobileSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    document.body.classList.remove('nav-open');
    setMenuButtonState(false);
  }

  if (toggle && sidebar && shell) {
    toggle.addEventListener('click', () => {
      if (isMobile()) {
        const open = !sidebar.classList.contains('open');
        sidebar.classList.toggle('open', open);
        document.body.classList.toggle('nav-open', open);
        setMenuButtonState(open);
      } else {
        const collapsed = !shell.classList.contains('sidebar-collapsed');
        shell.classList.toggle('sidebar-collapsed', collapsed);
        try { localStorage.setItem('urbanNestSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        setMenuButtonState(false);
      }
    });

    try {
      if (!isMobile() && localStorage.getItem('urbanNestSidebarCollapsed') === '1') {
        shell.classList.add('sidebar-collapsed');
      }
    } catch (e) {}

    document.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (isMobile()) closeMobileSidebar();
      });
    });

    window.addEventListener('resize', () => {
      if (!isMobile()) {
        closeMobileSidebar();
      }
    });
  }

  function closeAccountMenu() {
    if (!accountMenu || !accountToggle || !accountDropdown) return;
    accountMenu.classList.remove('open');
    accountToggle.setAttribute('aria-expanded', 'false');
    accountDropdown.setAttribute('aria-hidden', 'true');
  }

  if (accountMenu && accountToggle && accountDropdown) {
    accountToggle.addEventListener('click', (event) => {
      event.stopPropagation();
      const open = !accountMenu.classList.contains('open');
      accountMenu.classList.toggle('open', open);
      accountToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      accountDropdown.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    accountDropdown.addEventListener('click', event => event.stopPropagation());
    document.addEventListener('click', closeAccountMenu);
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        closeAccountMenu();
        if (isMobile()) closeMobileSidebar();
      }
    });
  }

  const themeToggle = document.getElementById('themeToggle');
  const applyThemeButtonState = () => {
    if (!themeToggle) return;
    const dark = document.documentElement.dataset.theme === 'dark';
    const icon = themeToggle.querySelector('.theme-icon');
    const label = themeToggle.querySelector('.theme-label');
    if (icon) icon.textContent = dark ? '☀' : '☾';
    if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
    themeToggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    themeToggle.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
  };

  if (themeToggle) {
    applyThemeButtonState();
    themeToggle.addEventListener('click', () => {
      const dark = document.documentElement.dataset.theme === 'dark';
      document.documentElement.dataset.theme = dark ? '' : 'dark';
      if (!document.documentElement.dataset.theme) delete document.documentElement.dataset.theme;
      try {
        localStorage.setItem('urbanNestTheme', dark ? 'light' : 'dark');
      } catch (e) {}
      applyThemeButtonState();
    });
  }

  document.querySelectorAll('[data-confirm]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  document.querySelectorAll('[data-toggle-password]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.togglePassword);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      button.textContent = input.type === 'password' ? 'Show' : 'Hide';
    });
  });

  document.querySelectorAll('[data-validate]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
      }
      const password = form.querySelector('input[name="password"], input[name="new_password"]');
      const confirm = form.querySelector('input[name="confirm_password"]');
      if (password && confirm && password.value !== confirm.value) {
        e.preventDefault();
        confirm.setCustomValidity('Passwords do not match.');
        confirm.reportValidity();
        confirm.setCustomValidity('');
      }
    });
  });

  const searchInput = document.getElementById('tableSearch');
  const table = document.getElementById('dataTable');
  if (searchInput && table) {
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
      });
    });
  }

  const cardSearch = document.getElementById('cardSearch');
  const cards = document.querySelectorAll('.apartment-card');
  if (cardSearch && cards.length) {
    cardSearch.addEventListener('input', () => {
      const query = cardSearch.value.toLowerCase().trim();
      cards.forEach(card => card.style.display = card.dataset.search.includes(query) ? '' : 'none');
    });
  }

  const paymentDate = document.getElementById('payment_date');
  const preview = document.getElementById('paymentPreview');
  if (paymentDate && preview) {
    const rentElement = document.getElementById('monthlyRentValue');
    const rent = rentElement ? (parseFloat(rentElement.dataset.rent) || 0) : 0;
    const updatePaymentPreview = () => {
      if (!paymentDate.value) { preview.innerHTML = ''; return; }
      const dueDate = paymentDate.dataset.dueDate || '';
      const paymentTs = new Date(paymentDate.value + 'T00:00:00').getTime();
      const dueTs = dueDate ? new Date(dueDate + 'T00:00:00').getTime() : 0;
      const discount = dueTs && paymentTs < dueTs ? 200 : 0;
      const late = dueTs && paymentTs > dueTs ? 500 : 0;
      const status = dueTs ? (paymentTs < dueTs ? 'Early Payment' : (paymentTs === dueTs ? 'Paid' : 'Overdue')) : 'Pending';
      const total = Math.max(0, rent - discount + late);
      preview.innerHTML = `
        <div class="preview-row"><span>Monthly Rent</span><strong>₱${rent.toLocaleString(undefined,{minimumFractionDigits:2})}</strong></div>
        <div class="preview-row"><span>Early Discount</span><strong>− ₱${discount.toLocaleString()}</strong></div>
        <div class="preview-row"><span>Late Fee</span><strong>+ ₱${late.toLocaleString()}</strong></div>
        <div class="preview-row"><span>Status</span><strong>${status}</strong></div>
        <div class="preview-row total"><span>Total Amount</span><strong>₱${total.toLocaleString(undefined,{minimumFractionDigits:2})}</strong></div>`;
      const payAmount = document.getElementById('payRentAmount');
      if (payAmount) payAmount.textContent = '₱' + total.toLocaleString(undefined,{minimumFractionDigits:2});
    };
    paymentDate.addEventListener('change', updatePaymentPreview);
    updatePaymentPreview();
  }

  document.querySelectorAll('.auto-dismiss').forEach(alert => {
    setTimeout(() => { alert.style.opacity='0'; alert.style.transform='translateY(-4px)'; alert.style.transition='.3s'; setTimeout(()=>alert.remove(),300); }, 4500);
  });
  // Smooth navigation: close mobile menu and animate the page before changing routes.
  document.querySelectorAll('.nav-link, .account-dropdown-item, .back-link, .auth-transition-link').forEach(link => {
    link.addEventListener('click', event => {
      const href = link.getAttribute('href');
      if (!href || href.startsWith('#') || link.target === '_blank') return;
      if (isMobile && isMobile()) {
        closeMobileSidebar();
      }
      if (link.classList.contains('nav-link') && typeof href === 'string') {
        event.preventDefault();
        document.body.classList.add('route-leaving');
        setTimeout(() => { window.location.href = href; }, 180);
      }
    });
  });

  const mainShell = document.querySelector('.main-area');
  if (mainShell) {
    mainShell.addEventListener('click', event => {
      if (!sidebar || !isMobile()) return;
      if (sidebar.classList.contains('open') && !sidebar.contains(event.target) && event.target !== toggle) {
        closeMobileSidebar();
      }
    });
  }

});


let apartmentGalleryImages = [];
let apartmentGalleryIndex = 0;
function openApartmentGallery(id) {
  const data=document.getElementById('gallery-data-'+id);
  const modal=document.getElementById('apartmentGalleryModal');
  if(!data||!modal) return;
  try { apartmentGalleryImages=JSON.parse(data.textContent||'[]'); } catch(e) { apartmentGalleryImages=[]; }
  apartmentGalleryIndex=0;
  modal.hidden=false;
  document.body.classList.add('modal-open');
  updateApartmentGallery();
}
function closeApartmentGallery(){ const modal=document.getElementById('apartmentGalleryModal'); if(modal){modal.hidden=true;document.body.classList.remove('modal-open');} }
function updateApartmentGallery(){
  const img=document.getElementById('galleryMainImage'), empty=document.getElementById('galleryEmpty'), counter=document.getElementById('galleryCounter');
  if(!img||!empty||!counter) return;
  if(!apartmentGalleryImages.length){img.hidden=true;empty.hidden=false;counter.textContent='No photos';return;}
  img.hidden=false; empty.hidden=true; img.src='../uploads/apartments/'+apartmentGalleryImages[apartmentGalleryIndex]; counter.textContent=(apartmentGalleryIndex+1)+' / '+apartmentGalleryImages.length;
}
function galleryPrev(){ if(apartmentGalleryImages.length){apartmentGalleryIndex=(apartmentGalleryIndex-1+apartmentGalleryImages.length)%apartmentGalleryImages.length;updateApartmentGallery();} }
function galleryNext(){ if(apartmentGalleryImages.length){apartmentGalleryIndex=(apartmentGalleryIndex+1)%apartmentGalleryImages.length;updateApartmentGallery();} }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeApartmentGallery(); if(e.key==='ArrowLeft') galleryPrev(); if(e.key==='ArrowRight') galleryNext(); });

const paymentMethodInputs=document.querySelectorAll('input[name="payment_method"]');
const referenceInput=document.getElementById('reference_number');
const paymentForm=document.getElementById('paymentForm');
let demoPaymentReady=false;
let currentDemoReference='';

function getCurrentPaymentTotal(){
  const totalNode=document.querySelector('#paymentPreview .preview-row.total strong');
  if(!totalNode) return 0;
  return parseFloat(totalNode.textContent.replace(/[^0-9.]/g,'')) || 0;
}
function makeDemoReference(prefix){ return prefix+'-'+Date.now().toString().slice(-8); }

function showCardDemo(){
  const amount=getCurrentPaymentTotal();
  if(amount <= 0){ alert('Unable to determine the rent amount. Please refresh the page and try again.'); return false; }
  currentDemoReference=makeDemoReference('CARD');
  if(referenceInput) referenceInput.value=currentDemoReference;
  const url=new URL('card_demo.php',window.location.href);
  url.searchParams.set('amount',amount.toFixed(2));
  url.searchParams.set('ref',currentDemoReference);
  const cardWindow=window.open(url.href,'_blank');
  if(!cardWindow){
    alert('Your browser blocked the new payment tab. Please allow pop-ups for localhost and click Pay Rent again.');
    return false;
  }
  cardWindow.focus();
  return true;
}

function showQrDemo(){
  const amount=getCurrentPaymentTotal();
  if(amount <= 0){ alert('Unable to determine the rent amount. Please refresh the page and try again.'); return false; }
  currentDemoReference=makeDemoReference('QR');
  if(referenceInput) referenceInput.value=currentDemoReference;
  const url=new URL('qr_demo.php',window.location.href);
  url.searchParams.set('amount',amount.toFixed(2));
  url.searchParams.set('ref',currentDemoReference);
  const qrWindow=window.open(url.href,'_blank');
  if(!qrWindow){
    alert('Your browser blocked the new payment tab. Please allow pop-ups for localhost and click Pay Rent again.');
    return false;
  }
  qrWindow.focus();
  return true;
}

function completeDemoPaymentFromPopup(reference){
  const finalReference=reference || currentDemoReference || makeDemoReference('DEMO');
  if(referenceInput) referenceInput.value=finalReference;
  demoPaymentReady=true;
  if(paymentForm) paymentForm.submit();
}
window.completeQrDemoFromPopup=completeDemoPaymentFromPopup;
window.completeCardDemoFromPopup=completeDemoPaymentFromPopup;

if(paymentForm){
  paymentForm.addEventListener('submit',function(e){
    if(demoPaymentReady) return;
    const selected=document.querySelector('input[name="payment_method"]:checked');
    if(!selected) return;
    if(selected.value==='Card'){
      e.preventDefault();
      showCardDemo();
    }else if(selected.value==='QR Payment'){
      e.preventDefault();
      showQrDemo();
    }
  });
}



// Urban Nest public landing page: language switch, mobile menu, scroll reveals, and header motion.
(function(){
  const translations = {
    en: {
      nav_home:'Home', nav_features:'Features', nav_how:'How It Works', nav_login:'Log In', back_home:'← Back to Urban Nest',
      hero_kicker:'APARTMENT RENTAL MANAGEMENT', hero_title:'A simpler way to find, manage, and enjoy your next home.',
      hero_text:'Urban Nest brings apartments, rental requests, tenants, payments, and support together in one organized workspace.',
      hero_cta:'Get Started', hero_secondary:'Explore Features', trust_one:'Easy apartment browsing', trust_two:'Organized rental requests', trust_three:'Simple payment tracking',
      float_home:'Your next home', float_ready:'Ready to explore', float_rentals:'Rental management', float_all:'Everything in one place',
      intro_kicker:'BUILT FOR A BETTER RENTAL EXPERIENCE', intro_title:'Less searching. Less paperwork. More confidence.',
      intro_text:'Whether you are looking for an available apartment or managing a property, Urban Nest keeps the important details easy to see and easy to manage.',
      features_kicker:'FEATURES', features_title:'Everything you need in one place.', features_text:'A clean workspace designed to make everyday rental tasks easier.',
      feature_1_title:'Browse Apartments', feature_1_text:'View available units, rental details, and apartment information before making a request.',
      feature_2_title:'Manage Requests', feature_2_text:'Keep rental requests organized from submission through approval.',
      feature_3_title:'Track Payments', feature_3_text:'See payment records and rental status without digging through paperwork.',
      feature_4_title:'Stay Connected', feature_4_text:'Use customer support messages to communicate with the rental team.',
      how_badge:'Simple by design', how_kicker:'HOW IT WORKS', how_title:'Start with a few simple steps.',
      step1_title:'Create an account', step1_text:'Register as a tenant and sign in securely.', step2_title:'Explore available apartments', step2_text:'Compare units and choose a place that fits your needs.',
      step3_title:'Manage your rental', step3_text:'Follow requests, payments, messages, and rental updates from your dashboard.', how_cta:'Log In to Urban Nest',
      cta_kicker:'READY WHEN YOU ARE', cta_title:'Make apartment rental management feel simple.', cta_text:'Enter Urban Nest and keep your rental journey organized from one place.', cta_button:'Continue to Login',
      footer_text:'Apartment Rental Management System • School Project',
      team_kicker:'OUR TEAM', team_title:'Meet the Team', team_text:'The people behind Urban Nest, working together to create a simple rental management experience.',
      team_role_1:'UI Specialist', team_role_2:'Frontend Developer', team_role_3:'Lead Developer', team_role_4:'Security Tester', team_role_5:'Frontend Tester',
      team_desc_1:'Designs intuitive interfaces and user experiences.', team_desc_2:'Designs and implements the user interface, creating an intuitive and visually appealing experience.', team_desc_3:'Develops and maintains the research platform, ensuring a seamless user experience.', team_desc_4:"Tests the website's security measures, identifying vulnerabilities and ensuring data protection.", team_desc_5:'Tests and evaluates the website to ensure functionality and a smooth user experience.',
      login_visual_title:'Manage rentals without the paperwork.', login_visual_text:'Track apartments, rental requests, tenants, and monthly payments in one clean workspace.',
      login_kicker:'WELCOME BACK', login_title:'Sign in to your account', login_text:'Enter your account details to continue.', login_username:'Username', username_placeholder:'Enter username',
      login_password:'Password', password_placeholder:'Enter password', forgot_password:'Forgot password?', sign_in:'Sign In', new_here:'New here?', create_account:'Create a Tenant Account'
    },
    tl: {
      nav_home:'Home', nav_features:'Mga Feature', nav_how:'Paano Ito Gumagana', nav_login:'Mag-login', back_home:'← Bumalik sa Urban Nest',
      hero_kicker:'PAMAMAHALA NG PAUPAHAN', hero_title:'Mas madaling paraan para makahanap, mamahala, at magkaroon ng bagong tahanan.',
      hero_text:'Pinagsasama ng Urban Nest ang mga apartment, rental request, tenant, bayarin, at support sa isang organisadong workspace.',
      hero_cta:'Magsimula', hero_secondary:'Tingnan ang Mga Feature', trust_one:'Madaling pag-browse ng apartment', trust_two:'Organisadong rental request', trust_three:'Simpleng payment tracking',
      float_home:'Iyong susunod na tahanan', float_ready:'Handang tuklasin', float_rentals:'Pamamahala ng paupahan', float_all:'Lahat nasa isang lugar',
      intro_kicker:'GINAWA PARA SA MAS MAGANDANG RENTAL EXPERIENCE', intro_title:'Mas kaunting paghahanap. Mas kaunting papeles. Mas kumpiyansa.',
      intro_text:'Kung naghahanap ka ng apartment o namamahala ng property, pinapadali ng Urban Nest ang pagtingin at pamamahala ng mahahalagang detalye.',
      features_kicker:'MGA FEATURE', features_title:'Lahat ng kailangan mo, nasa isang lugar.', features_text:'Malinis na workspace para mas mapadali ang mga gawain sa pag-upa.',
      feature_1_title:'Mag-browse ng Apartment', feature_1_text:'Tingnan ang available na unit, rental details, at impormasyon bago gumawa ng request.',
      feature_2_title:'Pamahalaan ang Requests', feature_2_text:'Ayusin ang rental requests mula sa pagsusumite hanggang approval.',
      feature_3_title:'Subaybayan ang Bayad', feature_3_text:'Tingnan ang payment records at rental status nang hindi naghahanap ng papeles.',
      feature_4_title:'Manatiling Connected', feature_4_text:'Gamitin ang customer support messages para makipag-ugnayan sa rental team.',
      how_badge:'Simple sa disenyo', how_kicker:'PAANO ITO GUMAGANA', how_title:'Magsimula sa ilang simpleng hakbang.',
      step1_title:'Gumawa ng account', step1_text:'Mag-register bilang tenant at ligtas na mag-sign in.', step2_title:'Tingnan ang available na apartment', step2_text:'Ihambing ang mga unit at pumili ng lugar na bagay sa iyong pangangailangan.',
      step3_title:'Pamahalaan ang iyong rental', step3_text:'Sundan ang requests, payments, messages, at rental updates sa iyong dashboard.', how_cta:'Mag-login sa Urban Nest',
      cta_kicker:'HANDA KA NA BA?', cta_title:'Gawing simple ang pamamahala ng apartment rental.', cta_text:'Pumasok sa Urban Nest at panatilihing organisado ang iyong rental journey sa isang lugar.', cta_button:'Magpatuloy sa Login',
      footer_text:'Apartment Rental Management System • School Project',
      team_kicker:'OUR TEAM', team_title:'Meet the Team', team_text:'The people behind Urban Nest, working together to create a simple rental management experience.',
      team_role_1:'UI Specialist', team_role_2:'Frontend Developer', team_role_3:'Lead Developer', team_role_4:'Security Tester', team_role_5:'Frontend Tester',
      team_desc_1:'Nagdidisenyo ng madaling gamitin na interface at magandang user experience.', team_desc_2:'Gumagawa at nag-iimplement ng user interface na madaling gamitin at kaaya-ayang tingnan.', team_desc_3:'Bumubuo at nagpapanatili ng research platform upang maging maayos ang user experience.', team_desc_4:'Sinusuri ang security ng website, hinahanap ang vulnerabilities, at tinitiyak ang proteksyon ng data.', team_desc_5:'Sinusuri ang website upang matiyak ang functionality at maayos na user experience.',
      login_visual_title:'Pamahalaan ang rental nang walang maraming papeles.', login_visual_text:'Subaybayan ang apartment, rental requests, tenants, at buwanang bayarin sa isang malinis na workspace.',
      login_kicker:'WELCOME BACK', login_title:'Mag-sign in sa iyong account', login_text:'Ilagay ang iyong account details para magpatuloy.', login_username:'Username', username_placeholder:'Ilagay ang username',
      login_password:'Password', password_placeholder:'Ilagay ang password', forgot_password:'Nakalimutan ang password?', sign_in:'Mag-sign In', new_here:'Bago rito?', create_account:'Gumawa ng Tenant Account'
    }
  };
  const langButton=document.getElementById('languageToggle');
  const applyLanguage=(lang)=>{
    const dict=translations[lang]||translations.en;
    document.documentElement.lang=lang==='tl'?'tl':'en';
    document.querySelectorAll('[data-i18n]').forEach(el=>{ if(dict[el.dataset.i18n]!==undefined) el.textContent=dict[el.dataset.i18n]; });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el=>{ if(dict[el.dataset.i18nPlaceholder]!==undefined) el.placeholder=dict[el.dataset.i18nPlaceholder]; });
    if(langButton){ langButton.querySelector('.lang-active').textContent=lang==='tl'?'TL':'EN'; }
    try{localStorage.setItem('urbanNestLanguage',lang);}catch(e){}
  };
  let lang='en'; try{lang=localStorage.getItem('urbanNestLanguage')||'en';}catch(e){}
  applyLanguage(lang);
  if(langButton) langButton.addEventListener('click',()=>applyLanguage(langButton.querySelector('.lang-active').textContent==='EN'?'tl':'en'));

  const landingNav=document.getElementById('landingNav');
  const landingMenu=document.getElementById('landingMenuToggle');
  if(landingNav && landingMenu){
    landingMenu.addEventListener('click',()=>{const open=landingNav.classList.toggle('open');landingMenu.setAttribute('aria-expanded',open?'true':'false');});
    landingNav.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{landingNav.classList.remove('open');landingMenu.setAttribute('aria-expanded','false');}));
  }
  const landingHeader=document.getElementById('landingHeader');
  const onScroll=()=>{if(landingHeader) landingHeader.classList.toggle('scrolled',window.scrollY>8);};
  window.addEventListener('scroll',onScroll,{passive:true}); onScroll();

  const revealItems=document.querySelectorAll('.reveal-on-scroll');
  if('IntersectionObserver' in window){
    const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('revealed');observer.unobserve(entry.target);}}),{threshold:.12,rootMargin:'0px 0px -50px 0px'});
    revealItems.forEach((el,i)=>{el.style.setProperty('--reveal-delay',Math.min((i%5)*90,360)+'ms');observer.observe(el);});
  }else revealItems.forEach(el=>el.classList.add('revealed'));
})();

// Urban Nest authentication slideshow + smooth login/forgot transitions
(function(){
  const visual=document.querySelector('.auth-visual');
  const slides=visual ? [...visual.querySelectorAll('.auth-slide')] : [];
  const images=[
    'assets/auth-backgrounds/apartment-1.png',
    'assets/auth-backgrounds/apartment-2.png',
    'assets/auth-backgrounds/apartment-3.png',
    'assets/auth-backgrounds/apartment-4.png'
  ];

  if(slides.length){
    let index=0;
    let active=0;
    const setSlide=(el,src,show)=>{
      el.style.backgroundImage=`url("${src}")`;
      el.classList.toggle('active',show);
    };
    images.forEach(src=>{const img=new Image();img.src=src;});
    setSlide(slides[0],images[0],true);
    if(slides[1]) setSlide(slides[1],images[1],false);
    window.setInterval(()=>{
      if(slides.length<2) return;
      index=(index+1)%images.length;
      const next=active===0?1:0;
      setSlide(slides[next],images[index],true);
      slides[active].classList.remove('active');
      active=next;
    },5000);
  }

  const page=document.body;
  const reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const links=document.querySelectorAll('[data-auth-transition]');

  links.forEach(link=>{
    link.addEventListener('click',event=>{
      if(reduce) return;
      const direction=link.dataset.authTransition==='right'?'right':'left';
      event.preventDefault();
      page.classList.remove('auth-leaving-left','auth-leaving-right');
      page.classList.add(direction==='left'?'auth-leaving-left':'auth-leaving-right');
      try{sessionStorage.setItem('urbanNestAuthTransition',direction);}catch(e){}
      window.setTimeout(()=>{window.location.assign(link.href);},420);
    });
  });

  try{
    const direction=sessionStorage.getItem('urbanNestAuthTransition');
    if(direction && !reduce){
      page.classList.add(direction==='left'?'auth-enter-left':'auth-enter-right');
      sessionStorage.removeItem('urbanNestAuthTransition');
      window.setTimeout(()=>{
        page.classList.remove('auth-enter-left','auth-enter-right');
      },520);
    }else if(direction){
      sessionStorage.removeItem('urbanNestAuthTransition');
    }
  }catch(e){}
})();
