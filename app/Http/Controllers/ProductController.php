<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Carbon\Carbon;
use DateTime;
use Ramsey\Uuid\Guid\Fields;

class ProductController extends Controller
{
    //helper functions
    static private function viewProduct($id){
        //move to get one product
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
    }
    static private function getImageUrl($file){
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move('storage', $filename);
        $link = asset('storage/'.$filename);
        return $link;
    }
    static private function getProductPrice($product){
        $expire = $product->expires_at;
        $dif =now()->diffInDays($expire);
        if(now()->diffInDays($expire) <= $product->days_before_discount_2){
            $price = $product->price - ($product->price * $product->discount_2 /100);
        }
        else if(now()->diffInDays($expire) <= $product->days_before_discount_1){
            $price = $product->price - ($product->price * $product->discount_1 /100);
        }
        else{
            $price = $product->price;
        }
        return $price;
    }
    static private function getModifiedProducts($products, $user_id){
        $modified_products = array();
        for($i=0 ;$i<count($products); $i++){
            //check if date has already expired remove from data base
            $expire = $products[$i]->expires_at;
            $current_time = now();
            if($current_time > $expire){
                error_log("delete this product");
                $products[$i] -> delete();
                continue;
            }
            // check if this user likes this product
            $liked_users = json_decode($products[$i]->liked_users);
            $isLiked = "false";
            for($j=0; $j<count($liked_users);$j++){
                if($liked_users[$j] === $user_id){
                    $isLiked = "true";
                    break;
                }
            }
            // check if this user is the owner if this product
            $is_owner = "false";
            if($user_id === $products[$i]->user_id) $is_owner = "true";


            $modified_products[$i] = array(
                'id' =>strval($products[$i]->id),
                'name' => $products[$i]->name,
                'image_url' => $products[$i]->image_url,
                'expires_at' => $products[$i]->expires_at,
                'type' => $products[$i]->type,
                'product_count' => strval($products[$i]->product_count),
                'price' =>strval(ProductController::getProductPrice($products[$i])),
                'original_price' => strval($products[$i]->price),
                'contact_info' => $products[$i]->contact_info,
                'discount_1' => strval($products[$i]->discount_1),
                'discount_2' => strval($products[$i]->discount_2),
                'days_before_discount_1' => strval($products[$i]->days_before_discount_1),
                'days_before_discount_2' => strval($products[$i]->days_before_discount_2),
                'view_count' => strval(count(json_decode($products[$i]->viewed_users))),
                'like_count' => strval(count(json_decode($products[$i]->liked_users))),
                'isLiked' => $isLiked,
                'description' => $products[$i]->description,
                'is_owner' => $is_owner,
                'comments' => $products[$i]->comments,
            );
        }
        return $modified_products;
    }

    // apis
    public function getAllProducts(){
        $products = Product::all();
        $user = auth()->user();
        $user_id = $user['id'];
        $modified_products = ProductController::getModifiedProducts($products,$user_id);
        return response($modified_products);
    }

    public function createNewProduct(Request $request){
        $product = new Product();
        $errors = array();
        $fields = array();
        // validation
        //TODO : check error messages + see if dicount values are sent numeric or not
        if($request->input('name')){
            $fields['name'] = $request->input('name');
        }else{
            $errors['name'] = "Please provide name";
        }

        if($request->hasfile('image')){
            $fields['image_url'] = ProductController::getImageUrl($request->file('image'));
        }else{
            $errors['image'] = "Please provide image";
        }

        if($request->input('expires_at')){
            $time = strtotime($request->input('expires_at'));
            $fields['expires_at'] = date('Y-m-d',$time);
        }else{
            $errors['expires_at'] = "Please provide expiry date";
        }

        if($request->input('contact_info')){
            $fields['contact_info'] = $request->input('contact_info');
        } else{
            $errors['contact_info'] = "Please provide contact info";
        }

        if($request->input('description')){
            $fields['description'] = $request->input('description');
        } else{
            $fields['description'] = "";
        }

        if($request->input('product_count') /*&& is_numeric($request->input('product_count'))*/){
            $fields['product_count'] = (int)$request->input('product_count');
        } else{
            $fields['product_count'] = 1;
        }

        if($request->input('days_before_discount_1')){
            $fields['days_before_discount_1'] = $request->input('days_before_discount_1');
        } else{
            $errors['days_before_discount_1'] = "Please provide contact info";
        }

        if($request->input('discount_1')){
            $fields['discount_1'] = $request->input('discount_1');
        } else{
            $errors['discount_1'] = "Please provide contact info";
        }

        if($request->input('days_before_discount_2')){
            $fields['days_before_discount_2'] = $request->input('days_before_discount_2');
        } else{
            $errors['days_before_discount_2'] = "Please provide day_before_discount_2";
        }

        if($request->input('discount_2')){
            $fields['discount_2'] = $request->input('discount_2');
        } else{
            $errors['discount_2'] = "Please provide discount_2";
        }

        if($request->input('price')){
            $fields['price'] = $request->input('price');
        } else{
            $errors['price'] = "Please provide price";
        }


        if($request->input('type')){
            $fields['type'] = $request->input('type');
        } else{
            $errors['type'] = "Please provide type";
        }

        //! IF VALIDATION FAILS:
        if(count($errors) > 0){
            return response() -> json([
                'message' => 'the given data was invalid',
                'errors' => $errors
                ],422);
        }

        // prep empty json for likes..
        $empty_array = array();
        $empty_array = json_encode($empty_array);

        // make days_before_discount_1 less than days_before_discount_2
        if($fields['days_before_discount_1'] < $fields['days_before_discount_2']){
            $temp1 = $fields['days_before_discount_1'];
            $temp2 = $fields['discount_1'];
            $fields['days_before_discount_1'] = $fields['days_before_discount_2'];
            $fields['discount_1'] = $fields['discount_2'];
            $fields['days_before_discount_2'] = $temp1;
            $fields['discount_2'] = $temp2;
        }

        //get user id
        $user = auth()->user();
        $user_id = $user['id'];

        // create product
        $product = new Product();
        $product->name = $fields['name'];
        $product->image_url = $fields['image_url'];
        $product->expires_at = $fields['expires_at'];
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
        $product->type = $fields['type'];
        $product->user_id = $user_id;
        if($fields['product_count']) $product->product_count = $fields['product_count'];

        $product->save();
        return response() -> json([
            'message' => 'Success',
        ]);
    }

