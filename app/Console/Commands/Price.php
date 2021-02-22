<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdminController;
use Illuminate\Console\Command;

class Price extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Coaster Price';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $admin=new AdminController();
        $admin->storeProductPrice();
        $this->info("Price Stored");
        return 0;
    }
}
