<?php

namespace App\Services;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\Security\SecurityEventLogger;
use InvalidArgumentException;

class ConsultationRequestService
{
    public function __construct(
        private readonly SecurityEventLogger $securityEvents,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     note?: ?string,
     *     level?: ?string,
     *     interest?: ?string,
     *     age?: ?string
     * }  $data
     */
    public function create(User $user, array $data): ConsultationRequest
    {
        $openRequest = $this->openRequestForUser($user);

        if ($openRequest !== null) {
            $this->securityEvents->consultationDuplicateBlocked($openRequest->id);

            throw new InvalidArgumentException(
                'شما در حال حاضر یک درخواست مشاوره باز دارید. پس از پیگیری توسط پشتیبانی می‌توانید درخواست جدید ثبت کنید.',
            );
        }

        return ConsultationRequest::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'mobile' => $user->mobile,
            'note' => $data['note'] ?? null,
            'level' => $data['level'] ?? null,
            'interest' => $data['interest'] ?? null,
            'age' => $data['age'] ?? null,
            'status' => ConsultationRequestStatus::New,
        ]);
    }

    public function userHasOpenRequest(User $user): bool
    {
        return $this->openRequestForUser($user) !== null;
    }

    private function openRequestForUser(User $user): ?ConsultationRequest
    {
        return ConsultationRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ConsultationRequestStatus::openCases())
            ->first();
    }
}
