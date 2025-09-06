<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorAuthController extends Controller
{
    /**
     * Connexion d'un médecin
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
            $doctor = Doctor::with('specialty')
                           ->where('email', $request->email)
                           ->where('is_active', true)
                           ->first();

            if (!$doctor || !Hash::check($request->password, $doctor->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            // Supprimer les anciens tokens
            $doctor->tokens()->delete();

            // Créer un nouveau token
            $token = $doctor->createToken('doctor-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie',
                'user' => $doctor,
                'token' => $token,
                'user_type' => 'doctor'
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
     * Déconnexion d'un médecin
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
     * Profil du médecin connecté
     */
    public function profile(Request $request)
    {
        try {
            $doctor = $request->user();
            
            // Vérifier que c'est bien un Doctor
            if (!$doctor instanceof \App\Models\Doctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non autorisé'
                ], 403);
            }
            
            $doctor->load([
                'specialty',
                'appointments' => function($query) {
                    $query->with('patient')
                          ->orderBy('appointment_date', 'desc')
                          ->take(10);
                }
            ]);

            return response()->json([
                'status' => 'success',
                'user' => $doctor,
                'user_type' => 'doctor'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour du profil médecin
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bio' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'consultation_fee' => 'sometimes|numeric|min:0',
            'available_hours' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $doctor = $request->user();
            $doctor->update($request->only([
                'bio', 'phone', 'address', 'city', 'consultation_fee', 'available_hours'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès',
                'user' => $doctor->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}