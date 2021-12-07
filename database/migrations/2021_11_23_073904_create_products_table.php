<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('category');
          //  $table->mediumText('image');
            $table->date('expires_at'); // yyyy-mm-dd
            $table->string('contact_info');
            $table->integer('product_count');
            $table->array('views');
            $table->array('likes');
            $table->double('price'); // 6 decimal numbers and 5 number after decimal point
            //$table->unsignedBigInteger('user_id');
           // $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            // $table->unsignedBigInteger('category_id');
            // $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
