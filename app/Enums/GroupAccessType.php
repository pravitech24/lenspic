<?php

namespace App\Enums;

enum GroupAccessType: string
{
    case Full = 'full_access';
    case Partial = 'partial_access';
}
