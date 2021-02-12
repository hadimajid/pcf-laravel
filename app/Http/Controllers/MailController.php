<?php

namespace App\Http\Controllers;

use App\Mail\AdminForgotPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public static function sendAdminForgotPasswordMail($email,$token,$code,$url){
        Mail::to($email)->send(new AdminForgotPassword(['email'=>$email,'token'=>$token,'code'=>$code,'url'=>$url]));
    }
}
