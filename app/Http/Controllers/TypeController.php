<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Type;

class TypeController extends Controller
{
    public function index(){
        $types = Type::all();
        return response() -> json([
            'hits' => count($types),
            'types' => $types
        ]);
    }
    public function store(Request $request){
        $fields = $request->validate(['name' => 'required|string']);
        $type = new Type();
        $type->name = $fields['name'];
        $type->save();
        return response() -> json([
            'msg' => 'Success',
            'type' => $type
        ]);
    }
    public function update($id,Request $request){

    }
    public function destroy($id,Request $request){
        $type = Type::findOrFail($id);
        $type->delete();
        return response() -> json([
            'msg' => 'deleted succesfuly'
        ]);
    }
}
