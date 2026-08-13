<?php
namespace Tests\Feature;
use Tests\TestCase;
class UnifiedThemeTest extends TestCase
{
    public function test_all_inertia_pages_use_an_approved_shared_shell():void{$files=glob(resource_path('js/Pages/**/*.vue'));$files=array_merge($files,glob(resource_path('js/Pages/*.vue')));foreach(array_unique($files)as$file){$source=file_get_contents($file);$this->assertTrue(str_contains($source,'AppShell')||str_contains($source,'AuthShell')||str_contains($file,'Invitations/Show.vue'),basename($file).' must use a shared LensPic shell.');}}
    public function test_gallery_grids_have_no_rounded_utility_on_immediate_media():void{foreach([resource_path('js/Pages/Events/Show.vue'),resource_path('js/Pages/MyPhotos.vue'),resource_path('js/Pages/Events/Reviews.vue')]as$file){$source=file_get_contents($file);$this->assertStringContainsString('photo-grid',$source);$this->assertDoesNotMatchRegularExpression('/photo-(?:tile|grid)[^>]*rounded-|<img[^>]*rounded-/',$source);}$css=file_get_contents(resource_path('css/app.css'));$this->assertStringContainsString('border-radius:0!important',$css);}
    public function test_theme_defines_accessible_interaction_and_responsive_tokens():void{$css=file_get_contents(resource_path('css/app.css'));foreach(['--lp-brand','--lp-canvas','.btn-primary','.btn-danger','.lp-alert-error','.lp-spinner','focus-visible']as$token)$this->assertStringContainsString($token,$css);$shell=file_get_contents(resource_path('js/Layouts/AppShell.vue'));foreach(['sm:','lg:','aria-expanded','Skip to content']as$token)$this->assertStringContainsString($token,$shell);}
}
