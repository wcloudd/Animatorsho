<?php

namespace App\Services;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\User;

class ConsultationRequestService
{
    /**
     * @param  array{
     *     name: string,
     *     mobile: string,
     *     note?: ?string,
     *     level?: ?string,
     *     interest?: ?string,
     *     age?: ?string
     * }  $data
     */
    public function create(?User $user, array $data): ConsultationRequest
    {
        return ConsultationRequest::query()->create([
            'user_id' => $user?->id,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'note' => $data['note'] ?? null,
            'level' => $data['level'] ?? null,
            'interest' => $data['interest'] ?? null,
            'age' => $data['age'] ?? null,
            'status' => ConsultationRequestStatus::New,
        ]);
    }
}
