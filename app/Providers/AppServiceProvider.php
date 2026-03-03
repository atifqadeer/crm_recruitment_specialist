<?php

namespace Horsefly\Providers;

use Horsefly\Applicant;
use Horsefly\IpAddress;
use Horsefly\Observers\ApplicantObserver;
use Horsefly\Observers\IpAddressObserver;
//use Horsefly\Observers\OfficeObserver;
use Horsefly\Observers\SaleObserver;
//use Horsefly\Observers\Sales_notesObserver;
//use Horsefly\Observers\UnitObserver;
//use Horsefly\Observers\UserObserver;
//use Horsefly\Office;
use Horsefly\Sale;
//use Horsefly\Sales_notes;
//use Horsefly\Unit;
//use Horsefly\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*** crm-permissions */
        $file = app_path('config/crm-permissions.php');
        if (file_exists($file)) {
            require_once($file);
        }
        /*** constants */
        $file= app_path('config/constants.php');
        if(file_exists($file)) {
            require_once($file);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('APP_ENV') === 'local') {
            URL::forceScheme('http');
        }
        Schema::defaultStringLength(191);

        //Eloquent model events
//        User::observe(UserObserver::class);
        Applicant::observe(ApplicantObserver::class);
//        Office::observe(OfficeObserver::class);
//        Unit::observe(UnitObserver::class);
        Sale::observe(SaleObserver::class);
//        Sales_notes::observe(Sales_notesObserver::class);
        IpAddress::observe(IpAddressObserver::class);
    }
}
