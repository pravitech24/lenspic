<?php
namespace App\Http\Controllers;
use App\Models\GroupAccessInvite;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvitationController extends Controller
{
    private function find(string $token): ?GroupAccessInvite
    {
        return GroupAccessInvite::with(['group.creator'])->where('invitation_token', $token)->first();
    }

    private function state(?GroupAccessInvite $invite): string
    {
        if (!$invite) return 'invalid';
        if (!$invite->group->is_active) return 'event_unavailable';
        if ($invite->revoked_at || !$invite->is_active) return 'revoked';
        if ($invite->expires_at?->isPast()) return 'expired';
        if ($invite->max_uses && $invite->used_count >= $invite->max_uses) return 'unavailable';
        return 'valid';
    }

    public function show(Request $request, string $token)
    {
        $invite = $this->find($token);
        $state = $this->state($invite);
        if ($state === 'valid' && $request->query('state') === 'session_expired') $state = 'session_expired';
        if ($state === 'valid') {
            $request->session()->put('pending_invitation', $token);
            $request->session()->put('pending_invitation_group', $invite->group_id);
        } else {
            $request->session()->forget(['pending_invitation', 'pending_invitation_group']);
        }
        $membership = $request->user() && $invite ? $invite->group->membershipFor($request->user()) : null;
        if ($membership?->pivot?->membership_status === 'active' && !($membership->pivot->access_type === GroupAccessInvite::PARTIAL && $invite?->access_type === GroupAccessInvite::FULL)) $state = 'already_accepted';
        if ($membership && in_array($membership->pivot->membership_status, ['blocked','removed','rejected'], true)) $state = 'unauthorized_account';
        return Inertia::render('Invitations/Show', [
            'status' => $state,
            'authenticated' => (bool)$request->user(),
            'onboardingComplete' => ($request->user() && app(\App\Services\Auth\OnboardingState::class)->complete($request->user())),
            'event' => $invite && in_array($state, ['valid','already_accepted'], true) ? [
                'id' => $invite->group_id,
                'name' => $invite->group->name,
                'event_type' => $invite->group->event_type,
                'event_date' => $invite->group->event_date?->format('F j, Y'),
                'location' => $state === 'valid' || $state === 'already_accepted' ? $invite->group->location : null,
                'photographer' => $invite->group->creator->studio_name ?: $invite->group->creator->name,
                'access' => $invite->label,
            ] : null,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invite = $this->find($token);
        if (!$invite || !hash_equals((string)$request->session()->get('pending_invitation'), $token)) return redirect()->route('invitations.show',['token'=>$token,'state'=>'session_expired']);
        abort_unless((int)$request->session()->get('pending_invitation_group') === $invite->group_id, 403);
        abort_unless($this->state($invite) === 'valid', 410, 'This invitation is no longer available.');
        abort_unless($request->user() && app(\App\Services\Auth\OnboardingState::class)->complete($request->user()), 403);
        $result = app(GroupController::class)->joinUser($invite->group, $request->user(), 'invitation_link', $invite);
        $request->session()->forget(['pending_invitation','pending_invitation_group']);
        $message = match($result){'upgraded'=>'Your access was upgraded.','existing'=>'You already joined this event.',default=>'Welcome to the event.'};
        return redirect()->route('groups.show', $invite->group)->with('success', $message);
    }

    public function decline(Request $request, string $token)
    {
        if (hash_equals((string)$request->session()->get('pending_invitation'), $token)) {
            $request->session()->forget(['pending_invitation','pending_invitation_group']);
        }
        return redirect()->route('home')->with('success', 'Invitation declined.');
    }
}
