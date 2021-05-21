<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use \Maatwebsite\Excel\Sheet;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Sheet::macro('setURL', function (Sheet $sheet, string $cell, string $url) {
            if ($url) {
                $fileName = basename($url);
    
                $sheet->getCell($cell)->setValue($fileName);
                $sheet->getCell($cell)->getHyperlink()->setUrl($url);
            }
        });
    }
}
