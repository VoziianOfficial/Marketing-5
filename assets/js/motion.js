(()=>{'use strict';const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;

function prepareTitle(el){if(reduced||el.dataset.wordsReady)return;el.dataset.wordsReady='true';let i=0;const walker=document.createTreeWalker(el,NodeFilter.SHOW_TEXT),nodes=[];while(walker.nextNode())nodes.push(walker.currentNode);nodes.forEach(node=>{const frag=document.createDocumentFragment();node.textContent.split(/(\s+)/).forEach(word=>{if(!word.trim()){frag.append(document.createTextNode(word));return}const mask=document.createElement('span'),inner=document.createElement('span');mask.className='word-mask';mask.style.setProperty('--word',Math.min(i++,10));inner.textContent=word;mask.append(inner);frag.append(mask)});node.replaceWith(frag)})}
if(!reduced&&'IntersectionObserver'in window){const titles=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('text-entered');titles.unobserve(e.target)}}),{threshold:.1});$$('h1,h2').forEach(el=>{prepareTitle(el);titles.observe(el)});const hero=$('.hero-image');if(hero)new MutationObserver(()=>{const title=$('.hero h1');delete title.dataset.wordsReady;title.classList.remove('text-entered');prepareTitle(title);requestAnimationFrame(()=>requestAnimationFrame(()=>title.classList.add('text-entered')))}).observe(hero,{attributes:true,attributeFilter:['src']})}
const bar=document.createElement('span');bar.className='scroll-indicator';bar.setAttribute('aria-hidden','true');$('header').append(bar);let pending=false;const parallax=$$('[data-parallax]');function updateScroll(){pending=false;const total=document.documentElement.scrollHeight-innerHeight;bar.style.setProperty('--read-progress',total>0?Math.min(1,scrollY/total):0);if(!reduced&&innerWidth>700)parallax.forEach(el=>{const r=el.getBoundingClientRect();if(r.bottom>0&&r.top<innerHeight){el.classList.add('parallax-active');el.style.setProperty('--parallax',Math.max(-22,Math.min(22,(innerHeight/2-r.top-r.height/2)*.05))+'px')}})}addEventListener('scroll',()=>{if(!pending){pending=true;requestAnimationFrame(updateScroll)}},{passive:true});addEventListener('resize',updateScroll);updateScroll();
const examples=[['thoughtful everyday essentials','Meet your new everyday favourites.','Considered essentials, made for the way you live. Find something that feels like you.','Explore the collection','Our approach'],['everyday essentials materials and care','Good materials. All the useful details.','Get to know the design, materials and care behind each piece. Find the fit that works for you.','Materials & care','Find your fit'],['the collection you had your eye on','Still on your mind? Take another look.','Your favourites are worth a second look. Explore the collection and pick up where you left off.','Revisit the collection','A little inspiration']];
$$('[data-workshop]').forEach(section=>{const buttons=$$('[data-intent]',section);buttons.forEach((b,i)=>b.addEventListener('click',()=>{buttons.forEach(x=>{x.classList.toggle('active',x===b);x.setAttribute('aria-pressed',x===b)});const data=examples[i];$('.demo-query',section).textContent=data[0];$('.demo-title',section).textContent=data[1];$('.demo-description',section).textContent=data[2];$$('.demo-sitelinks span',section).forEach((x,j)=>x.textContent=data[j+3]);const ad=$('.ad-example',section);ad.classList.remove('updating');requestAnimationFrame(()=>ad.classList.add('updating'))}));if(section.dataset.workshop==='return')buttons[2].click()});
$$('.principle').forEach(button=>button.addEventListener('click',()=>{const open=button.getAttribute('aria-expanded')!=='true';button.setAttribute('aria-expanded',open);$('.principle-back',button).setAttribute('aria-hidden',!open);$('.principle-front',button).setAttribute('aria-hidden',open)}));
$$('[data-readiness]').forEach(section=>{const inputs=$$('input',section),status=$('.readiness-result',section);inputs.forEach(input=>input.addEventListener('change',()=>{const selected=inputs.filter(x=>x.checked).length;status.textContent=selected===0?'Select what you already have in place.':selected===inputs.length?'A useful starting point. Let’s review the details together.':'A good place to begin. We can work through the remaining pieces together.'}))});
const message=$('textarea[name=message]');if(message)message.addEventListener('input',()=>{$('[data-char-count]').textContent=message.value.length+' / 3000'});

