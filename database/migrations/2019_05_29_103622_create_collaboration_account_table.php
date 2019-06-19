<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollaborationAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collaboration_account', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('users');
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('id')->on('user_auth')
                  ->onDelete('cascade');
            $table->string('email');
            $table->string('token');
            $table->string('refresh_token');
            $table->date('expiry');
            $table->boolean('status');
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
        Schema::dropIfExists('collaboration_account');
    }
}
