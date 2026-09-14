const root=document.querySelector('[data-public-menu]');
if(root){
  const toggle=root.querySelector('.menu-toggle'),nav=root.querySelector('.public-nav'),solutions=root.querySelector('[data-solutions-menu]'),solutionsTrigger=solutions?.querySelector('[data-solutions-trigger]'),solutionsPanel=solutions?.querySelector('[data-solutions-panel]');
  const closeSolutions=(restoreFocus=false)=>{if(!solutionsPanel)return;solutionsPanel.hidden=true;solutionsTrigger.setAttribute('aria-expanded','false');solutions.classList.remove('is-open');if(restoreFocus)solutionsTrigger.focus()};
  const setSolutions=open=>{solutionsPanel.hidden=!open;solutionsTrigger.setAttribute('aria-expanded',String(open));solutions.classList.toggle('is-open',open);if(open)solutionsPanel.querySelector('a')?.focus()};
  const closeMenu=()=>{root.classList.remove('menu-open');document.body.classList.remove('menu-lock');toggle.setAttribute('aria-expanded','false');closeSolutions()};
  toggle?.addEventListener('click',()=>{const opening=!root.classList.contains('menu-open');root.classList.toggle('menu-open',opening);document.body.classList.toggle('menu-lock',opening);toggle.setAttribute('aria-expanded',String(opening));if(opening)nav.querySelector('a,button')?.focus();else closeSolutions()});
  solutionsTrigger?.addEventListener('click',()=>setSolutions(solutionsPanel.hidden));
  solutionsTrigger?.addEventListener('keydown',event=>{if(event.key==='ArrowDown'){event.preventDefault();setSolutions(true)}});
  solutionsPanel?.addEventListener('keydown',event=>{const links=[...solutionsPanel.querySelectorAll('a')],index=links.indexOf(document.activeElement);if(event.key==='ArrowDown'||event.key==='ArrowUp'){event.preventDefault();links[(index+(event.key==='ArrowDown'?1:-1)+links.length)%links.length].focus()}});
  nav?.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeMenu));
  document.addEventListener('click',event=>{if(solutions&&!solutions.contains(event.target))closeSolutions()});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){if(solutions?.classList.contains('is-open'))closeSolutions(true);else{closeMenu();toggle?.focus()}}});
}
document.querySelectorAll('[data-faq-search]').forEach(input=>input.addEventListener('input',event=>{const query=event.target.value.trim().toLowerCase();document.querySelectorAll('[data-faq-item]').forEach(item=>{item.hidden=!item.textContent.toLowerCase().includes(query)})}));
document.querySelectorAll('[data-plan-toggle]').forEach(button=>button.addEventListener('click',()=>{const cycle=button.dataset.planToggle;document.querySelectorAll('[data-plan-toggle]').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));document.querySelectorAll('[data-plan-price]').forEach(item=>{item.hidden=item.dataset.planPrice!==cycle})}));

document.querySelectorAll('[data-home-hero-slider]').forEach(carousel=>{
  const slides=[...carousel.querySelectorAll('[data-hero-slide]')],dots=[...carousel.querySelectorAll('[data-carousel-dot]')],controls=carousel.querySelector('[data-carousel-controls]'),toggle=carousel.querySelector('[data-carousel-toggle]'),status=carousel.querySelector('[data-carousel-status]'),reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(slides.length<2)return;
  const playIcon=toggle.querySelector('[data-carousel-play-icon]'),pauseIcon=toggle.querySelector('[data-carousel-pause-icon]');
  let current=0,timer=null,userPaused=reduced,hovered=false,focused=false,touchStart=null;
  controls.hidden=false;carousel.classList.add('carousel-ready');
  const renderToggle=()=>{toggle.setAttribute('aria-label',userPaused?'Play slideshow':'Pause slideshow');toggle.setAttribute('aria-pressed',String(userPaused));playIcon.hidden=!userPaused;pauseIcon.hidden=userPaused};
  const render=(next,announce=false)=>{slides[current].hidden=true;dots[current].removeAttribute('aria-current');current=(next+slides.length)%slides.length;slides[current].hidden=false;dots[current].setAttribute('aria-current','true');if(announce)status.textContent=`Slide ${current+1} of ${slides.length}`};
  const stop=()=>{if(timer){window.clearInterval(timer);timer=null}};
  const start=()=>{stop();if(!userPaused&&!hovered&&!focused&&!document.hidden)timer=window.setInterval(()=>render(current+1),Number(carousel.dataset.interval)||5500)};
  const manual=next=>{userPaused=true;stop();renderToggle();render(next,true)};
  carousel.querySelector('[data-carousel-prev]').addEventListener('click',()=>manual(current-1));carousel.querySelector('[data-carousel-next]').addEventListener('click',()=>manual(current+1));dots.forEach((dot,index)=>dot.addEventListener('click',()=>manual(index)));
  toggle.addEventListener('click',()=>{userPaused=!userPaused;renderToggle();userPaused?stop():start()});
  carousel.addEventListener('mouseenter',()=>{hovered=true;stop()});carousel.addEventListener('mouseleave',()=>{hovered=false;start()});carousel.addEventListener('focusin',()=>{focused=true;stop()});carousel.addEventListener('focusout',event=>{if(!carousel.contains(event.relatedTarget)){focused=false;start()}});
  carousel.addEventListener('keydown',event=>{if(event.key==='ArrowLeft'){event.preventDefault();manual(current-1)}if(event.key==='ArrowRight'){event.preventDefault();manual(current+1)}});
  carousel.addEventListener('touchstart',event=>{touchStart=event.changedTouches[0].clientX},{passive:true});carousel.addEventListener('touchend',event=>{if(touchStart===null)return;const distance=event.changedTouches[0].clientX-touchStart;touchStart=null;if(Math.abs(distance)>45)manual(current+(distance<0?1:-1))},{passive:true});
  document.addEventListener('visibilitychange',()=>document.hidden?stop():start());renderToggle();start();
});
