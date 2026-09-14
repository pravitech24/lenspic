<?php
namespace App\Services\Team;
use App\Models\{StudioTeamMembership,User};
class TeamAuthorization {
 public const OPTIONAL_PERMISSIONS=['analytics.view_assigned','analytics.export_assigned'];
 public const ROLES=['admin','photographer','editor','viewer'];
 public const MATRIX=[
  'admin'=>['view_groups','create_groups','edit_groups','delete_groups','upload_photos','delete_photos','download_originals','manage_participants','manage_face_recognition','manage_watermark','manage_portfolio','manage_wallet','manage_transactions','view_reports','manage_branding','manage_team'],
  'photographer'=>['view_groups','create_groups','edit_groups','upload_photos','delete_photos','download_originals','manage_participants','manage_face_recognition','view_reports'],
  'editor'=>['view_groups','edit_groups','delete_photos','download_originals','manage_participants'],
  'viewer'=>['view_groups'],
 ];
 public function membership(User$user,User$owner):?StudioTeamMembership{return $user->id===$owner->id?null:StudioTeamMembership::where('studio_owner_id',$owner->id)->where('user_id',$user->id)->first();}
 public function activeMembership(User$user,User$owner):?StudioTeamMembership{$m=$this->membership($user,$owner);return$m?->status==='active'?$m:null;}
 public function ownerFor(User$user):?User{$managed=StudioTeamMembership::with('owner')->where('user_id',$user->id)->where('status','active')->where('role','admin')->oldest()->first();if($managed)return$managed->owner;if($user->hasGroupCreatorRole())return$user;$m=StudioTeamMembership::with('owner')->where('user_id',$user->id)->where('status','active')->oldest()->first();return$m?->owner;}
 public function allows(User$user,User$owner,string$permission):bool{if($user->id===$owner->id)return true;$m=$this->activeMembership($user,$owner);if(!$m)return false;$maximum=array_merge(self::MATRIX[$m->role]??[],self::OPTIONAL_PERMISSIONS);$assigned=$m->permissions?:self::MATRIX[$m->role]??[];return in_array($permission,$maximum,true)&&in_array($permission,$assigned,true);}
 public function canManageTeam(User$user,User$owner):bool{return$this->allows($user,$owner,'manage_team');}
 public function permissionsFor(string$role,?array$requested=null):array{$defaults=self::MATRIX[$role]??[];$maximum=array_merge($defaults,self::OPTIONAL_PERMISSIONS);return$requested===null?$defaults:array_values(array_intersect($maximum,$requested));}
}
