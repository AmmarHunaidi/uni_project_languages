<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    public function formatImage($file){
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move('storage', $filename);
        $link = asset('storage/'.$filename);
        return $link;
    }
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

        /*$product = Product::create([
            'name' => $fields['name'],
            'image_url' => $image_url,
            'expires_at' => $expires_at,
            'contact_info' => $fields['contact_info'],
            'description' => $fields['description'],
            'product_count' => $fields['product_count'],
            'days_before_discount_1' => $fields['days_before_discount_1'],
            'discount_1' => $fields['discount_1'],
            'days_before_discount_2' => $fields['days_before_discount_2'],
            'discount_2' => $fields['discount_2'],
            'viewed_users' => $empty_array,
            'liked_users' => $empty_array,
            'comments' => $empty_array,
            'price' => $fields['price'],
            'type_id' => $fields['type_id'],
            'user_id' => 1

        ]);*/

        $product->save();
        return response() -> json([
            'msg' => 'Success!',
            'product' => $product,
        ]);
    }

    public function getAllNandP(){

    }

    public function searchByFilter(Request $request){
        $name = ($request->input('name') ? $request->input('name'): "");
        $classification = ($request->input('classification') ? $request->input('classification'): "");
        $expires_at = ($request->input('expires_at') ? $request->input('expires_at'): "5000-1-1");
        $time = strtotime($expires_at);
        $expires_at_formatted = date('Y-m-d',$time);

        $products = Product::where('name','like','%'.$name.'%')
        ->where('classification' ,'like', '%'.$classification.'%')
        ->where('expires_at', '<=', $expires_at_formatted)
        ->get();
        return response() -> json([
        'msg' => 'Success!',
        'hits' => count($products),
        'products' => $products
    ]);
    }

    public function getOneProduct($id){
        if($product = DB::table('products')->where('id',$id))
        {
            if($product->get(''))
            return response()->json($product->get());
        }
        else
        {
            return response(['Invalid ID!']);
        }
    }

    public function deleteOneProduct($id){
        // return Product::destroy($id)  => returns 1 if deleted and 0 if not  /test it!
        //! check if same token 
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        $product->delete();
        return response() -> json([
            'msg' => 'Deleted Successfully'
        ]);
    }
    
    public function updateOneProduct(Request $request,$id){
        DB::table('products')
        ->where('id',$id)
        ->update($request->all());
        return response(['Success!']);
    }
}
