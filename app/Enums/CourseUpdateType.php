<?php

namespace App\Enums;

enum CourseUpdateType: string
{
    case Announcement = 'announcement';
    case LessonUpdate = 'lesson_update';
    case ExerciseUpdate = 'exercise_update';
    case ResourceAdded = 'resource_added';
    case Important = 'important';
}
