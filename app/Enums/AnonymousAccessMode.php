<?php

namespace App\Enums;

enum AnonymousAccessMode: string
{
    case Disabled = 'disabled';
    case Full = 'full';
    case FaceOnly = 'face_only';
}
