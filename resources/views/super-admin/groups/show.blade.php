@extends('super-admin.layout')
@section('title', 'Group Details')

@section('content')
<div class="page-header">
  <div class="page-title">
    <i class="fa-solid fa-images" style="margin-right:.5rem;"></i>
    {{ $group->name }}
  </div>
  <div>
    <a href="{{ route('super-admin.groups') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
  </div>
</div>

<div class="grid grid-3">
  <!-- Group Info -->
  <div class="card">
    <div class="card-header">Group Information</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Creator</div>
        <div>{{ $group->creator->name }}</div>
      </div>
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Email</div>
        <div style="font-size:13px;">{{ $group->creator->email }}</div>
      </div>
      <div>
        <a href="{{ route('super-admin.users.show', $group->creator) }}" class="btn btn-primary btn-sm" style="width:100%;text-align:center;">
          <i class="fa-solid fa-eye"></i> View Creator
        </a>
      </div>
    </div>
  </div>

  <!-- Group Stats -->
  <div class="card">
    <div class="card-header">Statistics</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Total Photos</div>
        <div style="font-weight:600;font-size:1.3rem;">{{ $photos->total() }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Total Members</div>
        <div style="font-weight:600;font-size:1.3rem;">{{ $members->total() }}</div>
      </div>
    </div>
  </div>

  <!-- Group Status -->
  <div class="card">
    <div class="card-header">Status</div>
    <div class="card-body">
      <div style="margin-bottom:1rem;">
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Status</div>
        <div>
          <span class="badge {{ $group->is_active ? 'badge-green' : 'badge-red' }}">
            {{ $group->is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.3rem;">Created</div>
        <div style="font-size:13px;">{{ $group->created_at->format('d M Y') }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Group Details -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Details</div>
  <div class="card-body">
    <div class="grid grid-2">
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Event Type</div>
        <div>{{ $group->getEventTypeLabel() }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Event Date</div>
        <div>{{ $group->event_date ? $group->event_date->format('d M Y') : '-' }}</div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Privacy</div>
        <div><span class="badge badge-blue">{{ ucfirst($group->privacy ?? 'private') }}</span></div>
      </div>
      <div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Guest Upload</div>
        <div>{{ $group->allow_guest_upload ? '✓ Enabled' : '✗ Disabled' }}</div>
      </div>
    </div>

    @if($group->description)
    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
      <div style="font-size:12px;color:var(--muted);margin-bottom:.5rem;">Description</div>
      <div style="color:var(--text);white-space:pre-wrap;">{{ $group->description }}</div>
    </div>
    @endif
  </div>
</div>

<!-- Members -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Members ({{ $members->total() }})</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Member</th>
          <th>Email</th>
          <th>Role</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        @forelse($members as $member)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <img src="{{ $member->profile_photo_url }}" class="avatar">
              {{ $member->name }}
            </div>
          </td>
          <td>{{ $member->email }}</td>
          <td><span class="badge badge-blue">{{ ucfirst($member->pivot->role ?? 'member') }}</span></td>
          <td style="font-size:12px;color:var(--muted);">
            @if(is_object($member->pivot->joined_at))
              {{ $member->pivot->joined_at->format('d M Y') }}
            @else
              {{ \Carbon\Carbon::parse($member->pivot->joined_at)->format('d M Y') }}
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem;">No members</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:1rem;border-top:1px solid var(--border);display:flex;justify-content:center;">
    {{ $members->links() }}
  </div>
</div>

<!-- Photos -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Photos ({{ $photos->total() }})</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Filename</th>
          <th>Uploaded By</th>
          <th>Uploaded</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($photos as $photo)
        <tr>
          <td style="font-family:monospace;font-size:12px;">{{ $photo->original_filename }}</td>
          <td>{{ $photo->uploader->name }}</td>
          <td style="font-size:12px;color:var(--muted);">{{ $photo->created_at->format('d M Y H:i') }}</td>
          <td>
            <a href="{{ route('photos.show', [$group, $photo]) }}" class="btn btn-outline btn-sm" target="_blank">
              <i class="fa-solid fa-eye"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem;">No photos</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:1rem;border-top:1px solid var(--border);display:flex;justify-content:center;">
    {{ $photos->links() }}
  </div>
</div>

<!-- Actions -->
<div class="card" style="margin-top:1.5rem;">
  <div class="card-header">Actions</div>
  <div class="card-body">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      @if($group->is_active)
      <form action="{{ route('super-admin.groups.toggle', $group) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pause"></i> Deactivate</button>
      </form>
      @else
      <form action="{{ route('super-admin.groups.toggle', $group) }}" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Activate</button>
      </form>
      @endif

      <form action="{{ route('super-admin.groups.delete', $group) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This will delete all photos.');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete Group</button>
      </form>
    </div>
  </div>
</div>

@endsection
