<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MailController;
use Illuminate\Console\Command;

class Category extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Coaster Categories SubCategories And Pieces';

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
        $admin->storeCategoryApiData();
        $this->info("Category Stored");
//        MailController::sendTestEmail('zain.waheed.softenica@gmail.com','category');

        return 0;
    }
}
