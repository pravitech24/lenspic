<?php
namespace App\Support;
class WatermarkPosition{public const VALUES=['top_left','top_right','bottom_left','bottom_right','bottom_center','center'];public static function coordinates(string$p,int$iw,int$ih,int$ww,int$wh):array{$m=max(8,(int)round(min($iw,$ih)*.02));return match($p){'top_left'=>[$m,$m],'top_right'=>[$iw-$ww-$m,$m],'bottom_left'=>[$m,$ih-$wh-$m],'bottom_center'=>[(int)round(($iw-$ww)/2),$ih-$wh-$m],'center'=>[(int)round(($iw-$ww)/2),(int)round(($ih-$wh)/2)],default=>[$iw-$ww-$m,$ih-$wh-$m]};}}
