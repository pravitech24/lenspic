<script setup>
import AppShell from '../../Layouts/AppShell.vue'
import PageHeader from '../../Components/PageHeader.vue'
import StateCard from '../../Components/StateCard.vue'
import { Head, Link, router } from '@inertiajs/vue3'
defineProps({ groups: { type: Array, default: () => [] } })
const initials = name => name.split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase()
const date = value => value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value)) : 'Date not set'
const remove = group => { if (confirm(`Delete “${group.name}”? This cannot be undone.`)) router.delete(`/groups/${group.id}`) }
</script>

<template><AppShell><Head title="Groups"/><div class="lp-page">
  <PageHeader eyebrow="Groups" title="Your galleries" description="Private, beautifully delivered photo collections."><Link v-if="$page.props.permissions?.createGroups" href="/groups/create" class="btn-primary">Create Group</Link></PageHeader>
  <StateCard v-if="!groups.length" class="mt-8" title="Create your first Group" message="Start a private gallery for your next event or photoshoot."><Link v-if="$page.props.permissions?.createGroups" href="/groups/create" class="btn-primary mt-5">Create Group</Link></StateCard>
  <div v-else class="mt-8 grid gap-x-5 gap-y-7 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    <article v-for="group in groups" :key="group.id" class="group relative border border-slate-200 bg-white shadow-sm">
      <Link :href="`/groups/${group.id}/gallery`" class="block" :aria-label="`Open ${group.name} gallery`"><div class="h-[130px] overflow-hidden bg-gradient-to-br from-ink via-indigo to-coral/70 md:h-[140px] xl:h-[160px]"><img v-if="group.cover.url" :src="group.cover.url" :alt="`Cover photo for ${group.name}`" loading="lazy" class="h-full w-full object-cover object-center" @error="group.cover.url=null"><div v-else class="flex h-full items-center justify-center text-4xl font-bold tracking-tight text-white">{{ initials(group.name) }}</div></div></Link>
      <div class="p-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><Link :href="`/groups/${group.id}/gallery`" class="block truncate text-base font-bold hover:text-indigo">{{ group.name }}</Link><p class="mt-1 text-xs text-slate-500">{{ date(group.event_date) }}</p></div>
        <details class="relative"><summary class="grid h-9 w-9 cursor-pointer list-none place-items-center text-xl text-slate-500 hover:bg-slate-100" aria-label="Group actions">•••</summary><div class="absolute right-0 z-20 mt-1 w-52 border border-slate-200 bg-white p-1 text-sm shadow-xl"><Link :href="`/groups/${group.id}/gallery`" class="block px-3 py-2 hover:bg-slate-50">Open gallery</Link><Link :href="`/groups/${group.id}/operations`" class="block px-3 py-2 hover:bg-slate-50">Upload & downloads</Link><Link :href="`/groups/${group.id}/members`" class="block px-3 py-2 hover:bg-slate-50">Participants & invites</Link><Link :href="`/groups/${group.id}/folders`" class="block px-3 py-2 hover:bg-slate-50">Manage folders</Link><Link :href="`/groups/${group.id}/settings`" class="block px-3 py-2 hover:bg-slate-50">Group settings</Link><button class="block w-full px-3 py-2 text-left text-red-700 hover:bg-red-50" @click="remove(group)">Delete group</button></div></details>
      </div><div class="mt-4 grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100 py-3 text-center"><div><strong class="block text-sm">{{ group.photos_count }}</strong><span class="text-[11px] text-slate-500">Photos</span></div><div><strong class="block text-sm">{{ group.members_count }}</strong><span class="text-[11px] text-slate-500">People</span></div><div><strong class="block text-sm">{{ group.folders_count }}</strong><span class="text-[11px] text-slate-500">Folders</span></div></div>
      <div class="mt-3 flex items-center justify-between gap-3 text-[11px] text-slate-500"><span>{{ group.privacy || 'private' }} · {{ group.is_active ? 'Active' : 'Archived' }}</span><span>Updated {{ date(group.updated_at) }}</span></div><p v-if="['queued','processing'].includes(group.cover.state)" class="mt-2 text-xs font-medium text-indigo">Cover processing…</p><p class="mt-2 truncate text-xs text-slate-500">Album by {{ group.owner_name || 'LensPic studio' }}</p></div>
    </article>
  </div>
</div></AppShell></template>
