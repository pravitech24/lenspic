<svg class="plan-feature__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('images')<rect x="3" y="5" width="16" height="14" rx="2"/><path d="m3 15 4-4 4 4 2-2 6 6"/><path d="M7 5V3h14v14h-2"/>@break
@case('refresh')<path d="M20 7v5h-5M4 17v-5h5M6.1 9A7 7 0 0 1 18 6l2 2M17.9 15A7 7 0 0 1 6 18l-2-2"/>@break
@case('video')<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/>@break
@case('palette')<path d="M12 3a9 9 0 0 0 0 18h1.3a1.7 1.7 0 0 0 1.2-2.9 1.7 1.7 0 0 1 1.2-2.9H18a3 3 0 0 0 3-3A9 9 0 0 0 12 3Z"/><path d="M7.5 10h.01M10 6.5h.01M14 6.5h.01M17 10h.01"/>@break
@case('heart')<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>@break
@case('download')<path d="M12 3v12m-5-5 5 5 5-5M5 21h14"/>@break
@case('sliders')<path d="M4 6h10M18 6h2M4 12h2M10 12h10M4 18h7M15 18h5"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="13" cy="18" r="2"/>@break
@case('layout-grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('users')<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 4.6"/>@break
@case('chart-users')<path d="M4 20V10M10 20V4M16 20v-7M2 20h15"/><circle cx="19" cy="7" r="2"/><path d="M16.5 14a3 3 0 0 1 5 0"/>@break
@case('stamp')<path d="M8 13h8l1.5 4H6.5zM9 13c.5-2 .5-3-.2-5A3.4 3.4 0 0 1 12 3a3.4 3.4 0 0 1 3.2 5c-.7 2-.7 3-.2 5M5 21h14"/>@break
@case('globe')<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>@break
@case('shield-users')<path d="M12 3 4 6v5c0 5 3.5 8.4 8 10 4.5-1.6 8-5 8-10V6z"/><circle cx="12" cy="10" r="2"/><path d="M8.5 16a3.5 3.5 0 0 1 7 0"/>@break
@case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>@break
@case('share')<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"/>@break
@case('scan-face')<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><circle cx="9" cy="10" r=".5" fill="currentColor"/><circle cx="15" cy="10" r=".5" fill="currentColor"/><path d="M9 15c1.5 1.2 4.5 1.2 6 0"/>@break
@case('headphones')<path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14a2 2 0 0 1 2-2h1v7H6a2 2 0 0 1-2-2zM20 14a2 2 0 0 0-2-2h-1v7h1a2 2 0 0 0 2-2z"/>@break
@case('plus-circle')<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>@break
@case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m8 12 2.7 2.7L16.5 9"/>@break
@endswitch
</svg>
