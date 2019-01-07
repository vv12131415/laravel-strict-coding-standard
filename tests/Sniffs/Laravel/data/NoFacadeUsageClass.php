<?php

declare(strict_types=1);

use Illuminate\Queue\LuaScripts;

class NoFacadeUsageClass
{
    public function doSomething()
    {
        LuaScripts::size();
    }
}