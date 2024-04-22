<?php

namespace App\Http\Controllers\Billing_Subscription;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Payment;
use Wameed\UrwayPaymentGateway\Urway;
use URWay\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request,$id){
        $user= $request->user();
        $bundle = Bundle::find($id);
        $payment = Payment::where('user_id', $user->id)
                ->where('bundle_id', $bundle->id)
                ->first();
        if(!$payment){
            $payment =Payment::create([
                'user_id' => $user->id,
                'bundle_id' => $bundle->id,
                'status' => 'unPaid',
            ]);
        }
        $urway = new Urway();
        $urway->setTrackId($payment->bundle->id)
            ->setAmount($payment->bundle->price)
            ->setCurrency('SAR')
            ->setCountry('Egypt')
            ->setAttribute('Bundle Name', $payment->bundle->name)
            ->setPaymentPageLanguage('ar')
            ->setAttribute('Bundle Description',$payment->bundle->description)
            ->setCustomerEmail($user->email)
            ->setMerchantIp('192.168.0.131');
        $response = $urway->pay();
        $payment_url = $response->getPaymentUrl();
        $transactionId = $response->payid;
        $payment->tranid = $transactionId;
        $payment->save();
        // $now = Carbon::now();
        // $lastStatus = Carbon::parse($payment->updated_at);
        // $minutesSinceLastStatus = $now->diffInMinutes($lastStatus);
        // if ($minutesSinceLastStatus > 1) {
        //     $payments = Payment::where('status', 'unPaid')->get();
        //     foreach ($payments as $payment) {
        //         $urway = new Urway();
        //         $urway->setTrackId($payment->bundle->id)
        //             ->setAmount($payment->bundle->price)
        //             ->setMerchantIp('192.168.0.131')
        //             ->setCurrency('SAR');
        //         if ($payment->tranid) {
        //             $verify = $urway->verify($payment->tranid);
        //             if ($verify->result !== "Successful") {
        //                 $payment->update(['status' => 'Paid']);
        //             }
        //         }
        //     }
        // }
        return response()->json(['URl' => $payment_url ,'message' => $payment->status,'status' => 200]);
    }
}
