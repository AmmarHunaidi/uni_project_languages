<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Type;
use Carbon\Carbon;
use DateTime;

class ProductController extends Controller
{
    //helper functions
    private function getImageUrl($file){
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->move('storage', $filename);
        $link = asset('storage/'.$filename);
        return $link;
    }
    private function getProductPrice($product){
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
    private function getModifiedProducts($products, $user_id){
        $modified_products = array();
        for($i=0 ;$i<count($products); $i++){
            // check if this user likes this product
            $liked_users = json_decode($products[$i]->liked_users);
            $isLiked = false;
            for($j=0; $j<count($liked_users);$j++){
                if($liked_users[$j] === $user_id){
                    $isLiked = true;
                    break;
                }
            }
            // check if this user is the owner if this product
            $is_owner = false;
            if($user_id === $products[$i]->user_id) $is_owner = true;

            //get type name 
            $type = Type::find($products[$i]->type_id);

            $modified_products[$i] = array(
                'id' => $products[$i]->id,
                'name' => $products[$i]->name,
                'image_url' => $products[$i]->image_url,
                'expires_at' => $products[$i]->expires_at,
                'type' => $type->name,
                'product_count' => $products[$i]->product_count,
                'price' => ProductController::getProductPrice($products[$i]),
                'original_price' => $products[$i]->price,
                'contact_info' => $products[$i]->contact_info,
                'discount_1' => $products[$i]->discount_1,
                'discount_2' => $products[$i]->discount_2,
                'days_before_discount_1' => $products[$i]->days_before_discount_1,
                'days_before_dsicount_2' => $products[$i]->days_before_discount_2,
                'view_count' => count(json_decode($products[$i]->viewed_users)),
                'like_count' => count(json_decode($products[$i]->liked_users)),
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
        return response()->json([
            $products
        ]);
    }

    public function createNewProduct(Request $request){
        $product = new Product();
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
            'type' => 'required|string',
        ]);
        // prep image url
        $image_url = ProductController::getImageUrl($fields['image']);

        // prep date
        $time = strtotime($request->input('expires_at'));
        $expires_at = date('Y-m-d',$time);

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

        //get type or add this type to tyoe table
        $fields['type'] = strtolower($fields['type']);
        $type = Type::where('name',$fields['type'])->first();
        if(!$type){
            $type = Type::create([
                'name' => $fields['type']
            ]);
        }
        else{
            $type->update(['count'=>($type->count+1)]);
        }
        $type_id = $type->id;

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
        $product->type_id = $type_id;
        $product->user_id = $user_id;
        if($fields['product_count']) $product->product_count = $fields['product_count'];

        $product->save();
        return response() -> json([
            'message' => 'Success',
        ]);
    }

    public function getUserProducts(){
        //TODO convert products to modified products
        $user = auth()->user();
        $user_id = $user['id'];
        $products = Product::where('user_id',$user_id)->get();
        return response()->json([
            'user' => $user_id,
            'products' => $products
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

        $user = auth()->user();
        $user_id = $user['id'];

        return response() -> json([
            ProductController::getModifiedProducts($products,$user_id)
        ]);
    }

    public function getOneProduct($id){
        //TODO add view logic in get one product
        //TODO convert product to modified version
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'msg' => 'Provide Valid Id'
            ]);
        }
        $this->viewProduct($id);
        $product = Product::find($id);
        $product->liked_users = count(json_decode($product->liked_users));
        $product->viewed_users = count(json_decode($product->viewed_users));
        $expire = $product->expires_at;
        if(now()->diffInDays($expire) <= $product->days_before_discount_2){
            $product->price = $product->price - ($product->price * $product->discount_2 /100);
        }
        else if(now()->diffInDays($expire) <= $product->days_before_discount_1){
            $product->price = $product->price - ($product->price * $product->discount_1 /100);
        }
        return response()->json([
            'msg' => 'Returned Successfully',
            'product' => $product
        ]);
    }

    public function deleteOneProduct($id){
        $product = Product::find($id);
        if(!$product){
            return response() -> json([
                'message' => 'error',
                'error' => 'Provide Valid Id'
            ]);
        }
        $product_user_id = $product->user_id;
        $user = auth()->user();
        $user_id = $user['id'];
        $product->delete();
        return response() -> json([
            'message' => 'Success'
        ]);
    }

    public function updateOneProduct(Request $request,$id){
        // tawfeek resends everything
        //TODO what if theu want to edit photo
        $product =Product::find($id);
        DB::table('products')
        ->where('id',$id)
        ->update($request->all());
        return response()->json([
            'message' => 'Sucess'
        ]);
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
            'message' => 'Success',
        ]);
    }

    public function viewProduct($id){
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
        return response() -> json([
            'msg' => 'success',
            'viewed' => $views
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
