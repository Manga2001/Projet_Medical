<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    /**
     * Liste des médecins avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = Doctor::with('specialty')
                          ->active();

            // Filtres
            if ($request->has('specialty_id')) {
                $query->bySpecialty($request->specialty_id);
            }

            if ($request->has('city')) {
                $query->byCity($request->city);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            $doctors = $query->select([
                'id', 'first_name', 'last_name', 'city', 'address',
                'consultation_fee', 'bio', 'specialty_id', 'available_hours'
            ])
            ->orderBy('first_name')
            ->paginate(15);

            return response()->json([
                'status' => 'success',
                'message' => 'Médecins récupérés avec succès',
                'data' => $doctors
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des médecins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un médecin
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with(['specialty'])
                           ->where('is_active', true)
                           ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Médecin récupéré avec succès',
                'data' => $doctor
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Médecin non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du médecin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créneaux disponibles d'un médecin
     */
    public function availableSlots($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Date invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $doctor = Doctor::findOrFail($id);
            $date = $request->date;
            $dayOfWeek = strtolower(date('l', strtotime($date))); // monday, tuesday, etc.

            // Récupérer les heures de disponibilité du médecin
            $availableHours = $doctor->available_hours[$dayOfWeek] ?? [];

            if (empty($availableHours)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Aucun créneau disponible pour cette date',
                    'data' => []
                ], 200);
            }

            // Récupérer les rendez-vous déjà pris pour cette date
            $bookedSlots = $doctor->appointments()
                                 ->whereDate('appointment_date', $date)
                                 ->whereIn('status', ['confirmed', 'pending'])
                                 ->pluck('appointment_date')
                                 ->map(function($datetime) {
                                     return date('H:i', strtotime($datetime));
                                 })
                                 ->toArray();

            // Générer les créneaux disponibles
            $availableSlots = [];
            foreach ($availableHours as $timeRange) {
                list($start, $end) = explode('-', $timeRange);
                $current = strtotime($start);
                $endTime = strtotime($end);

                while ($current < $endTime) {
                    $timeSlot = date('H:i', $current);
                    
                    if (!in_array($timeSlot, $bookedSlots)) {
                        $availableSlots[] = [
                            'time' => $timeSlot,
                            'datetime' => $date . ' ' . $timeSlot . ':00',
                            'available' => true
                        ];
                    }

                    $current += 30 * 60; // Créneaux de 30 minutes
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Créneaux récupérés avec succès',
                'data' => [
                    'doctor_id' => $doctor->id,
                    'doctor_name' => $doctor->full_name,
                    'date' => $date,
                    'day' => $dayOfWeek,
                    'available_slots' => $availableSlots,
                    'total_slots' => count($availableSlots)
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Médecin non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des créneaux',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}