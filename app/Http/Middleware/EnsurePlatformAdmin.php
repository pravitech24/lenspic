<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsurePlatformAdmin { public function handle(Request $request,Closure $next,string $level='admin'){ $user=$request->user();abort_unless($user && !in_array($user->status, ['suspended','banned'], true),403);abort_unless($level==='super'?$user->isSuperAdmin():$user->isAdmin(),403);return $next($request); } }
