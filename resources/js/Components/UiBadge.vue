<script setup>defineProps({tone:{type:String,default:'info'}})</script><template><span :class="`lp-badge-${tone}`"><slot/></span></template>
