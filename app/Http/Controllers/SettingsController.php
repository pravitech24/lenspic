<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizationService;
use App\Contracts\ProtectedMediaStorage;
use App\Http\Requests\UpdateBusinessBrandingRequest;
use App\Services\Branding\BusinessBrandingPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\RazorpayOrder;
use App\Services\Storage\StorageUsage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    private function page(string $section)
    {
        $user = Auth::user();

        return Inertia::render('Settings/Index', [
            'section' => $section,
            'user' => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'phone' => $user->phone, 'account_type' => $user->account_type,
                'plan' => $user->plan, 'storage_used' => $user->storage_used,
                'meta' => $user->meta ?? [], 'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
            'storage' => $section === 'profile' ? app(StorageUsage::class)->summary($user) : null,
            'branding' => $section === 'branding' ? app(BusinessBrandingPresenter::class)->settings($user) : null,
            'team' => $section === 'team'
                ? $user->createdGroups()->withCount('members')->get()->map(fn ($group) => [
                    'id' => $group->id, 'name' => $group->name, 'members_count' => $group->members_count,
                ])
                : [],
        ]);
    }
    public function index()    { return $this->page('profile'); }
    public function profile()  { return $this->page('profile'); }
    public function preferences() { $this->authorizeStudio(); return $this->page('preferences'); }
    public function branding() { $this->authorizeStudio(); return $this->page('branding'); }
    public function watermark(){ $this->authorizeStudio(); return $this->page('watermark'); }
    public function team()     { $this->authorizeStudio(); return $this->page('team'); }
    public function portfolio(){ $this->authorizeStudio(); $actor=Auth::user(); $user=app(\App\Services\Team\TeamAuthorization::class)->ownerFor($actor)??$actor; abort_unless(app(\App\Services\Team\TeamAuthorization::class)->allows($actor,$user,'manage_portfolio'),403); return Inertia::render('Settings/Portfolio',['section'=>'portfolio','user'=>['plan'=>$user->plan],'branding'=>app(\App\Services\Branding\FlipbookBrandingPresenter::class)->portfolio($user->load(['flipbookSetting','businessBranding'])),'contacts'=>app(BusinessBrandingPresenter::class)->portfolio($user)]); }
    public function wallet()   { $this->authorizeStudio(); return $this->utilityPage('wallet'); }
    public function transactions(){ $this->authorizeStudio(); return $this->utilityPage('transactions'); }
    public function privacy(){ return $this->utilityPage('privacy'); }
    public function subscription()
    {
        $this->authorizeStudio();
        $user = Auth::user();
        $subscriptions = $user->subscriptions()->latest()->limit(25)->get();

        return Inertia::render('Settings/Subscription', [
            'user' => [
                'plan' => $user->plan,
                'plan_label' => $user->plan_label,
                'plan_expires_at' => $user->plan_expires_at,
                'storage_used' => $user->storage_used,
                'storage_used_human' => $user->storage_used_human,
                'plan_limits' => $user->plan_limits,
                'groups_used' => $user->createdGroups()->count(),
            ],
            'currentSubscription' => $subscriptions->first(fn ($subscription) => $subscription->isActive())?->only('id', 'plan', 'status', 'billing_cycle', 'starts_at', 'expires_at', 'amount', 'currency', 'scheduled_plan', 'scheduled_change_at'),
            'subscriptions' => $subscriptions->map(fn ($subscription) => $subscription->only('id', 'plan', 'status', 'billing_cycle', 'starts_at', 'expires_at', 'amount', 'currency', 'created_at')),
            'orders' => RazorpayOrder::where('user_id', $user->id)->latest()->limit(10)->get()->map(fn ($order) => $order->only('id', 'plan', 'billing_cycle', 'amount', 'currency', 'status', 'created_at')),
            'availablePlans' => BillingController::planCatalog(),
            'paymentConfigured' => filled(config('services.razorpay.key_id')),
            'storageEntitlements' => app(\App\Services\Storage\PlanEntitlements::class)->for($user),
            'usage' => [
                'storage' => app(\App\Services\Storage\StorageUsage::class)->summary($user),
                'team' => app(\App\Services\Team\TeamSeatUsage::class)->summary($user),
            ],
            'billingMode' => config('billing.mode'),
        ]);
    }

    private function utilityPage(string $section)
    {
        $user = Auth::user();

        return Inertia::render('Settings/Utility', [
            'section' => $section,
            'user' => ['plan' => $user->plan, 'plan_label' => $user->plan_label],
            'portfolioBranding' => $section === 'portfolio'
                ? app(BusinessBrandingPresenter::class)->portfolio($user)
                : [],
            'transactions' => $section === 'transactions'
                ? $user->subscriptions()->latest()->limit(25)->get()->map(fn ($subscription) => $subscription->only('id', 'plan', 'status', 'billing_cycle', 'amount', 'currency', 'created_at', 'expires_at'))
                : [],
        ]);
    }

    private function authorizeStudio(): void
    {
        $this->authorize('create', Group::class);
    }

    public function updateProfile(Request $request, ImageOptimizationService $imageOptimizationService)
    {
        $user = Auth::user();
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => filled($request->input('phone')) ? preg_replace('/[\s().-]+/', '', (string) $request->input('phone')) : null,
        ]);
        $validated = $request->validate([
            'first_name' => ['required','string','max:60','regex:/^[\pL\pM\s\'\-\.]+$/u'],
            'last_name'  => ['nullable','string','max:60','regex:/^[\pL\pM\s\'\-\.]+$/u'],
            'email'      => 'required|email|unique:users,email,'.$user->id,
            'phone'      => ['nullable','string','max:20','regex:/^\+?[1-9]\d{7,14}$/'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $phone = filled($validated['phone'] ?? null) ? preg_replace('/[\s().-]+/', '', $validated['phone']) : null;
        $emailChanged = $email !== $user->email;
        $firstName = preg_replace('/\s+/u', ' ', trim($validated['first_name']));
        $lastName = preg_replace('/\s+/u', ' ', trim($validated['last_name'] ?? ''));
        $data = ['name' => trim($firstName.' '.$lastName), 'email' => $email, 'phone' => $phone];
        if ($emailChanged) $data['email_verified_at'] = null;

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) Storage::disk('public')->delete($user->profile_photo);
            $optimized = $imageOptimizationService->optimizeAndStore($request->file('profile_photo'), 'avatars');
            $data['profile_photo'] = $optimized['path'];
        }

        $user->update($data);
        return back()->with('success', $emailChanged ? 'Profile updated. Verify your new email address before using verified-email features.' : 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);
        $request->user()->forceFill(['password' => Hash::make($validated['password'])])->save();
        $request->session()->regenerate();
        return back()->with('success', 'Password updated securely.');
    }

    public function updatePreferences(Request $request)
    {
        $this->authorizeStudio();
        $existing=$request->user()->meta['preferences']??[];
        $request->merge(collect(['in_app_notifications'=>true,'email_notifications'=>true,'whatsapp_notifications'=>false,'notify_group_activity'=>true,'notify_upload_completion'=>true,'notify_face_recognition'=>true,'notify_team_activity'=>true,'notify_storage_warnings'=>true,'notify_billing_wallet'=>true,'notify_product_updates'=>true])->mapWithKeys(fn($default,$key)=>[$key=>$request->has($key)?$request->boolean($key):($existing[$key]??$default)])->all());
        $validated = $request->validate([
            'upload_quality_preference' => ['required','in:standard,high_resolution'],
            'post_transfer_action' => ['required','in:none,leave_group,archive_group'],
            'in_app_notifications' => ['required','boolean'],
            'email_notifications' => ['required','boolean'],
            'whatsapp_notifications' => ['required','boolean'],
            'notify_group_activity' => ['required','boolean'],
            'notify_upload_completion' => ['required','boolean'],
            'notify_face_recognition' => ['required','boolean'],
            'notify_team_activity' => ['required','boolean'],
            'notify_storage_warnings' => ['required','boolean'],
            'notify_billing_wallet' => ['required','boolean'],
            'notify_product_updates' => ['required','boolean'],
        ]);
        $meta = $request->user()->meta ?? [];
        $validated['whatsapp_notifications'] = false;
        $meta['preferences'] = $validated;
        $request->user()->update(['meta' => $meta]);
        return back()->with('success', 'Account preference updated.');
    }

    public function updateBranding(UpdateBusinessBrandingRequest $request, ProtectedMediaStorage $storage)
    {
        $user=$request->user();$validated=$request->validated();$old=$user->businessBranding()->value('logo_object_key');$new=null;
        unset($validated['logo'],$validated['remove_logo']);
        if($request->hasFile('logo')){$file=$request->file('logo');$extension=$file->getMimeType()==='image/png'?'png':'jpg';$new=$storage->putFile('branding/users/'.$user->id,$file,$extension);$validated+=['logo_disk'=>$storage->disk(),'logo_object_key'=>$new,'logo_mime_type'=>$file->getMimeType(),'logo_size_bytes'=>$storage->size($new)];}
        if($request->boolean('remove_logo')&&!$new)$validated+=['logo_disk'=>null,'logo_object_key'=>null,'logo_mime_type'=>null,'logo_size_bytes'=>null];
        try{$user->businessBranding()->updateOrCreate([], $validated);$user->unsetRelation('businessBranding');}catch(\Throwable$e){if($new)$storage->delete($new);throw$e;}
        if($old&&($new||$request->boolean('remove_logo'))&&$storage->exists($old))$storage->delete($old);
        return back()->with('success','Business branding updated.');
    }

    public function updateWatermark(Request $request)
    {
        $validated = $request->validate([
            'watermark_text' => ['nullable','string','max:120'],
            'watermark_position' => ['required','in:bottom-right,bottom-left,center'],
            'watermark_opacity' => ['required','integer','between:20,90'],
        ]);
        $user = Auth::user();
        $meta = $user->meta ?? [];
        $meta['watermark_text'] = $validated['watermark_text'];
        $meta['watermark_position'] = $validated['watermark_position'];
        $meta['watermark_opacity'] = $validated['watermark_opacity'];
        $user->update(['meta' => $meta]);
        return back()->with('success', 'Watermark settings saved!');
    }
}
