<?php

namespace VitoDeploy\LaravelReverbPlugin;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Plugins\RegisterSiteType;
use Illuminate\Support\ServiceProvider;

class LaravelReverbPluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->loadViewsFrom(__DIR__.'/../views', 'vitodeploy-reverb');

            // Site feature
            RegisterSiteFeature::make('laravel', 'laravel-reverb')
                ->label('Laravel Reverb')
                ->description('Enable Laravel Reverb for this site')
                ->register();
            RegisterSiteFeatureAction::make('laravel', 'laravel-reverb', 'enable')
                ->label('Enable')
                ->handler(Enable::class)
                ->register();
            RegisterSiteFeatureAction::make('laravel', 'laravel-reverb', 'disable')
                ->label('Disable')
                ->handler(Disable::class)
                ->register();

            // Site type
            RegisterSiteType::make(LaravelReverb::id())
                ->label('Laravel Reverb')
                ->handler(LaravelReverb::class)
                ->form(DynamicForm::make([
                    DynamicField::make('source_control')
                        ->component()
                        ->label('Source Control'),
                    DynamicField::make('port_alert')
                        ->alert()
                        ->options(['type' => 'warning'])
                        ->description('Make sure this port is not used by any other service on the server.'),
                    DynamicField::make('port')
                        ->text()
                        ->description('This is the port reverb will run on the server. This port is not exposed to internet!')
                        ->default(8080),
                    DynamicField::make('repository')
                        ->text()
                        ->label('Repository')
                        ->placeholder('organization/repository')
                        ->description('Your Laravel project that has Reverb instaleld on'),
                    DynamicField::make('branch')
                        ->text()
                        ->label('Branch')
                        ->default('main'),
                    DynamicField::make('command')
                        ->text()
                        ->label('Command')
                        ->default('php artisan reverb:start')
                        ->description('The command to start Laravel Reverb'),
                ]))
                ->register();
        });
    }
}
