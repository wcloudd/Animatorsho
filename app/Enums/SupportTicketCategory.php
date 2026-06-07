<?php

namespace App\Enums;

enum SupportTicketCategory: string
{
    case Payment = 'payment';
    case License = 'license';
    case CourseAccess = 'course_access';
    case Consultation = 'consultation';
    case Technical = 'technical';
    case Other = 'other';
}
