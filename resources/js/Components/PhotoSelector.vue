<script setup>
defineProps({ checked:Boolean, label:{type:String,required:true}, visible:Boolean })
defineEmits(['change'])
</script>
<template><label :class="['photo-select absolute left-1 top-1 z-10 grid h-11 w-11 cursor-pointer place-items-center transition-opacity',visible||checked?'opacity-100':'opacity-0 group-hover/photo:opacity-100 group-focus-within/photo:opacity-100']" @click.stop @mousedown.stop @touchstart.stop>
  <input type="checkbox" class="peer sr-only" :checked="checked" :aria-label="label" @click.stop @change.stop="$emit('change',$event.target.checked)">
  <span :class="['grid h-7 w-7 place-items-center overflow-hidden rounded-[7px] border-[1.5px] shadow-[0_3px_12px_rgba(0,0,0,.28)] backdrop-blur-md transition motion-reduce:transition-none peer-focus-visible:ring-2 peer-focus-visible:ring-white peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-indigo md:h-[26px] md:w-[26px]',checked?'scale-105 border-indigo bg-indigo text-white ring-4 ring-indigo/25':'border-white/95 bg-slate-950/40 text-transparent hover:scale-105 hover:bg-white']" aria-hidden="true">
    <svg v-if="checked" viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="m4.5 10.2 3.3 3.3 7.7-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </span>
</label></template>
