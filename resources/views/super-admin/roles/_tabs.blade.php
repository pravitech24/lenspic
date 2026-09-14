@php
  $governanceTab=$activeTab??(request()->routeIs('super-admin.permissions.*')?'permissions':(request()->routeIs('super-admin.role-assignments.*')?'assignments':(request()->routeIs('super-admin.role-history.*')?'history':'roles')));
  $tabs=[
    ['roles','Roles',route('super-admin.roles.index')],
    ['permissions','Permissions',route('super-admin.permissions.index')],
    ['assignments','User Assignments',route('super-admin.role-assignments.index')],
    ['history','Change History',route('super-admin.role-history.index')],
  ];
@endphp
<nav style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem" aria-label="Roles and permissions sections">
  @foreach($tabs as [$key,$label,$href])
    <a href="{{$href}}" class="btn {{$governanceTab===$key?'btn-primary':'btn-outline'}} btn-sm" @if($governanceTab===$key) aria-current="page" @endif>{{$label}}</a>
  @endforeach
</nav>
