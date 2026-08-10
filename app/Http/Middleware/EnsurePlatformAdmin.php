<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsurePlatformAdmin { public function handle(Request $request,Closure $next,string $level="admin"){ $user=$request->user();abort_unless($user && !in_array($user->status,["suspended","banned"],true),403);abort_unless($level==="super"?$user->isSuperAdmin():$user->isAdmin(),403);$response=$next($request);if(!$request->isMethodSafe() && $response->getStatusCode()<400){app(\App\Services\AuditLogger::class)->log("admin.".($request->route()?->getName()??"mutation"),"platform_admin_action",null,[],[],["route_parameters"=>array_map(fn($value)=>is_object($value)&&method_exists($value,"getKey")?$value->getKey():$value,$request->route()?->parameters()??[])]);}return $response;} }
