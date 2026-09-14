<?php

namespace App\Support;

final class UploadStatusPresenter
{
    public static function label(?string $state): string
    {
        return match ($state) {
            'pending', 'queued' => 'Preparing',
            'processing' => 'Processing',
            'completed' => 'Ready',
            'failed' => "Couldn't process",
            'completed_with_errors' => 'Needs attention',
            'cancelled' => 'Cancelled',
            default => 'Preparing',
        };
    }

    public static function message(string $state, int $ready, int $total, int $failed = 0): string
    {
        return match ($state) {
            'completed' => 'Your photos are ready and now available in the gallery.',
            'completed_with_errors' => "$ready of $total photos are ready. $failed photos need attention.",
            'failed' => "We couldn't process these photos. Please try again.",
            'processing' => "$ready of $total photos ready. We're preparing your gallery.",
            'cancelled' => 'Upload cancelled.',
            default => 'Preparing your photos… You can leave this page. Your photos will continue processing safely.',
        };
    }
}