    public function getUserProducts(){
        $user = auth()->user();
        $user_id = $user['id'];
        $products = Product::where('user_id',$user_id)->get();
        $modified_products = ProductController::getModifiedProducts($products,$user_id);
        return response($modified_products);
    }

    public function searchByFilter(Request $request){
        $input = ($request->input('query') ? $request->input('query') : "");
        $date = ($request->input('date') ? $request->input('date'): "5000-1-1");
        $time = strtotime($date);
        $expires_at_formatted = date('Y-m-d',$time);
        $products = Product::where(function($query) use ($input, $expires_at_formatted){
            $query->where('name','like','%'.$input.'%')
            ->where('expires_at' ,'<=', $expires_at_formatted);
        })->orWhere(function($query) use ($input, $expires_at_formatted){
            $query->where('type' ,'like', '%'.$input.'%')
            ->where('expires_at' ,'<=', $expires_at_formatted);
        })
        ->get();
        $user = auth()->user();
        $user_id = $user['id'];

        $modified_products =  ProductController::getModifiedProducts($products,$user_id);
        return response($modified_products);
    }
    public function getOneProduct($id){
        $products = Product::where('id',$id)->get();
        $user = auth()->user();
        $user_id = $user['id'];
        if(!$products){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        ProductController::viewProduct($id);
        $modifiedproducts = ProductController::getModifiedProducts($products,$user_id);
        return response()->json($modifiedproducts[0]);
    }

    public function deleteOneProduct($id){
        $product = Product::find($id);
        $product->delete();
        return response() -> json([
            'message' => 'Success'
        ],200);
    }
    public function updateOneProduct(Request $request, $id){
        $product = Product::find($id);
        $fields= $request->all();
        if($request->hasfile('image')){
            $fields['image_url'] = ProductController::getImageUrl($request->file('image'));
        }else{
            $fields['image_url'] =$product->image_url;
        }

        $product->name = $fields['name'];
        $product->image_url = $fields['image_url'];
        $product->contact_info = $fields['contact_info'];
        $product->description = $fields['description'];
        $product->days_before_discount_1 = $fields['days_before_discount_1'];
        $product->discount_1 = $fields['discount_1'];
        $product->days_before_discount_2 = $fields['days_before_discount_2'];
        $product->discount_2 = $fields['discount_2'];
        $product->price = $fields['price'];
        $product->type = $fields['type'];
        $product->save();
        return response() -> json([
            'message' => 'Success',
            'product' => Product::find($id)
        ],200);
        }

    public function likeProduct($id, Request $request){
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'message' => 'error',
                'error' => 'Provide valid ID'
            ]);
        }
        $likes = $product->liked_users;
        $likes = json_decode($likes);
        $user = auth()->user();
        $user_id = $user['id'];
        $found = 0;
        for($i=0;$i<count($likes);$i++)
        {
            if($likes[$i] === $user_id)
            {
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
            'message' => 'Success',
        ]);
    }

    public function commentOnProduct($id,Request $request)
    {
        $product = Product::find($id);
        $comment = $request ->input('comment');
        $comments =$product['comments'];
        $comments = json_decode($comments);
        $user = auth()->user();
        $user_id = $user['id'];
        $comment_after['id'] = count($comments)+1;
        $comment_after['user_id'] = $user_id;
        $comment_after['comment'] = $comment;
        $comment_after['user_name'] = $user['name'];
        array_push($comments,$comment_after);
        $comments = json_encode($comments);
        $product['comments'] = $comments;
        $product->update();
        return response()->json([
            'message' => 'Success'
        ]);
    }
    public function deleteComment($id,Request $request)
    {
        $commentid = $request->input('comment_id');
        $product = Product::find($id);
        $comments = $product['comments'];
        $comments = json_decode($comments);
        $found = 0;
        for($i=0;$i<count($comments);$i++)
        {
            if($comments[$i]->id === $commentid)
            {
                unset($comments[$i]);
                $found = 1;
                break;
            }
        }
        if($found === 0)
        {
            return response()->json([
                'msg' => 'Not Found!'
            ]);
        }
        $comments = json_encode($comments);
        $product->comments = $comments;
        $product->update();
        return response()->json([
            'message' => 'Success'
        ]);
    }
}
