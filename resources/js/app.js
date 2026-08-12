import '../css/app.css';import{createApp,h}from'vue';import{createInertiaApp}from'@inertiajs/vue3';
createInertiaApp({title:t=>t?`${t} · LensPic`:'LensPic',resolve:n=>{const pages=import.meta.glob('./Pages/**/*.vue',{eager:true});return pages[`./Pages/${n}.vue`]},setup({el,App,props,plugin}){createApp({render:()=>h(App,props)}).use(plugin).mount(el)},progress:{color:'#4f46e5'}});
