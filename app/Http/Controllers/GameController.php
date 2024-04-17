<?php

namespace App\Http\Controllers;

use App\Models\game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    public function queueGame(Request $request){
        $validator = Validator::make($request->all(), [
            'player1_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $player1_id = $request->player1_id;
        //$player1_id = Auth::user()->id;

        $existingGame = game::where('player1_id', $player1_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        if ($existingGame) {
            return response()->json([
                'msg' => 'You already have a game in progress or in queue. Please finish it before starting a new one.',
            ], 400);
        }

        $game = new game();
        $game->player1_id = $player1_id;
        $game->save();

        return response()->json([
            'msg' => 'Game queued successfully',
            'gameId' => $game->id,
        ]);
    }

    public function cancelRandomQueue(Request $request){
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $player_id = $request->player_id;
        //$player_id = Auth::user()->id;

        Cache::put($player_id, 'cancelled', 1);

        return response()->json([
            'msg' => 'Game search cancelled',
        ], 200);
    }

    public function joinRandomGame(Request $request){
        $validator = Validator::make($request->all(), [
            'player2_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $player2_id = $request->player2_id;
        //$player2_id = Auth::user()->id;

        $existingGame = game::where('player2_id', $player2_id)
            ->orWhere('player1_id', $player2_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        if ($existingGame) {
            return response()->json([
                'msg' => 'You already have a game in progress or in queue. Please finish it before starting a new one.',
            ], 400);
        }

        $random_game = game::where('status', 'queue')->first();
        if (!$random_game) {
            return response()->json([
                'game_found' => false,
                'msg' => 'No games in queue',
            ], 400);
        }

        $random_game->player2_id = $player2_id;
        $random_game->status = 'playing';
        $random_game->save();

        return response()->json([
            'game_found' => true,
            'msg' => 'Game started successfully',
            'players' => [$random_game->player1_id, $random_game->player2_id],
            'gameId' => $random_game->id,
        ]);
    }

    public function endGame(Request $request){
        $validator = Validator::make($request->all(), [
            'winner_id' => 'required|integer|exists:users,id',
            'gameId' => 'required|integer|exists:games,id',

        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $game_id = $request->game_id;
        $winner_id = $request->winner_id;
        //$winnder_id = Auth::user()->id;

        $game = game::find($game_id);
        $game->status = 'finished';
        $game->winner_id = $winner_id;
        $game->save();

        return response()->json([
            'msg' => 'Game ended successfully',
            'game_id' => $game->id,
        ]);
    }

    public function myGameHistory(Request $request){
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $player_id = $request->player_id;
        //$player_id = Auth::user()->id;

        $games = game::where('player1_id', $player_id)
            ->orWhere('player2_id', $player_id)
            ->get();

        if ($games->count() == 0){
            return response()->json([
                'msg' => 'No games found',
                'games' => [],
            ]);
        }

        return response()->json([
            'msg' => 'Game history retrieved successfully',
            'games' => $games,
        ]);
    }

    public function dequeueGame(Request $request){
        $validator = Validator::make($request->all(), [
            'gameId' => 'required|integer|exists:games,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $gameId = $request->gameId;
        //$player_id = Auth::user()->id;

        $game = game::find($gameId);
        $game->delete();

        return response()->json([
            'msg' => 'Game unqueued successfully',
            'game_id' => $game->id,
        ]);
    }

}
