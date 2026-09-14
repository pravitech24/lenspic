<?php

namespace App\Support;

final class MediaQuality
{
    public const STANDARD='standard';
    public const HIGH_RESOLUTION='high_resolution';
    public const VALUES=[self::STANDARD,self::HIGH_RESOLUTION];
    public static function quotaUnits(string $quality): string { return $quality===self::HIGH_RESOLUTION?'2.50':'1.00'; }
}
