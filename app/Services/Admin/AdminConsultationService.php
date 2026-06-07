<?php

namespace App\Services\Admin;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;

class AdminConsultationService
{
    /**
     * @param  array{
     *     status: ConsultationRequestStatus,
     *     admin_note?: ?string
     * }  $data
     */
    public function update(ConsultationRequest $consultation, array $data): ConsultationRequest
    {
        $consultation->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        return $consultation->fresh();
    }
}
