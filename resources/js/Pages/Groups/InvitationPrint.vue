<script setup>
import { Head } from '@inertiajs/vue3'
import QRCode from 'qrcode'
import { onMounted, ref } from 'vue'

const props=defineProps({group:Object,invitation:Object})
const qr=ref('')
onMounted(async()=>{qr.value=await QRCode.toDataURL(props.invitation.share_url,{width:420,margin:2,errorCorrectionLevel:'H'})})
function printPage(){window.print()}
</script>

<template><Head :title="`Join ${group.name}`"/><button type="button" class="print-button" :disabled="!qr" @click="printPage">Print</button><main class="sheet"><div class="logo">Lens<span>Pic</span></div><h1>Join {{group.name}}</h1><p>Scan to open the secure Group invitation</p><div class="qr-wrap"><span v-if="!qr">Preparing QR code…</span><img v-else :src="qr" alt="QR code for Group invitation"></div><p>Invitation code</p><div class="code">{{invitation.code}}</div><p class="instructions">Open the invitation, enter the code if requested, confirm your access, and browse the photos shared with you.</p></main></template>

<style scoped>
@page{size:A4;margin:14mm}*{box-sizing:border-box}.sheet{min-height:260mm;border:3px solid #4f46e5;border-radius:28px;padding:24mm;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:Arial,sans-serif;color:#111827}.logo{font-size:28px;font-weight:800}.logo span{color:#4f46e5}h1{font-size:34px;margin:20px 0 5px}.qr-wrap{display:grid;place-items:center;width:360px;max-width:80%;min-height:360px;margin:24px}.qr-wrap img{width:100%}.code{font:800 34px monospace;letter-spacing:7px;background:#eef2ff;padding:14px 22px;border-radius:14px}.instructions{font-size:17px;line-height:1.6;max-width:520px}.print-button{position:fixed;right:20px;top:20px;padding:12px 18px}@media print{.print-button{display:none}.sheet{border:0}}@media(max-width:600px){.sheet{padding:20px;min-height:100vh}.qr-wrap{min-height:260px}}
</style>
