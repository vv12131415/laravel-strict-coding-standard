<?php

declare(strict_types=1);


use Illuminate\Support\Facades\Request;

class FacadeUsageClass
{
    public function callFacadeWithImport()
    {
        Request::path();
    }


    public function callFacadeWithAlias()
    {
        \Request::path();
    }
}