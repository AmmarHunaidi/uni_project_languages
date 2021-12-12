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
            $table->text('image_url')->nullable();
            $table->date('expires_at'); // yyyy-mm-dd
            $table->string('contact_info');
            $table->string('description'); // maybe switch to text?
            $table->integer('product_count')->default(1);
            $table->integer('days_before_discount_1');
            $table->integer('discount_1');
            $table->integer('days_before_discount_2');
            $table->integer('discount_2');
            $table->json('viewed_users');
            $table->json('liked_users');
            $table->json('comments');
            $table->double('price'); // 6 decimal numbers and 5 number after decimal point
            $table->foreignId('user_id')->constrained();
            $table->foreignId('type_id')->constrained();
            $table->softDeletes();
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