$$('.carousel').forEach(track=>{let start=0,left=0,active=false;track.addEventListener('pointerdown',e=>{if(e.pointerType!=='mouse'||e.button!==0)return;active=true;start=e.clientX;left=track.scrollLeft;track.setPointerCapture(e.pointerId);track.classList.add('dragging')});track.addEventListener('pointermove',e=>{if(active)track.scrollLeft=left-(e.clientX-start)});const stop=()=>{active=false;track.classList.remove('dragging')};track.addEventListener('pointerup',stop);track.addEventListener('pointercancel',stop);track.addEventListener('dragstart',e=>e.preventDefault());track.addEventListener('keydown',e=>{if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();track.scrollBy({left:(e.key==='ArrowRight'?1:-1)*(track.firstElementChild.offsetWidth+20),behavior:reduced?'instant':'smooth'})}})});
const creativeTrack=$('.creative-gallery .creative-track');
if(creativeTrack){
    const originals=$$('.creative-tile',creativeTrack);
    let index=originals.length,timer,busy=false,dragStart=0,dragOffset=0,dragging=false,stepWidth=0;
    if(originals.length){
        originals.forEach(tile=>{const clone=tile.cloneNode(true);clone.setAttribute('aria-hidden','true');creativeTrack.append(clone)});
        [...originals].reverse().forEach(tile=>{const clone=tile.cloneNode(true);clone.setAttribute('aria-hidden','true');creativeTrack.prepend(clone)});
        const tiles=$$('.creative-tile',creativeTrack);
        const setPosition=(animate=true)=>{
            const gap=parseFloat(getComputedStyle(creativeTrack).gap)||0;
            stepWidth=tiles[0].getBoundingClientRect().width+gap;
            creativeTrack.style.transition=animate&&!reduced?'transform .62s cubic-bezier(.22,.72,.24,1)':'none';
            creativeTrack.style.transform=`translateX(${-index*stepWidth+dragOffset}px)`;
        };
        const normalize=()=>{
            if(index>=originals.length*2){index=originals.length;setPosition(false)}
            else if(index<originals.length){index=originals.length*2-1;setPosition(false)}
            busy=false;
        };
        const go=()=>{if(busy)return;busy=true;index+=1;setPosition();if(reduced)normalize()};
        const start=()=>{if(reduced||timer)return;timer=setInterval(()=>{if(!document.hidden&&!creativeTrack.matches(':hover,:focus-within'))go()},2800)};
        const stop=()=>{clearInterval(timer);timer=null};
        creativeTrack.addEventListener('pointerdown',e=>{
            if(e.pointerType!=='mouse'||e.button!==0)return;
            stop();
            dragging=true;
            busy=false;
            dragStart=e.clientX;
            dragOffset=0;
            creativeTrack.setPointerCapture(e.pointerId);
            creativeTrack.classList.add('dragging');
            setPosition(false);
        });
        creativeTrack.addEventListener('pointermove',e=>{
            if(!dragging)return;
            dragOffset=e.clientX-dragStart;
            setPosition(false);
        });
        const endDrag=()=>{
            if(!dragging)return;
            const threshold=Math.min(120,stepWidth*.28);
            if(dragOffset<-threshold)index+=1;
            else if(dragOffset>threshold)index-=1;
            dragging=false;
            dragOffset=0;
            busy=true;
            creativeTrack.classList.remove('dragging');
            setPosition();
            start();
            if(reduced)normalize();
        };
        creativeTrack.addEventListener('pointerup',endDrag);
        creativeTrack.addEventListener('pointercancel',endDrag);
        creativeTrack.addEventListener('dragstart',e=>e.preventDefault());
        creativeTrack.addEventListener('keydown',e=>{
            if(e.key!=='ArrowRight'&&e.key!=='ArrowLeft')return;
            e.preventDefault();
            stop();
            if(!busy){busy=true;index+=e.key==='ArrowRight'?1:-1;setPosition();if(reduced)normalize()}
            start();
        });
        creativeTrack.addEventListener('transitionend',e=>{if(e.target===creativeTrack)normalize()});
        creativeTrack.addEventListener('pointerenter',stop);
        creativeTrack.addEventListener('pointerleave',start);
        creativeTrack.addEventListener('focusin',stop);
        creativeTrack.addEventListener('focusout',start);
        addEventListener('resize',()=>setPosition(false));
        setPosition(false);
        start();
    }
}

if('IntersectionObserver'in window){const motionObserver=new IntersectionObserver(entries=>entries.forEach(e=>e.target.style.animationPlayState=e.isIntersecting?'running':'paused'));$$('.hero-visual,.hero-float,.file-chip,.ticker>div').forEach(el=>motionObserver.observe(el))}
})();
(()=>{const buttons=[...document.querySelectorAll('[data-hero-art]')],image=document.querySelector('.service-hero .hero-visual>img');if(!image)return;let timer;buttons.forEach(button=>button.addEventListener('click',()=>{clearTimeout(timer);buttons.forEach(b=>{b.classList.toggle('active',b===button);b.setAttribute('aria-pressed',b===button)});image.classList.add('art-changing');timer=setTimeout(()=>{image.src='assets/images/'+button.dataset.heroArt+'.webp';image.alt=button.textContent+' campaign illustration';image.classList.remove('art-changing')},matchMedia('(prefers-reduced-motion: reduce)').matches?0:220)}))})();

const contactForm=document.querySelector('.designed-form');if(contactForm)contactForm.addEventListener('reset',()=>{const count=document.querySelector('[data-char-count]');if(count)count.textContent='0 / 3000'});
