<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\SecurityServiceProvider;

return [
    AppServiceProvider::class,
    SecurityServiceProvider::class,
    FortifyServiceProvider::class,
];
