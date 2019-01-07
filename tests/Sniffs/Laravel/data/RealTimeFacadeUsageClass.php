<?php

declare(strict_types=1);


use Facades\Illuminate\Filesystem\Filesystem;

class RealTimeFacadeUsageClass
{
    public function callRealTimeFacade()
    {
        Filesystem::exists(__FILE__);
    }
}