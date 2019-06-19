<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')->on('user_auth')
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('designation')->nullable();
            $table->string('mobile',15);
            $table->integer('status')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('region');
            $table->string('country');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_details');
    }
}
