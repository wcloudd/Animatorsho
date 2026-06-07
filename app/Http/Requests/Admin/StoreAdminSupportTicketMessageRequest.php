<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesSupportTicketAttachment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminSupportTicketMessageRequest extends FormRequest
{
    use ValidatesSupportTicketAttachment;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'waiting_for_user' => ['sometimes', 'boolean'],
            ...$this->attachmentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->attachmentMessages();
    }
}
