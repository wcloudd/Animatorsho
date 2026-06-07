<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group',
    'key',
    'value',
])]
class Setting extends Model
{
    public const GROUP_SMS = 'sms';

    public const KEY_ENABLED = 'enabled';

    public const KEY_ADMIN_NOTIFICATIONS_ENABLED = 'admin_notifications_enabled';

    public const KEY_ADMIN_MOBILE = 'admin_mobile';
}
