<script setup>
import {nextTick,onBeforeUnmount,watch} from 'vue'
const props=defineProps({open:Boolean,busy:Boolean,title:String,trigger:Object}),emit=defineEmits(['close'])
function close(){if(props.busy&&!confirm('Cancel the active upload and close this window?'))return;emit('close')}
function keydown(e){if(e.key==='Escape')close();if(e.key==='Tab'){const controls=[...e.currentTarget.querySelectorAll('button,input,select,[tabindex]:not([tabindex="-1"])')].filter(x=>!x.disabled);if(!controls.length)return;const first=controls[0],last=controls.at(-1);if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}}}
watch(()=>props.open,async value=>{if(value){await nextTick();document.querySelector('[data-upload-modal] button')?.focus();document.body.style.overflow='hidden'}else{document.body.style.overflow='';props.trigger?.focus?.()}})
onBeforeUnmount(()=>document.body.style.overflow='')
</script>
<template><Teleport to="body"><div v-if="open" class="fixed inset-0 z-[120] grid items-end bg-black/55 sm:place-items-center sm:p-6" @mousedown.self="close"><section data-upload-modal role="dialog" aria-modal="true" :aria-label="title" class="flex max-h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-2xl sm:max-h-[90vh] sm:max-w-4xl" @keydown="keydown"><slot :close="close"/></section></div></Teleport></template>
