<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthShell from '../../Components/AuthShell.vue'
import UiButton from '../../Components/UiButton.vue'
const props = defineProps({ defaultCountryCode:String, whatsappEnabled:Boolean, requestReference:String, defaultChannel:String })
const initialChannel = props.defaultChannel === 'mobile' ? (props.whatsappEnabled ? 'whatsapp' : 'email') : props.defaultChannel
const form = useForm({ request_id:props.requestReference, channel:initialChannel || 'email', email:'', country_code:props.defaultCountryCode || '+91', phone:'' })
const channels = [{value:'whatsapp',label:'WhatsApp'}, {value:'sms',label:'SMS'}, {value:'email',label:'Email'}]
function submit() {
    if (form.processing) return
    form.post('/send-otp', { onError:()=>{ form.request_id=crypto.randomUUID() } })
}
</script>
<template>
<Head title="Create account"/>
<AuthShell eyebrow="Join LensPic" title="Create your secure workspace" description="Verify one contact method to continue.">
    <div class="grid grid-cols-3 rounded-xl bg-slate-100 p-1" role="group" aria-label="Verification method">
        <button v-for="channel in channels" :key="channel.value" type="button" :aria-pressed="form.channel===channel.value" :disabled="form.processing" :class="['rounded-lg py-2.5 text-sm font-semibold',form.channel===channel.value?'bg-white text-indigo shadow-sm':'text-slate-500 hover:text-ink']" @click="form.channel=channel.value;form.clearErrors()">{{channel.label}}</button>
    </div>
    <form class="mt-6 space-y-5" :aria-busy="form.processing" @submit.prevent="submit">
        <label v-if="form.channel==='email'" class="lp-label">Email address<input v-model="form.email" type="email" autocomplete="email" class="field mt-2" required></label>
        <div v-else class="grid grid-cols-[100px_1fr] gap-3">
            <label class="lp-label">Code<input v-model="form.country_code" class="field mt-2" autocomplete="tel-country-code" inputmode="tel"></label>
            <label class="lp-label">Mobile<input v-model="form.phone" type="tel" class="field mt-2" autocomplete="tel" required></label>
        </div>
        <p v-if="form.channel==='whatsapp'" class="text-sm text-slate-600">By requesting a verification code, you agree to receive a one-time authentication message on WhatsApp.</p>
        <p v-if="form.channel==='sms'" class="text-sm text-slate-600">Request a one-time authentication code by SMS. This does not subscribe you to marketing messages.</p>
        <p v-if="Object.keys(form.errors).length" class="lp-error" role="alert">{{Object.values(form.errors)[0]}}</p>
        <p v-if="form.processing" role="status" class="text-sm text-slate-600">Requesting verification code…</p>
        <UiButton type="submit" class="w-full" :disabled="form.processing" :loading="form.processing">Continue securely</UiButton>
        <p class="text-center text-sm text-slate-600">Already registered? <Link href="/login" class="font-semibold text-indigo hover:underline">Sign in</Link></p>
    </form>
</AuthShell>
</template>
