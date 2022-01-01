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
        $errors = array();
        $fields = array();
        if($request->input('name')){
            $fields['name'] = $request->input('name');
        }else{
            $errors['name'] = "Please provide name";
        }

        if($request->input('email')){
            $fields['email'] = $request->input('email');
            $user = User::where('email', $fields['email'])->first();
            if($user){
                $errors['email'] = "This email has already been registered";
            }
        }else{
            $errors['email'] = "Please provide email";
        }

        if($request->input('password')){
            $fields['password'] = $request->input('password');
        }else{
            $errors['password'] = "Please provide password";
        }

        if($request->input('phone_number')){
            $fields['phone_number'] = $request->input('phone_number');
        }else{
            $errors['phone_number'] = "Please provide phone_number";
        }
        
        if(count($errors) > 0){
            return response($errors,422);
        }

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
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
        return response() -> json([
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
}
