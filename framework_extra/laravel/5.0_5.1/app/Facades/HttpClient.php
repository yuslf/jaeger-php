<?php
namespace App\Facades;

use App\Helper\HttpClientHelper;
use Illuminate\Support\Facades\Facade;

class HttpClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HttpClientHelper::class;
    }
}