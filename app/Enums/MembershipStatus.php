<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Removed = 'removed';
    case Rejected = 'rejected';
}
