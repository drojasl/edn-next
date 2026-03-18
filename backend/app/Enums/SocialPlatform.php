<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case CELL_PHONE = 'cell_phone';
    case WHATSAPP = 'whatsapp';
    case TELEGRAM = 'telegram';
    case INSTAGRAM = 'instagram';
    case FACEBOOK = 'facebook';
    case TIKTOK = 'tiktok';
    case YOUTUBE = 'youtube';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
