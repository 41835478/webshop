<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColloqueSondageReponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sondage_reponses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sondage_id');
            $table->integer('user_id');
            $table->string('email');
            $table->dateTime('sent_at');
            $table->dateTime('response_at');
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
        Schema::dropIfExists('sondage_reponses');
    }
}