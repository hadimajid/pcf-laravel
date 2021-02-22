<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdminController;
use Illuminate\Console\Command;

class Inventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Coaster Inventory';

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
        $admin->storeWareHouseInventory();
        $this->info("Inventory Stored");
        return 0;
    }
}
