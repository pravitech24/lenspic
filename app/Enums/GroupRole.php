<?php

namespace App\Enums;

enum GroupRole: string
{
    case Admin = 'admin';
    case Viewer = 'member';
}
