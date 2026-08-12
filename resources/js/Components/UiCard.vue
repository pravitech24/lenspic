<script setup>defineProps({hover:Boolean,padded:{type:Boolean,default:true}})</script><template><section :class="['lp-card',hover&&'lp-card-hover',padded&&'p-5 md:p-6']"><slot/></section></template>
