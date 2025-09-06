<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    /**
     * Liste des rendez-vous (pour le patient connecté)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = null;

            if ($user instanceof \App\Models\Patient) {
                $query = $user->appointments();
            } elseif ($user instanceof \App\Models\Doctor) {
                $query = $user->appointments();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non autorisé'
                ], 403);
            }

            $appointments = $query->with([
                'patient' => function($q) {
                    $q->select('id', 'first_name', 'last_name', 'phone');
                },
                'doctor' => function($q) {
                    $q->select('id', 'first_name', 'last_name', 'specialty_id')
                      ->with('specialty:id,name');
                }
            ])
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous récupérés avec succès',
                'data' => $appointments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau rendez-vous (Patient uniquement)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date_format:Y-m-d H:i:s|after:now',
            'reason' => 'required|string|max:500',
            'payment_method' => 'required|in:online,cash'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patient = $request->user();
            
            // Vérifier que c'est bien un patient
            if (!$patient instanceof \App\Models\Patient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seuls les patients peuvent prendre rendez-vous'
                ], 403);
            }

            // Vérifier que le médecin existe et est actif
            $doctor = Doctor::where('id', $request->doctor_id)
                           ->where('is_active', true)
                           ->first();

            if (!$doctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Médecin non trouvé ou inactif'
                ], 404);
            }

            // Vérifier si le créneau est disponible
            $appointmentDate = $request->appointment_date;
            $existingAppointment = Appointment::where('doctor_id', $doctor->id)
                                            ->where('appointment_date', $appointmentDate)
                                            ->whereIn('status', ['confirmed', 'pending'])
                                            ->first();

            if ($existingAppointment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce créneau n\'est plus disponible'
                ], 409);
            }

            // Créer le rendez-vous
            $appointment = Appointment::create([
                'reference' => Appointment::generateReference(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $appointmentDate,
                'status' => 'pending',
                'reason' => $request->reason,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'amount' => $doctor->consultation_fee
            ]);

            // Charger les relations pour la réponse
            $appointment = Appointment::with([
                'doctor' => function($q) {
                    $q->select('id', 'first_name', 'last_name', 'consultation_fee', 'specialty_id')
                      ->with('specialty:id,name');
                },
                'patient' => function($q) {
                    $q->select('id', 'first_name', 'last_name', 'phone');
                }
            ])->find($appointment->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous créé avec succès',
                'data' => $appointment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du rendez-vous',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Détails d'un rendez-vous
     */
    public function show($id, Request $request)
    {
        try {
            $user = $request->user();
            $query = Appointment::with(['patient', 'doctor.specialty']);

            // Filtrer selon le type d'utilisateur
            if ($user instanceof \App\Models\Patient) {
                $query->where('patient_id', $user->id);
            } elseif ($user instanceof \App\Models\Doctor) {
                $query->where('doctor_id', $user->id);
            } else {
                // Admin peut voir tous les rendez-vous
            }

            $appointment = $query->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous récupéré avec succès',
                'data' => $appointment
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un rendez-vous
     */
    public function cancel($id, Request $request)
    {
        try {
            $user = $request->user();
            $query = Appointment::query();

            // Filtrer selon le type d'utilisateur
            if ($user instanceof \App\Models\Patient) {
                $query->where('patient_id', $user->id);
            } elseif ($user instanceof \App\Models\Doctor) {
                $query->where('doctor_id', $user->id);
            } else {
                // Admin peut annuler tous les rendez-vous
            }

            $appointment = $query->findOrFail($id);

            if ($appointment->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce rendez-vous est déjà annulé'
                ], 400);
            }

            if ($appointment->status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible d\'annuler un rendez-vous terminé'
                ], 400);
            }

            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous annulé avec succès',
                'data' => $appointment
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'annulation du rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer un rendez-vous (Médecin uniquement)
     */
    public function confirm($id, Request $request)
    {
        try {
            $doctor = $request->user();
            
            if (!$doctor instanceof \App\Models\Doctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seuls les médecins peuvent confirmer les rendez-vous'
                ], 403);
            }

            $appointment = Appointment::where('doctor_id', $doctor->id)
                                    ->findOrFail($id);

            if ($appointment->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Seuls les rendez-vous en attente peuvent être confirmés'
                ], 400);
            }

            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);

            // Envoyer l'email de confirmation
            try {
                Mail::to($appointment->patient->email)
                    ->send(new \App\Mail\AppointmentConfirmed($appointment));
            } catch (\Exception $e) {
                Log::warning('Erreur envoi email confirmation RDV: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous confirmé avec succès',
                'data' => $appointment
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la confirmation du rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}