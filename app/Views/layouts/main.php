<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title><?=e($config['app_name'])?></title><link rel="icon" href="/logo.jpg"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/assets/css/app.css"></head><body><div class="topbar">Compra fácil · Gestión segura · Entrega a domicilio</div><nav class="navbar custom-navbar sticky-top"><div class="container nav-wrap"><a class="navbar-brand d-flex align-items-center gap-2" href="/"><img src="/logo.jpg" class="logo"><span class="brand">MiMarket</span></a><form class="searchbar" action="/" method="get"><input type="hidden" name="r" value="catalog"><input name="search" placeholder="¿Qué estás buscando?" value="<?=e($_GET['search']??'')?>"><button>Buscar</button></form><div class="nav-actions"><a class="navpill" href="/">Catálogo</a><a class="navpill" href="/?r=cart">Carrito <b data-cart-count><?=array_sum($_SESSION['cart']??[])?></b></a><?php if(auth()):?><a class="navpill" href="/?r=orders">Pedidos</a><a class="navpill" href="/?r=profile">Mi cuenta</a><?php if(staff()):?><a class="navpill adminpill" href="/?r=admin">Administración</a><?php endif;?><span class="hello">Hola, <?=e(auth()['name'])?></span><form method="post" action="/?r=logout" class="d-inline"><input type="hidden" name="csrf" value="<?=csrf()?>"><button class="navpill border-0">Salir</button></form><?php else:?><a class="navpill" href="/?r=login">Ingresar</a><a class="btn grad" href="/?r=register">Crear cuenta</a><?php endif;?></div></div></nav><?php if(staff()):?><div class="adminnav"><div class="container adminnav-scroll"><a href="/?r=admin">Resumen</a><a href="/?r=adminProducts">Productos</a><a href="/?r=categories">Categorías</a><a href="/?r=inventory">Inventario</a><a href="/?r=adminOrders">Pedidos</a><a href="/?r=reports">Reportes</a><?php if(admin()):?><a href="/?r=users">Usuarios y roles</a><?php endif;?></div></div><?php endif;?><main class="container py-4 app-main"><?php if(!empty($_SESSION['flash'])):?><div class="alert alert-info shadow-sm"><?=e($_SESSION['flash']);unset($_SESSION['flash']);?></div><?php endif;?><?=$content?></main><footer><b>MiMarket</b><br><small>Compra, inventario y gestión de pedidos en un solo lugar.</small></footer><script>
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('input[type="text"],input[type="email"],input:not([type]),textarea').forEach(el=>{if(!el.maxLength||el.maxLength<0)el.maxLength=el.tagName==='TEXTAREA'?1000:120;});

 // Cascada con búsqueda integrada: el filtro vive dentro del mismo control visual.
 document.querySelectorAll('select.searchable-select').forEach((sel)=>{
   if(sel.dataset.enhanced==='1') return;
   sel.dataset.enhanced='1';
   sel.classList.add('native-search-select');
   const shell=document.createElement('div'); shell.className='smart-select';
   const trigger=document.createElement('button'); trigger.type='button'; trigger.className='smart-select-trigger';
   const label=document.createElement('span');
   const arrow=document.createElement('span'); arrow.className='smart-select-arrow'; arrow.textContent='⌄';
   trigger.append(label,arrow);
   const panel=document.createElement('div'); panel.className='smart-select-panel';
   const searchRow=document.createElement('div'); searchRow.className='smart-select-search-row';
   const search=document.createElement('input'); search.type='search'; search.className='smart-select-search'; search.placeholder='Escribe para buscar...'; search.maxLength=80;
   const clear=document.createElement('button'); clear.type='button'; clear.className='smart-select-clear'; clear.textContent='×'; clear.setAttribute('aria-label','Limpiar búsqueda');
   searchRow.append(search,clear);
   const options=document.createElement('div'); options.className='smart-select-options';
   panel.append(searchRow,options); shell.append(trigger,panel); sel.parentNode.insertBefore(shell,sel); shell.appendChild(sel);
   const syncLabel=()=>{const o=sel.options[sel.selectedIndex];label.textContent=o?o.text:'Seleccionar';};
   const render=()=>{
     const q=search.value.toLowerCase().trim(); options.innerHTML='';
     [...sel.options].forEach((o,idx)=>{if(q && !o.text.toLowerCase().includes(q))return; const b=document.createElement('button');b.type='button';b.className='smart-select-option'+(idx===sel.selectedIndex?' active':'');b.textContent=o.text;b.addEventListener('click',()=>{sel.selectedIndex=idx;sel.dispatchEvent(new Event('change',{bubbles:true}));syncLabel();shell.classList.remove('open');search.value='';render();});options.appendChild(b);});
     if(!options.children.length){const empty=document.createElement('div');empty.className='smart-select-empty';empty.textContent='Sin coincidencias';options.appendChild(empty);}
   };
   trigger.addEventListener('click',()=>{document.querySelectorAll('.smart-select.open').forEach(x=>{if(x!==shell)x.classList.remove('open')});shell.classList.toggle('open');if(shell.classList.contains('open')){render();setTimeout(()=>search.focus(),0);}});
   search.addEventListener('input',render); clear.addEventListener('click',()=>{search.value='';render();search.focus();});
   document.addEventListener('click',e=>{if(!shell.contains(e.target))shell.classList.remove('open');});
   syncLabel(); render();
 });
});
</script></body></html>