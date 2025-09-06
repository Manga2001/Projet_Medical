<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentCreated;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentReminder;
use App\Mail\PaymentConfirmation;
use App\Models\Appointment;
use App\Models\Payment;

class NotificationController extends Controller
{
    /**
     * Envoyer un email de création de rendez-vous
     */
    public function sendAppointmentCreated($appointmentId)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor.specialty'])
                                    ->findOrFail($appointmentId);

            Mail::to($appointment->patient->email)
                ->send(new AppointmentCreated($appointment));

            return response()->json([
                'status' => 'success',
                'message' => 'Email de création envoyé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un email de confirmation de rendez-vous
     */
    public function sendAppointmentConfirmed($appointmentId)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor.specialty'])
                                    ->findOrFail($appointmentId);

            if ($appointment->status !== 'confirmed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le rendez-vous doit être confirmé pour envoyer cet email'
                ], 400);
            }

            Mail::to($appointment->patient->email)
                ->send(new AppointmentConfirmed($appointment));

            return response()->json([
                'status' => 'success',
                'message' => 'Email de confirmation envoyé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un email de rappel
     */
    public function sendAppointmentReminder($appointmentId, Request $request)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor.specialty'])
                                    ->findOrFail($appointmentId);

            $hoursUntil = $request->input('hours_until', 24);

            Mail::to($appointment->patient->email)
                ->send(new AppointmentReminder($appointment, $hoursUntil));

            return response()->json([
                'status' => 'success',
                'message' => "Email de rappel envoyé ({$hoursUntil}h avant)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un email de confirmation de paiement
     */
    public function sendPaymentConfirmation($paymentId)
    {
        try {
            $payment = Payment::with(['appointment.patient', 'appointment.doctor'])
                             ->findOrFail($paymentId);

            if ($payment->status !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le paiement doit être complété pour envoyer cet email'
                ], 400);
            }

            Mail::to($payment->appointment->patient->email)
                ->send(new PaymentConfirmation($payment));

            return response()->json([
                'status' => 'success',
                'message' => 'Email de confirmation de paiement envoyé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer des rappels automatiques pour les rendez-vous du lendemain
     */
    public function sendAutomaticReminders()
    {
        try {
            $tomorrow = now()->addDay();
            
            $appointments = Appointment::with(['patient', 'doctor.specialty'])
                                     ->whereDate('appointment_date', $tomorrow->toDateString())
                                     ->where('status', 'confirmed')
                                     ->get();

            $sentCount = 0;
            $errors = [];

            foreach ($appointments as $appointment) {
                try {
                    Mail::to($appointment->patient->email)
                        ->send(new AppointmentReminder($appointment, 24));
                    $sentCount++;
                } catch (\Exception $e) {
                    $errors[] = "Erreur RDV {$appointment->reference}: " . $e->getMessage();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Rappels automatiques traités",
                'data' => [
                    'total_appointments' => $appointments->count(),
                    'emails_sent' => $sentCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi des rappels automatiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la configuration email
     */
    public function testEmailConfiguration()
    {
        try {
            $config = [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ];

            // Masquer les informations sensibles
            $config['username'] = $config['username'] ? 
                substr($config['username'], 0, 3) . '***' . substr($config['username'], -3) : 
                'Non configuré';

            return response()->json([
                'status' => 'success',
                'message' => 'Configuration email récupérée',
                'config' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sendTestEmail(Request $request)
    {
        try {
            $testEmail = $request->input('email', 'test@example.com');
            
            Mail::raw('Ceci est un email de test depuis la plateforme médicale.', function($message) use ($testEmail) {
                $message->to($testEmail)
                       ->subject('🧪 Test Email - Plateforme Médicale')
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return response()->json([
                'status' => 'success',
                'message' => "Email de test envoyé à {$testEmail}"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}