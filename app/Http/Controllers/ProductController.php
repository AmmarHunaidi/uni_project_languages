<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    public function getALl(){
        $products = Product::all();
        return response()->json([
            'hits' => count($products),
            'products' => $products
        ]);
    }

    public function createNewProduct(Request $request){
        $product = new Product();
        $error = array();
        $fields = $request->validate([
            'name' => 'required|string',
            'image' => 'required|image',
            'expires_at' => 'required|string',
            'contact_info' => 'required|string',
            'description' => 'string',
            'product_count' => 'numeric',
            'days_before_discount_1' => 'required|numeric',
            'discount_1' => 'required|numeric',
            'days_before_discount_2' => 'required|numeric',
            'discount_2' => 'required|numeric',
            'price' => 'required|numeric',
            'type_id' => 'required|numeric'
        ]);
        // prep image url
        $file = $fields['image'];
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move('storage', $filename);
        $link = asset('storage/'.$filename);
        $image_url = $link;

        // prep date
        $time = strtotime($request->input('expires_at'));
        $expires_at = date('Y-m-d',$time);
        
        // prep empty json for likes..
        $empty_array = array();
        $empty_array = json_encode($empty_array);

        //get user id
        $user = auth()->user();
        $user_id = $user['id'];

        // create product 
        $product = new Product();
        $product->name = $fields['name'];
        $product->image_url = $image_url;
        $product->expires_at = $expires_at;
        $product->contact_info = $fields['contact_info'];
        $product->description = $fields['description'];
        $product->days_before_discount_1 = $fields['days_before_discount_1'];
        $product->discount_1 = $fields['discount_1'];
        $product->days_before_discount_2 = $fields['days_before_discount_2'];
        $product->discount_2 = $fields['discount_2'];
        $product->viewed_users = $empty_array;
        $product->liked_users = $empty_array;
        $product->comments = $empty_array;
        $product->price = $fields['price'];
        $product->type_id = $fields['type_id'];
        $product->user_id = $user_id;

        $product->save();
        return response() -> json([
            'msg' => 'Success!',
            'product' => $product,
        ]);
    }

    public function getAllProducts(){

        return response()->json([
            'user' => auth()->user()
        ]);
    }

    public function searchByFilter(Request $request){
        $name = ($request->input('name') ? $request->input('name'): "");
        $type_id = ($request->input('type_id') ? $request->input('type_id'): "");
        $expires_at = ($request->input('expires_at') ? $request->input('expires_at'): "5000-1-1");
        $time = strtotime($expires_at);
        $expires_at_formatted = date('Y-m-d',$time);

        $products = Product::where('name','like','%'.$name.'%')
        ->where('type_id' ,'like', '%'.$type_id.'%')
        ->where('expires_at', '<=', $expires_at_formatted)
        ->get();
        return response() -> json([
        'msg' => 'Success!',
        'hits' => count($products),
        'products' => $products //! cant return product we need to return modified version
    ]);
    }

    public function getOneProduct($id){ // get user's products 
        $product = Product::find($id);
        if(!$product)
        {
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        return response()->json([
            'msg' => 'Returned Successfully',
            'product' => $product
        ]);
    }

    public function deleteOneProduct($id){
        $product = Product::find($id);
        $product_user_id = $product->user_id;
        $user = auth()->user();
        $user_id = $user['id'];
        if(!$product){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        if($product_user_id !== $user_id){
            return response() -> json([
                'msg' => 'You can not delete this product'
            ]);
        }
        $product->delete();
        return response() -> json([
            'msg' => 'Deleted Successfully'
        ]);
    }
    
    public function updateOneProduct(Request $request,$id){
        DB::table('products')
        ->where('id',$id) // check if id is correct
        ->update($request->all());
        return response()->json([
            'msg' => 'sucess!',
            'product' => $product
        ]);
    }

    
    public function likeProduct($id, Request $request){
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        $likes = $product->liked_users;
        $likes = json_decode($likes);
        $user = auth()->user();
        $user_id = $user['id'];
        $found = 0;
        for($i=0;$i<count($likes);$i++)
        {
            error_log($likes[$i]);
            if($likes[$i] === $user_id)
            {
                error_log('hi');
                unset($likes[$i]);
                $found =1;
            }
        }
        if($found === 0)
        {
            array_push($likes,$user_id);
        }
        $likes = json_encode($likes);
        $product->liked_users = $likes;
        $product->update();
        return response() -> json([
            'msg' => 'success',
            'likes' => $likes
        ]);
    }

    public function viewProduct($id){
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        $views = $product->viewed_users;
        $views = json_decode($views);
        $user = auth()->user();
        $user_id = $user['id'];
        $found = 0;
        for($i=0;$i<count($views);$i++){
            if($views[$i] === $user_id){
                $found =1;
            }
        }
        if($found === 0){
            array_push($views,$user_id);
        }
        $views = json_encode($views);
        $product->viewed_users = $views;
        $product->update();
        return response() -> json([
            'msg' => 'success',
            'viewed' => $views
        ]);
    }
}
