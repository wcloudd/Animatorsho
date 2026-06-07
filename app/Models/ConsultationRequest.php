<?php

namespace App\Models;

use App\Enums\ConsultationRequestStatus;
use Database\Factories\ConsultationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'mobile',
    'note',
    'level',
    'interest',
    'age',
    'status',
    'admin_note',
])]
class ConsultationRequest extends Model
{
    /** @use HasFactory<ConsultationRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConsultationRequestStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
