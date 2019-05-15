<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helper\HttpClientHelper;

class HttpClientProvider extends ServiceProvider
{
    //protected $defer = true;

    /*public function provides()
    {
        return [HttpClientHelper::class];
    }*/

    public function register()
    {
        $this->app->bind('HttpClient',function(){
            return new HttpClientHelper();
        });
        //$this->app->bind('http_client',HttpClientHelper::class);
    }

    public function boot()
    {

    }
}
