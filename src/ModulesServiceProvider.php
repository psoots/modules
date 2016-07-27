<?php

namespace Caffeinated\Modules;

use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/modules.php' => config_path('modules.php'),
        ], 'config');

        if(config('modules.custom_stubs')){
            $this->publishes([
                __DIR__.'/../resources/stubs/' => config('modules.custom_stubs'),
            ], 'stubs');
        }

        $modules = $this->app['modules'];

        $modules->register();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/modules.php', 'modules'
        );

        $this->app->register('Caffeinated\Modules\Providers\RepositoryServiceProvider');

        $this->app->register('Caffeinated\Modules\Providers\MigrationServiceProvider');

        $this->app->register('Caffeinated\Modules\Providers\ConsoleServiceProvider');

        $this->app->register('Caffeinated\Modules\Providers\GeneratorServiceProvider');

        $this->app->singleton('modules', function ($app) {
            $repository = $app->make('Caffeinated\Modules\Contracts\Repository');

            return new \Caffeinated\Modules\Modules($app, $repository);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string
     */
    public function provides()
    {
        return ['modules'];
    }

    public static function compiles()
    {
        $repository = app()->make('modules');
        $modules    = $repository->all();
        $files      = [];

        foreach ($modules as $slug => $properties) {
            $namespace       = $repository->resolveNamespace($properties);
            $file            = $repository->getPath()."/{$namespace}/Providers/{$namespace}ServiceProvider.php";
            $serviceProvider = $repository->getNamespace().'\\'.$namespace."\\Providers\\{$namespace}ServiceProvider";

            if (class_exists($serviceProvider)) {
                $files = array_merge($files, forward_static_call([$serviceProvider, 'compiles']));
            }
        }

        return array_map('realpath', $files);
    }
}
