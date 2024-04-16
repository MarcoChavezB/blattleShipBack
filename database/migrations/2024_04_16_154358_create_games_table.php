<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

// GAMES
// CODE - STATUS(QUEUE - PLAYING - FINISHED) - PLAYER1_ID - PLAYER2_ID - WINNER_ID 
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('status', ['queue', 'playing', 'finished'])->default('queue');
            $table->bigInteger('player1_id');
            $table->bigInteger('player2_id')->nullable();
            $table->bigInteger('winner_id')->nullable();

            $table->foreign('player1_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('player2_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
};
