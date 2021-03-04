<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MailController;
use Illuminate\Console\Command;

class Product extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Coaster Products';

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
        $admin->storeProductApiData();
        $this->info("Product Stored");
        MailController::sendTestEmail('zain.waheed.softenica@gmail.com','products');

        return 0;
    }
}
