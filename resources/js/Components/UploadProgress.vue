<script setup>
import {computed} from 'vue'
const props=defineProps({value:{type:Number,default:0},label:String,detail:String,tone:{type:String,default:'brand'}})
const progress=computed(()=>Math.max(0,Math.min(100,Math.round(props.value))))
</script>
<template><div><div class="mb-2 flex items-end justify-between gap-4"><div><p class="text-sm font-semibold text-ink">{{label}}</p><p v-if="detail" class="mt-0.5 text-xs text-slate-500" aria-live="polite">{{detail}}</p></div><span class="text-sm font-semibold tabular-nums">{{progress}}%</span></div><div role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100" :aria-label="label" class="h-2 overflow-hidden rounded-full bg-slate-200"><div :class="['h-full rounded-full transition-[width] duration-300',tone==='success'?'bg-emerald-500':tone==='warning'?'bg-amber-500':'bg-indigo']" :style="{width:`${progress}%`}"></div></div></div></template>
