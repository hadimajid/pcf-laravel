<?php

namespace App\Http\Controllers;

use App\Mail\AdminForgotPassword;
use App\Mail\ContactUs;
use App\Mail\Test;
use App\Mail\UserForgotPassword;
use App\Mail\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public static function sendAdminForgotPasswordMail($email,$token,$code,$url){
        Mail::to($email)->send(new AdminForgotPassword(['email'=>$email,'token'=>$token,'code'=>$code,'url'=>$url]));
    }
    public static function sendUserForgotPasswordMail($email,$token,$url){
        Mail::to($email)->send(new UserForgotPassword(['email'=>$email,'token'=>$token,'url'=>$url]));
    }
    public static function sendVerifyEmail($email,$token,$code,$url){
        Mail::to($email)->send(new VerifyEmail(['email'=>$email,'token'=>$token,'code'=>$code,'url'=>$url]));
    }
    public static function sendTestEmail($email,$message){
        Mail::to($email)->send(new Test(['cron_job'=>$message]));
    }
    public static function sendContactUsEmail($contact_email,$email,$name,$phone,$subject,$message){
        Mail::to($contact_email)->send(new ContactUs(['email'=>$email,'name'=>$name,'phone'=>$phone,'subject'=>$subject,'message'=>$message]));
    }
}
