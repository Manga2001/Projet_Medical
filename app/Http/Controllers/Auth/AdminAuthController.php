<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    /**
     * Connexion d'un administrateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email et mot de passe requis',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = User::where('email', $request->email)
                        ->where('role', 'admin')
                        ->where('is_active', true)
                        ->first();

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            // Supprimer les anciens tokens
            $admin->tokens()->delete();

            // Créer un nouveau token
            $token = $admin->createToken('admin-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie',
                'user' => $admin,
                'token' => $token,
                'user_type' => 'admin'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion d'un administrateur
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Profil de l'administrateur connecté
     */
    public function profile(Request $request)
    {
        try {
            $admin = $request->user();
            
            // Vérifier que c'est bien un User (Admin)
            if (!$admin instanceof \App\Models\User) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'user' => $admin,
                'user_type' => 'admin'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}