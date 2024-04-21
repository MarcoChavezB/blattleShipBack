<?php

namespace App\Http\Controllers;


use App\Events\TestEvent;
use App\Events\NotifyEvent;
use App\Events\AlertWinner;
use App\Events\AlertEvent;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Util\Test;

class GameController extends Controller
{


    function sendNotify(Request $request){
        $player_id = Auth::user()->id;

        event(new AlertEvent("Se destruyo un barco rival!", $player_id));
        return response()->json([
            'msg' => 'Notification sent successfully',
        ]);
    }

    public function queueGame(){
        $player_id = Auth::user()->id;

        $existingGameAsP1 = game::where('player1_id', $player_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        $existingGameAsP2 = game::where('player2_id', $player_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        if ($existingGameAsP1 || $existingGameAsP2) {
            return response()->json([
                'msg' => 'You already have a game in progress or in queue. Please finish it before starting a new one.',
            ], 400);
        }

        $game = new Game();
        $game->player1_id = $player_id;
        $game->save();

        return response()->json([
            'msg' => 'Game queued successfully',
            'gameId' => $game->id,
        ]);
    }

    public function cancelRandomQueue(Request $request){
        $player_id = Auth::user()->id;

        Cache::put($player_id, 'cancelled', 1);

        return response()->json([
            'msg' => 'Game search cancelled',
        ], 200);
    }

    public function joinRandomGame(Request $request){
        $player2_id = Auth::user()->id;

        $existingGameAsPlayerOne = game::where('player1_id', $player2_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        $existingGameAsPlayerTwo = game::where('player2_id', $player2_id)
            ->whereIn('status', ['playing', 'queue'])
            ->first();

        if ($existingGameAsPlayerTwo || $existingGameAsPlayerOne) {
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

        try {
            event(new TestEvent(['gameId' => $random_game->id, 'players' => [$random_game->player1_id, $random_game->player2_id]]));
            Log::info('El evento TestEvent se ha enviado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al emitir el evento TestEvent: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json([
            'game_found' => true,
            'msg' => 'Game started successfully',
            'players' => [$random_game->player1_id, $random_game->player2_id],
            'turn' => $random_game->player1_id,
            'gameId' => $random_game->id,
        ]);
    }

    public function endGame(Request $request){
        $validator = Validator::make($request->all(), [
            'losser_id' => 'required|integer|exists:users,id',
            'gameId' => 'required|integer|exists:games,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors    " => $validator->errors()], 400);
        }

        $game_id = $request->gameId;
        $losser_id = $request->losser_id;

        $game = game::find($game_id);
        $game->status = 'finished';

        if ($game->player1_id == $losser_id) {
            $game->winner_id = $game->player2_id;
        }
        else if ($game->player2_id == $losser_id) {
            $game->winner_id = $game->player1_id;
        }

        $game->save();
        event(new AlertWinner('Felicidades Ganaste el juego!', $game->winner_id));

        return response()->json([
            'msg' => 'Game ended successfully',
            'game_id' => $game->id,
            'winner_id' => $game->winner_id,
        ]);
    }

    public function myGameHistory(Request $request){
        $player_id = Auth::user()->id;
        $perPage = 5;

        $games = Game::where('player1_id', $player_id)
            ->orWhere('player2_id', $player_id)
            ->join('users as player1', 'games.player1_id', '=', 'player1.id')
            ->join('users as player2', 'games.player2_id', '=', 'player2.id')
            ->join('users as winner', 'games.winner_id', '=', 'winner.id')
            ->select('games.id', 'games.status', 'games.created_at', 'player1.id as player1_id', 'player2.id as player2_id', 'winner.id as winner_id', 'player1.name as player1_name',  'player2.name as player2_name', 'winner.name as winner_name')
            ->where('status', 'finished')
            ->paginate($perPage);

        foreach ($games as $game) {
            $game->player_id = $player_id;
        }

        return response()->json([
            'page' =>$games->currentPage(),
            'games' => $games->items(),
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
        if ($game->status != 'queue'){
            return response()->json([
                'msg' => 'Game is not in queue',
            ], 400);
        }
        $game->delete();

        return response()->json([
            'msg' => 'Game unqueued successfully',
            'game_id' => $game->id,
        ]);
    }

    public function sendBoard(Request $request){
        $validator = Validator::make($request->all(), [
            'gameId' => 'required|integer|exists:games,id',
            'board' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 400);
        }

        $game = game::find($request->gameId);
        if($game->status != 'playing'){
            return response()->json([
                'msg' => 'Game is not in progress',
            ], 400);
        }

        $turn = $request->turn;
        $newTurn = ($turn == 1) ? 2 : 1;

        event(new NotifyEvent($newTurn, $request->board));
    }
}
