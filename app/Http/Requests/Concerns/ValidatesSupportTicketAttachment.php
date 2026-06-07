<?php

namespace App\Http\Requests\Concerns;

trait ValidatesSupportTicketAttachment
{
    /**
     * @return array<string, mixed>
     */
    protected function attachmentRules(): array
    {
        $mimes = implode(',', config('support.attachment_mimes', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'zip']));
        $maxKb = config('support.attachment_max_kb', 5120);

        return [
            'attachment' => [
                'nullable',
                'file',
                'mimes:'.$mimes,
                'max:'.$maxKb,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attachmentMessages(): array
    {
        return [
            'attachment.file' => 'فایل پیوست معتبر نیست.',
            'attachment.mimes' => 'نوع فایل مجاز نیست. فرمت‌های مجاز: jpg, jpeg, png, webp, pdf, zip',
            'attachment.max' => 'حجم فایل نباید بیشتر از ۵ مگابایت باشد.',
        ];
    }
}
