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
        /* $fields = $request->validate([
            'name' => 'required|string',
            ''
        ]);*/
        //! Todo : replace all if/elses with validate() && image upload
        // handle if the values are null
        //name
        if($request->input('name')){
            $product->name = $request->input('name');
        }
        else{
            $error['name'] = "Please provide name";
        }
        //image
        /* if($request->hasfile('image')){
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            //$file->move('uploads/images/', $filename);
            Storage::disk('custom')->put($filename,$file);
            $product->image = $filename;
        }
        else{
            $error['image'] = "Please select image file";
        }*/
        //date expires_at
        if($request->input('expires_at')){
            $time = strtotime($request->input('expires_at'));
            $formatTime = date('Y-m-d',$time);
            $product->expires_at = $formatTime;
        }
        else{
            $error['expires_at'] = "Please provide expiration date";
        }
        //classification
        if($request->input('category')){
            $product->category = $request->input('category');
        }
        else{
            $error['category'] = "Please provide category";
        }
        //contact_info
        if($request->input('contact_info')){
            $product->contact_info = $request->input('contact_info');
        }
        else{
            $error['contact_info'] = "Please provide contact_info";
        }
        //product_count
        if($request->input('product_count')){
            $product->product_count = (integer)$request->input('product_count');
        }
        else{
            $error['product_count'] = "Please provide product_count";
        }
        //price
        if($request->input('price')){
            $product->price = (double)$request->input('price');
        }
        else{
            $error['price'] = "Please provide price";
        }
        //if there are errors send back errors
        if(count($error)){
            return response() -> json([
                'msg' => 'failed!',
                'error' => $error
            ]);
        }
        //ready to save tp database
        $product->save();
        return response() -> json([
            'msg' => 'Success!',
            'product' => $product
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
