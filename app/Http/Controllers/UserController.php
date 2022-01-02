<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class UserController extends Controller
{
    public function register(Request $request){
        $exists = User::where('email',$request->email)->exists();
        if($exists)
        {
            //error_log("Here!");
            return response() -> json([
                'message' => 'the given data was invalid',
                'errors' => [
                    'email' =>'Email already registered'
                ]
            ],401);
        }
        $fields = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string|confirmed',
            'phone_number' => 'required|numeric'
        ]);
        //error_log($fields['phone_number']);
        $user = User::create([
            'name' => $fields['name'],
            'email' => $request ->email,
            'phone_number' => $fields['phone_number'],
            'password' => bcrypt($fields['password'])
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;
        User::where('id',$user['id'])->update(['remember_token'=>$token]);
        $created_user = User::find($user['id']);
        return response() -> json([
            'email' => $user->email,
            'name' => $user->name,
            'token' => $token,
            'image_url' => $created_user->image_url
        ]);
    }

    public function login(Request $request){
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        //check email
        $user = User::where('email', $fields['email'])->first();

        //check password
        if(!$user || !Hash::check($fields['password'],$user->password)){
            return response() -> json([
                'message' => 'the given data was invalid',
                'errors' => [
                    'email' =>'Invalid email or password'
                ]
            ],401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;
        User::where('id',$user['id'])->update(['remember_token'=>$token]);
        return response()->json_encode([
            'email' => $user->email,
            'name' => $user->name,
            'token' => $token,
            'image_url' => $user->image_url
        ]);
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return response() -> json();
    }

    public function editUser(Request $request){
        $errors = array();
        $fields = array();
        if($request->hasfile('image')){
            $fields['image'] = $request->file('image');
        }else{
            $errors['image'] = "Please provide image";
        }

        if(count($errors) > 0){
            return response($errors,422);
        }

        $file = $fields['image'];
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move('storage', $filename);
        $link = asset('storage/'.$filename);
        $image_url = $link;


        //get user id
        $user = auth()->user();
        $user_id = $user['id'];

        User::where('id',$user['id'])->update(['image_url'=>$image_url]);

        return response() -> json([
            'message' => 'Success',
            'image_url' => $image_url
        ],200);
    }
    public function open(Request $request)
    {
        $token =$request->validate([ 
            'token' => 'required|string'
        ]);
        if(User::where('remember_token',$token)->exists())
        {
            $user = User::where('remember_token',$token)->first();
            $token = auth('sanctum')->user()->createToken('myapptoken')->plainTextToken;
            return response()->json([
            'email' => $user->email,
            'name' => $user->name,
            'token' => $token,
            'image_url' => $user->image_url
            ],200);
        }
        else
        {
            return response()->json([
                'message'=> 'Not Valid'
            ],400);
        }
    }
}
