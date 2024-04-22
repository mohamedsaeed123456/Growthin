<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Wameed\UrwayPaymentGateway\Urway;

class UpdatePaymentStatus extends Command
{
    protected $signature = 'payments:verify';
    protected $description = 'Verify payments and update status.';
    public function handle()
    {

    }
}
