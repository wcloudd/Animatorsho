<?php

namespace App\Http\Requests;

use App\Enums\SupportTicketCategory;
use App\Http\Requests\Concerns\ValidatesSupportTicketAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    use ValidatesSupportTicketAttachment;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::enum(SupportTicketCategory::class)],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
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
