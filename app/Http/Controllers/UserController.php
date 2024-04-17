<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function login(Request $reques){
        $validate = Validator::make($reques->all(), [
            'email' => 'required|email|min:3|max:255',
            'password' => 'required|min:6|max:255'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'password.required' => 'Password is required'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors()
            ], 400);
        }

        if(!auth()->attempt($reques->all())){
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = auth()->user()->createToken('personal_token');

        return response()->json([
            'status' => 'success',
            'message' => 'Login success',
            'data' => [
                'token' => $token->plainTextToken
            ]
        ], 200);
    }

    function register(Request $request){
        $validate = Validator::make($request->all(), [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|min:6|max:255|unique:users',
            'password' => 'required|min:6|max:255'
        ], [
            'name.required' => 'El nombre es requerido',
            'name.min' => 'El nombre es muy corto',
            'name.max' => 'El nombre es muy largo',
            'email.required' => 'El email es requerido',
            'email.email' => 'El email es invalido',
            'email.min' => 'El email es muy corto',
            'email.max' => 'El email es muy largo',
            'email.unique' => 'El email ya esta en uso',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña es muy corta',
            'password.max' => 'La contraseña es muy larga'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'error',
                'message' => $validate->errors()
            ], 400);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Register success'
        ], 200);
    }
}
