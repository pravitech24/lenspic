<script setup>
import AppShell from '../Layouts/AppShell.vue'
import PageHeader from '../Components/PageHeader.vue'
import UiButton from '../Components/UiButton.vue'
import UiCard from '../Components/UiCard.vue'
import { Head, useForm } from '@inertiajs/vue3'
const props=defineProps({step:String,user:Object,accountType:String,profileDefaults:Object})
const role=useForm({role:null})
const profile=useForm({name:'',first_name:'',last_name:'',company_name:'',email:'',...props.profileDefaults})
</script>
<template>
<AppShell><Head title="Onboarding"/><div class="lp-page-narrow">
<PageHeader eyebrow="Studio setup" title="Shape your LensPic workspace" description="A short setup keeps your workspace and delivery experience relevant."/>
<UiCard class="mt-8">
    <template v-if="step==='role'">
        <h2 class="text-xl font-bold">How will you use LensPic?</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2"><button v-for="r in ['photographer','user']" :key="r" type="button" :aria-pressed="role.role===r" :class="['rounded-2xl border p-6 text-left',role.role===r?'border-indigo bg-indigo/5':'border-slate-200 hover:border-slate-300']" @click="role.role=r"><b class="capitalize">{{r}}</b><p class="mt-2 text-sm text-slate-600">{{r==='photographer'?'Create Groups and deliver galleries.':'Join Groups and discover your photos.'}}</p></button></div>
        <p v-if="role.errors.role" class="lp-error" role="alert">{{role.errors.role}}</p>
        <UiButton class="mt-6" :disabled="!role.role || role.processing" :loading="role.processing" @click="role.post('/onboarding/account-type')">Continue</UiButton>
    </template>
    <form v-else @submit.prevent="profile.post('/onboarding/profile')">
        <h2 class="text-xl font-bold">{{accountType==='photographer'?'Complete your studio profile':'Complete your profile'}}</h2>
        <template v-if="accountType==='photographer'">
            <div class="mt-5 grid gap-4 sm:grid-cols-2"><label class="lp-label">First name<input v-model="profile.first_name" class="field mt-2" required></label><label class="lp-label">Last name<input v-model="profile.last_name" class="field mt-2" required></label></div>
            <label class="lp-label mt-4 block">Studio name<input v-model="profile.company_name" class="field mt-2" required></label>
        </template>
        <label v-else class="lp-label mt-5 block">Name<input v-model="profile.name" class="field mt-2" required></label>
        <label class="lp-label mt-4 block">{{accountType==='photographer'?'Studio email':'Email'}}<input v-model="profile.email" class="field mt-2" type="email" :required="accountType==='photographer' || !!user.email_verified_at" :readonly="!!user.email_verified_at"></label>
        <p v-for="(error,key) in profile.errors" :key="key" class="lp-error" role="alert">{{error}}</p>
        <UiButton type="submit" class="mt-6" :disabled="profile.processing" :loading="profile.processing">Finish setup</UiButton>
    </form>
</UiCard></div></AppShell>
</template>
