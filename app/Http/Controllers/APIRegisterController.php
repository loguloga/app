<?php

namespace App\Http\Controllers;
use JWTAuth;
use App\User;
use Response;
use Validator;
use JWTFactory;
use App\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Password;

class APIRegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:user_auth',
            'name' => 'required',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        $user = User::create([
            'email'                 => $request->get('email'),
            'password'              => bcrypt($request->get('password')),
            'verification_status'   => $request->get('verification_status'),
            'status'                => $request->get('status')
        ]);

        $auth = DB::table('user_auth')
                                      ->where('email', $email)
                                      ->value('id');
        UserDetails::create([
            'user_id'               => $auth,
            'name'                  => $request->name,
            'company'               => $request->company,
            'designation'           => $request->designation,
            'mobile'                => $request->mobile,
            'status'                => $request->status,
            'ip_address'            => $request->ip_address,
            'region'                => $request->region,
            'country'               => $request->country,
            'image_path'            => $request->image_path,
            'verification_status'   => $request->verification_status,
            'status'                => $request->status
        ]);
        
        $user = User::first();
        $token = JWTAuth::fromUser($user);
        
        $verification_code = str_random(30); //Generate verification code
 
        $update = User::where('email', $email)->update(['verification_status'=>$verification_code]);
      
        $subject = "Please verify your email address.";
       
        Mail::send('email.verify', ['name' => $name, 'verification_status' => $verification_code],
                    function($mail) use ($email, $name, $subject){
                            $mail->from('loganatan94@gmail.com', "QR Solutions");   
                            $mail->to($email, $name);
                            $mail->subject($subject);
                        });

        return response()->json([compact('token'), 'success'=> true, 'message'=> 'Thanks for signing up! Please check your email to complete your registration.']);        

        //return Response::json(compact('token'));
    }

    public function verifyUser($verification_code)
    {
        $check = DB::table('user_auth')->where('verification_status',$verification_code)->first();

        if(!is_null($check))
        {
            $user = User::find($check->id);
            if($user->status == 1)
            {
                return redirect("localhost:4200/verify-register")->with('message', 'Account already verified..');
            }
            $user->update(['status' => 1]);
            //DB::table('user_auth')->where('verification_status',$verification_code)->delete();
            return redirect("http://localhost:4200/verify-register")->with('message', 'You have successfully verified your email address.');
        }
        return redirect("http://localhost:4200/verify-register")->with('error', 'Verification code is invalid.');
    }

    public function recover(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $error_message = "Your email address was not found.";
            return response()->json(['success' => false, 'error' => ['email'=> $error_message]], 401);
        }
        try {
            Password::sendResetLink($request->only('email'), function (Message $message) {
                $message->subject('Your Password Reset Link');
            });
        } catch (\Exception $e) {
            //Return with error
            $error_message = $e->getMessage();
            return response()->json(['success' => false, 'error' => $error_message], 401);
        }
        return response()->json([
            'success' => true, 'data'=> ['message'=> 'A reset email has been sent! Please check your email.']
        ]);
    }

    public function resetPassChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $userId     = $request->id;
        $data       = Crypt::decrypt($userId);
        $password   = bcrypt($request->get('password'));

        $email = User::where('id', $data)->value('email');
       
        $update = User::where('id', $data)->update(['password'  => $password]);
        if($update)
        {
            DB::table('password_resets')->where('email',$email)->delete();
            $error_message = "your password has been updated successfully";
            return response()->json(['success' => true, 'msg' => ['password'=> $error_message]], 200);
        }
        else{
            $error_message = "please try again can't change the password";
            return response()->json(['success' => false, 'msg' => ['password'=> $error_message]], 404);
        }        
    }

    public function changePassword(Request $request)
    {
        $user_id    = $request->user_id;
        $password   = bcrypt($request->get('password'));
        
        $update = User::where('id', $user_id)->update(['password' => $password]);
        if($update){
            return response()->json(['User' => 'password changed successfully'], 200);        
        }else{
            return response()->json(['User' => 'Sorry, Password change has not done'], 400);
        }
    }   
}

