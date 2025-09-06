<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    /**
     * Liste des spécialités actives
     */
    public function index()
    {
        try {
            $specialties = Specialty::active()
                ->withCount(['doctors as active_doctors_count' => function($query) {
                    $query->where('is_active', true);
                }])
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Spécialités récupérées avec succès',
                'data' => $specialties
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des spécialités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une spécialité avec ses médecins
     */
    public function show($id)
    {
        try {
            $specialty = Specialty::with(['doctors' => function($query) {
                $query->active()
                      ->select('id', 'first_name', 'last_name', 'city', 'consultation_fee', 'specialty_id')
                      ->orderBy('first_name');
            }])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Spécialité récupérée avec succès',
                'data' => $specialty
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Spécialité non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la spécialité',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}