<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Formatos de presentación (ver App\Support\Formato): @fecha, @fechaHora, @kg
        Blade::directive('fecha', fn ($e) => "<?php echo e(\\App\\Support\\Formato::fecha($e)); ?>");
        Blade::directive('fechaHora', fn ($e) => "<?php echo e(\\App\\Support\\Formato::fechaHora($e)); ?>");
        Blade::directive('kg', fn ($e) => "<?php echo e(\\App\\Support\\Formato::kg($e)); ?>");
    }
}
