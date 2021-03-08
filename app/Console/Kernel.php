<?php

namespace App\Console;

use App\Console\Commands\Category;
use App\Console\Commands\Inventory;
use App\Console\Commands\Price;
use App\Console\Commands\Product;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Category::class,
        Inventory::class,
        Price::class,
        Product::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
            $schedule->command('category:store')->dailyAt("00:00");
            $schedule->command('product:store')->dailyAt("00:00");
            $schedule->command('inventory:store')->everyMinute();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
