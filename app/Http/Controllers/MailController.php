<?php

namespace App\Http\Controllers;

use App\Mail\AdminForgotPassword;
use App\Mail\Test;
use App\Mail\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public static function sendAdminForgotPasswordMail($email,$token,$code,$url){
        Mail::to($email)->send(new AdminForgotPassword(['email'=>$email,'token'=>$token,'code'=>$code,'url'=>$url]));
    }
    public static function sendVerifyEmail($email,$token,$code,$url){
        Mail::to($email)->send(new VerifyEmail(['email'=>$email,'token'=>$token,'code'=>$code,'url'=>$url]));
    }
    public static function sendTestEmail($email,$message){
        Mail::to($email)->send(new Test(['cron_job'=>$message]));
    }
}
