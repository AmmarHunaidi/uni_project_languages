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
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'phone_number' => 'required|numeric'
        ]);
        error_log($fields['phone_number']);
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'phone_number' => $fields['phone_number'],
            'password' => bcrypt($fields['password'])
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;
        User::where('id',$user['id'])->update(['remember_token'=>$token]);
        return response() -> json([
            'email' => $user->email,
            'name' => $user->name,
            'token' => $token
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
                'message' => 'error',
                'error' => 'Invalid email or password'
            ],401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;
        User::where('id',$user['id'])->update(['remember_token'=>$token]);
        return response() -> json([
            'email' => $user->email,
            'name' => $user->name,
            'token' => $token
        ]);
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return response() -> json();
    }

    public function editUser(Request $request){
        $fields = $request->validate([
            'image' => 'required|image',
        ]);
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
        ]);
    }
}
