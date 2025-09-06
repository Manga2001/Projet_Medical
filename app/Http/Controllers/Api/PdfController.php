<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    /**
     * Vérifier les permissions d'accès à un rendez-vous
     */
    private function canAccessAppointment($user, $appointment)
    {
        // Si c'est un patient, vérifier que c'est son RDV
        if ($user instanceof \App\Models\Patient) {
            return $appointment->patient_id === $user->id;
        }
        
        // Si c'est un médecin, vérifier que c'est son RDV
        if ($user instanceof \App\Models\Doctor) {
            return $appointment->doctor_id === $user->id;
        }
        
        // Si c'est un admin, il a accès à tout
        if ($user instanceof \App\Models\User) {
            return isset($user->role) && $user->role === 'admin';
        }
        
        return false;
    }

    /**
     * Prévisualiser le justificatif (HTML)
     */
    public function previewAppointmentReceipt($appointmentId, Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }
            
            // Récupérer le rendez-vous avec toutes les relations
            $appointment = Appointment::with([
                'patient',
                'doctor',
                'doctor.specialty',
                'payments' => function($query) {
                    $query->where('status', 'completed')->latest();
                }
            ])->find($appointmentId);

            if (!$appointment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rendez-vous non trouvé'
                ], 404);
            }

            // Vérifier que les relations existent
            if (!$appointment->patient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Patient non trouvé pour ce rendez-vous'
                ], 400);
            }

            if (!$appointment->doctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Médecin non trouvé pour ce rendez-vous'
                ], 400);
            }

            if (!$appointment->doctor->specialty) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Spécialité non trouvée pour ce médecin'
                ], 400);
            }

            // Vérifier les permissions
            if (!$this->canAccessAppointment($user, $appointment)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non autorisé à accéder à ce justificatif',
                    'debug' => [
                        'user_type' => get_class($user),
                        'user_id' => $user->id,
                        'user_role' => $user->role ?? 'N/A',
                        'appointment_patient_id' => $appointment->patient_id,
                        'appointment_doctor_id' => $appointment->doctor_id
                    ]
                ], 403);
            }

            // Préparer les données sécurisées pour la vue
            $data = [
                'appointment' => $appointment,
                'patient' => $appointment->patient,
                'doctor' => $appointment->doctor,
                'specialty' => $appointment->doctor->specialty,
                'payment' => $appointment->payments->first(),
                'generated_at' => now(),
                'qr_code_data' => url("/api/appointments/{$appointment->id}/verify?ref={$appointment->reference}"),
                'preview' => true
            ];

            return view('pdf.appointment-receipt', $data);

        } catch (\Exception $e) {
            Log::error('Erreur PDF Preview', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'appointment_id' => $appointmentId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la prévisualisation',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Générer et télécharger le justificatif PDF
     */
    public function generateAppointmentReceipt($appointmentId, Request $request)
    {
        try {
            $user = $request->user();
            
            $appointment = Appointment::with([
                'patient',
                'doctor.specialty',
                'payments' => function($query) {
                    $query->where('status', 'completed')->latest();
                }
            ])->find($appointmentId);

            if (!$appointment || !$this->canAccessAppointment($user, $appointment)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $data = [
                'appointment' => $appointment,
                'patient' => $appointment->patient,
                'doctor' => $appointment->doctor,
                'specialty' => $appointment->doctor->specialty,
                'payment' => $appointment->payments->first(),
                'generated_at' => now(),
                'qr_code_data' => url("/api/appointments/{$appointment->id}/verify?ref={$appointment->reference}")
            ];

            $pdf = Pdf::loadView('pdf.appointment-receipt', $data)->setPaper('a4');
            $filename = "justificatif_rdv_{$appointment->reference}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur génération PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un reçu de paiement
     */
    public function generatePaymentReceipt($paymentId, Request $request)
    {
        try {
            $user = $request->user();
            
            $payment = \App\Models\Payment::with([
                'appointment.patient',
                'appointment.doctor.specialty'
            ])->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paiement non trouvé'
                ], 404);
            }

            if (!$this->canAccessAppointment($user, $payment->appointment)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            if ($payment->status !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Paiement non complété'
                ], 400);
            }

            $data = [
                'payment' => $payment,
                'appointment' => $payment->appointment,
                'patient' => $payment->appointment->patient,
                'doctor' => $payment->appointment->doctor,
                'generated_at' => now()
            ];

            $pdf = Pdf::loadView('pdf.payment-receipt', $data)->setPaper('a4');
            $filename = "recu_paiement_{$payment->transaction_id}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur génération reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier un rendez-vous par QR code (public)
     */
    public function verifyAppointment(Request $request, $id)
    {
        try {
            $ref = $request->query('ref');
            
            $appointment = Appointment::with(['patient', 'doctor.specialty'])
                                    ->where('id', $id)
                                    ->where('reference', $ref)
                                    ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rendez-vous non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Rendez-vous vérifié',
                'data' => [
                    'reference' => $appointment->reference,
                    'patient_name' => $appointment->patient->full_name,
                    'doctor_name' => $appointment->doctor->full_name,
                    'specialty' => $appointment->doctor->specialty->name,
                    'appointment_date' => $appointment->appointment_date,
                    'status' => $appointment->status,
                    'verified_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}