<?php

namespace App\Http\Controllers;
use JWTAuth;
use App\User;
use Validator;
use JWTFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class APILoginController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        
        $credentials = $request->only('email', 'password');
        
        $account=DB::table('user_auth')
       ->where('email','=',$email)
       ->count();
       
       if($account > 0)
        {
            $status = DB::table('user_auth')->where('email',$email)->value('status'); 
        
            if($status == 1){
                try {
                    if (! $token = JWTAuth::attempt($credentials)) {
                        return response()->json(['credentials_error' => 'invalid credentials'], 401);
                    }
                } catch (JWTException $e) {
                    return response()->json(['token_error' => 'could not create token'], 500);
                }
            $auth = DB::table('user_auth')->select('id')
                                        ->where('email', $email)
                                        ->get();

            $users = DB::table('user_details')->select('user_id', 'name', 'company', 'designation', 'mobile', 'region', 'country', 'image_path')
                                                ->where('user_id', $auth[0]->id)
                                                ->get();
                                    
            // //convert object into array
            // for ($i = 0, $c = count($users); $i < $c; ++$i) 
            // {
            //     $users[$i] = (array) $users[$i];
            // }         
                                                
            return response()->json(compact('token','users','email'));
            }
            else{
                return response()->json(['verify_error' => 'please check your email to register'], 500);
            }
        }
        else
        {
            return response()->json(['credentials_error' => 'invalid credentials'], 404);
        }    
    }

    public function logout(Request $request) 
    {
      
        $this->validate($request, ['token' => 'required']);
        
        try {
            JWTAuth::invalidate($request->input('token'));
            return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }
}