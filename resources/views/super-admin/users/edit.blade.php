@extends('super-admin.layout')
@section('title', 'Edit User')

@section('content')
<div class="page-header">
  <div class="page-title">
    <i class="fa-solid fa-edit" style="margin-right:.5rem;"></i>
    Edit User: {{ $user->name }}
  </div>
  <a href="{{ route('super-admin.users.show', $user) }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-header">User Information</div>
  <div class="card-body">
    <form action="{{ route('super-admin.users.update', $user) }}" method="POST">
      @csrf @method('PUT')

      <div class="grid grid-2">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" value="{{ $user->name }}" required>
          @error('name')<span style="color:var(--danger);font-size:12px;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" value="{{ $user->email }}" required>
          @error('email')<span style="color:var(--danger);font-size:12px;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
          <label>Phone</label>
          <input type="tel" name="phone" value="{{ $user->phone }}">
        </div>

        <div class="form-group">
          <label>Role *</label>
          <select name="role" required>
            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="super_admin" {{ $user->role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
          </select>
          @error('role')<span style="color:var(--danger);font-size:12px;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
          <label>Status *</label>
          <select name="status" required>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="banned">Banned</option>
          </select>
        </div>

        <div class="form-group">
          <label>New Password (leave blank to keep current)</label>
          <input type="password" name="password" placeholder="Min 8 characters">
          @error('password')<span style="color:var(--danger);font-size:12px;">{{ $message }}</span>@enderror
        </div>
      </div>

      <div style="display:flex;gap:.5rem;margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
        <a href="{{ route('super-admin.users.show', $user) }}" class="btn btn-outline"><i class="fa-solid fa-times"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>

@endsection
