<?php

namespace App\Enums;

enum CourseResourceAccessScope: string
{
    case AllStudents = 'all_students';
    case PackageSpecific = 'package_specific';
}
