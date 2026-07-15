export function createDetailCarousel(root, items, onChange) {
  let active = 0; let paused = matchMedia('(prefers-reduced-motion: reduce)').matches; let timer;
  const tabs = root.querySelector('[data-detail-tabs]'); const panel = root.querySelector('[data-detail-panel]'); const dots = root.querySelector('[data-detail-dots]'); const pause = root.querySelector('[data-detail-pause]');
  tabs.innerHTML = items.map((item, index) => `<button type="button" role="tab" data-index="${index}" aria-selected="${index===0}"><span>${item.label}</span><strong>${item.name}</strong><small>${item.effect}</small></button>`).join('');
  dots.innerHTML = items.map((_, index) => `<button type="button" data-index="${index}" aria-label="Ir para peça ${index + 1}"></button>`).join('');
  function render(user=false){ const item=items[active]; tabs.querySelectorAll('button').forEach((b,i)=>{b.classList.toggle('is-active',i===active); b.setAttribute('aria-selected',i===active?'true':'false')}); dots.querySelectorAll('button').forEach((b,i)=>b.classList.toggle('is-active',i===active)); panel.innerHTML=`<p class="kicker">${item.label}</p><h3>${item.name}</h3><p>${item.summary}</p><p><strong>Efeito:</strong> ${item.effect}</p>`; onChange?.(item,user); }
  function go(index,user=true){ active=(index+items.length)%items.length; render(user); restart(); }
  root.addEventListener('click',(event)=>{ const button=event.target.closest('button'); if(!button) return; if(button.dataset.index) go(Number(button.dataset.index)); if(button.matches('[data-detail-prev]')) go(active-1); if(button.matches('[data-detail-next]')) go(active+1); if(button.matches('[data-detail-pause]')) { paused=!paused; pause.textContent=paused?'Reproduzir':'Pausar'; restart(); }});
  root.addEventListener('keydown',(event)=>{ if(event.key==='ArrowDown'||event.key==='ArrowRight'){event.preventDefault();go(active+1)} if(event.key==='ArrowUp'||event.key==='ArrowLeft'){event.preventDefault();go(active-1)} });
  root.addEventListener('mouseenter',()=>{paused=true; restart();}); root.addEventListener('focusin',()=>{paused=true; restart();});
  function restart(){ clearInterval(timer); if(!paused) timer=setInterval(()=>go(active+1,false),6500); }
  render(); restart(); return { go };
}
