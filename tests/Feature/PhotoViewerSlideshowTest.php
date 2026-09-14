<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhotoViewerSlideshowTest extends TestCase
{
    public function test_viewer_uses_one_context_persistent_slideshow_controller(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Photos/Show.vue'));

        $this->assertSame(1, substr_count($source, "Number(preferences.slideshow_interval||5)"));
        $this->assertStringContainsString("target.searchParams.set('slideshow','1')", $source);
        $this->assertStringContainsString("new URLSearchParams(location.search).get('slideshow')==='1'", $source);
        $this->assertStringContainsString('clearSlideshowTimer()', $source);
        $this->assertStringContainsString('props.viewer.next_url||(loop.value?props.viewer.first_url:null)', $source);
        $this->assertStringContainsString("message.value='Slideshow finished'", $source);
    }

    public function test_viewer_suspends_for_visibility_loading_interaction_and_panels(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Photos/Show.vue'));

        foreach (['!tabVisible.value', '!imageReady.value', 'interacting.value', 'infoOpen.value', 'shareOpen.value', 'deleteOpen.value'] as $condition) {
            $this->assertStringContainsString($condition, $source);
        }
        $this->assertStringContainsString("document.addEventListener('visibilitychange',visibilityChanged)", $source);
        $this->assertStringContainsString('@load="imageLoaded"', $source);
        $this->assertStringContainsString('imageSafetyTimer=setTimeout(imageFailed,10000)', $source);
    }

    public function test_close_control_cleans_up_and_returns_to_gallery_safely(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['type="button"','aria-label="Back to gallery"','@click.stop.prevent="close"','if(closing)return','playing.value=false','clearSlideshowTimer()','document.body.style.overflow=previousBodyOverflow',"window.removeEventListener('keydown',keydown)","document.removeEventListener('visibilitychange',visibilityChanged)","document.removeEventListener('fullscreenchange',fullscreenChanged)",'if(document.fullscreenElement)await document.exitFullscreen()',"router.visit(props.event.gallery_url,{replace:true,preserveScroll:true,onError", "router.visit(target,{replace:true,preserveScroll:true"]as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('document.exitFullscreen();return',$source);
    }

    public function test_escape_focus_trap_and_gallery_focus_restoration_are_present(): void
    {
        $viewer=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));$gallery=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        $this->assertStringContainsString("if(e.key==='Escape'){e.preventDefault();close()}",$viewer);
        $this->assertStringContainsString("if(e.key==='Tab')",$viewer);
        $this->assertStringContainsString("history.back()",$viewer);
        $this->assertStringContainsString("sessionStorage.setItem(scrollKey",$gallery);
        $this->assertStringContainsString("focus({preventScroll:true})",$gallery);
    }

    public function test_viewer_has_normal_zoom_swipe_and_zoomed_pan_state_machine(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(["distance>=6","Math.abs(dx)>Math.abs(dy)*1.15","threshold=width*.22","Math.abs(state.velocityX)>=.55","scale.value>1","x.value=resist","y.value=resist","dx*.22","@pointercancel=\"pointerCancel\"","@lostpointercapture=\"pointerCancel\"","@dragstart.prevent","suppressClick=state.moved"]as$expected)$this->assertStringContainsString($expected,$source);
    }

    public function test_viewer_gesture_cleanup_and_autoplay_coordination_are_present(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(["clearSlideshowTimer();gesture=",'releasePointerCapture','clearTimeout(gestureAnimationTimer)',"window.addEventListener('resize',measureFit)","window.removeEventListener('resize',measureFit)",'fitObserver?.disconnect()','interacting.value=false;scheduleSlideshow()','if(navigating||animating.value||!imageReady.value)return']as$expected)$this->assertStringContainsString($expected,$source);
    }

    public function test_normal_zoom_vertical_drag_is_preserved_and_never_navigates(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(["if(gesture.direction==='vertical')","y.value=resist(gesture.originY+dy,bounds.y)","if(state.direction==='vertical')","validY=clamp(y.value","Math.abs(viewerHeight-scaledHeight)/2","y.value=clamp(y.value"]as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString("gesture.direction==='vertical'&&scale.value===1",$source);
    }

    public function test_navigation_arrows_have_independent_clickable_control_zones(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['viewer-nav-zone--previous','viewer-nav-zone--next','viewer-tools--top','viewer-tools--bottom','z-index:30','z-index:40','pointer-events:none','pointer-events:auto','height:52px;width:52px','right:max(10px,env(safe-area-inset-right))','left:max(10px,env(safe-area-inset-left))','aria-label="Next photo"','aria-label="Previous photo"','@pointerdown.stop','@click.stop.prevent="navigate(viewer.next_url)"','@click.stop.prevent="navigate(viewer.previous_url)"']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString("right-3 top-1/2",$source);
    }

    public function test_default_view_uses_per_photo_contain_fit_inside_control_free_stage(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['baseFitScale','fitWidth','fitHeight','widthScale=availableWidth/image.value.naturalWidth','heightScale=availableHeight/image.value.naturalHeight','Math.min(widthScale,heightScale)','ref="imageStage"','viewer-image-stage','object-contain',"maxWidth:'100%'","maxHeight:'100%'","visibility:imageReady?'visible':'hidden'",'new ResizeObserver','fitObserver.observe(imageStage.value)','nextTick(()=>measureFit(scale.value===1))']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('Math.min(widthScale,heightScale,1)',$source);
        $this->assertStringNotContainsString('object-cover',$source);
    }

    public function test_full_viewport_canvas_uses_separate_reference_control_zones(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['.viewer-image-stage{position:absolute;inset:0','viewer-top-left','aria-label="Back to gallery"','viewer-counter','viewer-tools--top','aria-label="View controls"','viewer-tools--bottom','aria-label="Photo actions"','v-if="photo.can_delete"','bottom:calc(max(10px,env(safe-area-inset-bottom))','top:calc(max(10px,env(safe-area-inset-top))']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('viewer-toolbar',$source);
    }

    public function test_delete_control_uses_outline_trash_icon_without_changing_its_action(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['v-if="photo.can_delete"','aria-label="Delete photo"','title="Delete photo"','@click.stop="deleteOpen=true"','viewBox="0 0 24 24"','width="20" height="20"','fill="none"','stroke="currentColor"','stroke-width="2"','aria-hidden="true"']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('>⌫</button>',$source);
    }

    public function test_share_group_and_current_photo_download_use_safe_progressive_actions(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['event.can_share_group','shareData.value?.endpoint','authorizedGroupShare()','shareCurrentGroup()','navigator.share(data)',"error?.name!=='AbortError'",'https://wa.me/?text=${encodeURIComponent(groupShareText())}','navigator.clipboard.writeText(shareData.value.url)','aria-label="Share Group"','title="Share Group"','function downloadCurrentPhoto','const downloadUrl=props.photo.download_url','anchor.download=filename','anchor.click()']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('props.photo.share_url',$source);
    }

    public function test_settings_share_and_download_use_outline_icons_and_separate_handlers(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));
        foreach(['aria-label="Photo settings"','title="Photo settings"','aria-label="Share Group"','title="Share Group"','aria-label="Download photo"','title="Download photo"','@click.stop="infoOpen=!infoOpen"','@click.stop="shareCurrentGroup"','@click.stop.prevent="downloadCurrentPhoto"','m8.6 10.5 6.8-4','m7 10 5 5 5-5','circle cx="12" cy="12" r="3"']as$expected)$this->assertStringContainsString($expected,$source);
    }

    public function test_active_group_gallery_uses_the_corrected_share_download_and_settings_controls(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach(['shareCurrentGroup()','downloadCurrentPhoto(photo)','group.share_endpoint','group.can_share_group','aria-label="Photo settings"','aria-label="Share Group"','aria-label="Download photo"','@click="shareCurrentGroup"','@click.stop.prevent="downloadCurrentPhoto(photo)"','m8.6 10.5 6.8-4','m7 10 5 5 5-5']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('@click="share">↗</button>',$source);
    }

    public function test_share_errors_are_status_specific_and_cancellation_is_not_an_error(): void
    {
        $viewer=file_get_contents(resource_path('js/Pages/Photos/Show.vue'));foreach(["error?.status===401||error?.status===403","error?.status===404","error?.status===409","error?.status===419","error?.name!=='AbortError'",'error.status=response.status']as$expected)$this->assertStringContainsString($expected,$viewer);
        $gallery=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));foreach(["error?.status===401","error?.status===403","error?.status===404","error?.status===409","error?.status===419","error?.status===422","error?.name!=='AbortError'",'error.status=response.status']as$expected)$this->assertStringContainsString($expected,$gallery);foreach([$viewer,$gallery]as$source)$this->assertStringNotContainsString('The Group could not be shared. Check Group Invitations.',$source);
    }

    public function test_gallery_share_contract_and_copy_whatsapp_fallback_use_share_url(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach(['shareOpen.value=true','body.invitation?.share_url','invitation.value=body.invitation','copyGroupLink()','navigator.clipboard.writeText(value)','shareGroupWhatsApp()','https://wa.me/?text=${encodeURIComponent','Gallery Group share request failed','Share Group Invite','Invite Links','Group Invitation QR','QRCode.toDataURL(shareUrl.value','width:1024','anchor.download=qrFilename()','window.print()','qr-print-card','qrOpen.value?closeQr():closeShare()','role="dialog"','aria-modal="true"',"event.key==='Escape'",'document.body.style.overflow']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('The Group could not be shared. Check Group Invitations.',$source);
    }

    public function test_gallery_invite_links_copies_backend_url_with_fallback_and_temporary_feedback(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach(['copyInviteLink()','invitation.value?.share_url','navigator.clipboard?.writeText','navigator.clipboard.writeText(invitation.value.share_url)',"document.createElement('textarea')","document.execCommand('copy')",'textarea.remove()',"inviteCopied.value=true","setTimeout(()=>inviteCopied.value=false,2000)",'Could not copy the link. Please try again.','Gallery invitation copy failed',"errorName:error?.name||'Error'",':disabled="inviteCopying"',"inviteCopied?'Link copied':'Invite Links'",'aria-live="polite"']as$expected)$this->assertStringContainsString($expected,$source);
        $this->assertStringNotContainsString('@click="shareOptionsOpen=!shareOptionsOpen"',$source);
    }

    public function test_qr_copy_confirmation_uses_dedicated_visible_state_and_safe_fallback(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach(["qrCopyStatus=ref('idle')","qrCopyStatus.value='copying'",'navigator.clipboard.writeText(invitation.value.share_url)',"document.createElement('textarea')","document.execCommand('copy')","qrCopyStatus.value='success'","setTimeout(()=>qrCopyStatus.value='idle',2400)","qrCopyStatus.value='error'","qrCopyStatus==='success'",'role="status"','aria-live="polite"','Link copied',"qrCopyStatus==='copying'?'Copying…':qrCopyStatus==='success'?'Copied':'Copy Link'",'Could not copy the link. Please try again.','clearTimeout(qrCopyTimer.value)','qr-copy-feedback','prefers-reduced-motion:reduce']as$expected)$this->assertStringContainsString($expected,$source);
    }

    public function test_qr_download_is_single_click_blob_download_without_navigation(): void
    {
        $source=file_get_contents(resource_path('js/Pages/Groups/Show.vue'));
        foreach(['downloadInvitationQr()','qrDownloadStatus.value=\'downloading\'','fetch(qrData.value)','response.blob()','URL.createObjectURL(blob)','anchor.download=qrFilename()','event=>event.stopPropagation()','anchor.click()','anchor.remove()','URL.revokeObjectURL(completedUrl)','lenspic-${slug}-group-invite-qr.png','lenspic-group-invite-qr.png',"qrDownloadStatus.value='success'","setTimeout(()=>qrDownloadStatus.value='idle',2200)","qrDownloadStatus.value='error'",'QR downloaded','Could not download the QR. Please try again.','@click.stop.prevent="downloadInvitationQr"',"qrDownloadStatus==='downloading'?'Downloading…':qrDownloadStatus==='success'?'Downloaded':'Download QR'",'shareOpen?\'share-dialog-open\':\'\'','.share-dialog-open a[href$="/operations"]{pointer-events:none!important}']as$expected)$this->assertStringContainsString($expected,$source);
        preg_match('/async function downloadInvitationQr\(\).*?\nfunction printQrContent/s',$source,$match);
        $handler=$match[0]??'';$this->assertStringNotContainsString('/operations',$handler);$this->assertStringNotContainsString('router.visit',$handler);$this->assertStringNotContainsString('location.href',$handler);$this->assertStringNotContainsString('window.open',$handler);
    }
}
