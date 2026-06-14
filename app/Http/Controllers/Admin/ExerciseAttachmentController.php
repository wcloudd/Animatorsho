<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminExerciseAttachmentListService;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseAttachmentController extends Controller
{
    public function __construct(
        private readonly AdminExerciseAttachmentListService $attachments,
    ) {}

    public function index(): Response
    {
        return Inertia::render(
            'admin/exercise-attachments/index',
            $this->attachments->indexForAdmin(),
        );
    }
}
