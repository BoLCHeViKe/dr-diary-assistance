<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if (!$user->activo) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta con el administrador.'
            ], 403);
        }

        $token = $user->createToken('dda-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('rol'),
        ]);
    }

    // POST /api/logout  (requiere auth:sanctum)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
