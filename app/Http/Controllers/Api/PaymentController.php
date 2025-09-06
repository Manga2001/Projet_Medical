<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Initier un paiement
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'payment_method' => 'required|in:card,mobile_money,bank_transfer',
            'gateway' => 'required|in:stripe,cinetpay,simulator'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $appointment = Appointment::with(['patient', 'doctor'])
                                    ->findOrFail($request->appointment_id);

            // Vérifier que l'utilisateur peut payer ce rendez-vous
            if ($user instanceof \App\Models\Patient && $appointment->patient_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non autorisé à payer ce rendez-vous'
                ], 403);
            }

            // Vérifier que le rendez-vous peut être payé
            if ($appointment->payment_status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce rendez-vous est déjà payé'
                ], 400);
            }

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'transaction_id' => Payment::generateTransactionId(),
                'amount' => $appointment->amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'gateway' => $request->gateway
            ]);

            // Traiter selon la gateway
            switch ($request->gateway) {
                case 'stripe':
                    return $this->processStripePayment($payment, $request);
                
                case 'cinetpay':
                    return $this->processCinetPayPayment($payment, $request);
                
                case 'simulator':
                    return $this->processSimulatedPayment($payment, $request);
                
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gateway de paiement non supporté'
                    ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'initiation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traitement Stripe
     */
    private function processStripePayment($payment, $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $payment->amount * 100, // Stripe utilise les centimes
                'currency' => 'xof', // Franc CFA
                'metadata' => [
                    'appointment_id' => $payment->appointment_id,
                    'transaction_id' => $payment->transaction_id
                ]
            ]);

            $payment->update([
                'gateway_response' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Paiement Stripe initié',
                'data' => [
                    'payment_id' => $payment->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'publishable_key' => env('STRIPE_KEY')
                ]
            ]);

        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Traitement CinetPay
     */
    private function processCinetPayPayment($payment, $request)
    {
        try {
            $data = [
                'apikey' => env('CINETPAY_API_KEY'),
                'site_id' => env('CINETPAY_SITE_ID'),
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => 'XOF',
                'description' => 'Paiement consultation médicale',
                'return_url' => url('/api/payments/cinetpay/success'),
                'notify_url' => url('/api/payments/cinetpay/notify'),
                'metadata' => json_encode([
                    'appointment_id' => $payment->appointment_id
                ])
            ];

            $response = Http::post('https://api-checkout.cinetpay.com/v2/payment', $data);
            $result = $response->json();

            if ($response->successful() && $result['code'] === '201') {
                $payment->update([
                    'gateway_response' => $result
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Paiement CinetPay initié',
                    'data' => [
                        'payment_id' => $payment->id,
                        'payment_url' => $result['data']['payment_url'],
                        'payment_token' => $result['data']['payment_token']
                    ]
                ]);
            } else {
                throw new \Exception('Erreur CinetPay: ' . ($result['message'] ?? 'Erreur inconnue'));
            }

        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Simulateur de paiement (pour les tests)
     */
    private function processSimulatedPayment($payment, $request)
    {
        try {
            // Simuler un délai de traitement
            sleep(1);

            // Simuler un succès (90% de chance de succès)
            $success = rand(1, 10) <= 9;

            if ($success) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => [
                        'simulated' => true,
                        'success' => true,
                        'transaction_ref' => 'SIM_' . strtoupper(uniqid())
                    ]
                ]);

                // Mettre à jour le statut du rendez-vous
                $payment->appointment->update([
                    'payment_status' => 'paid'
                ]);

                // Envoyer l'email de confirmation de paiement
                try {
                    Mail::to($payment->appointment->patient->email)
                        ->send(new \App\Mail\PaymentConfirmation($payment));
                } catch (\Exception $e) {
                    Log::warning('Erreur envoi email confirmation paiement: ' . $e->getMessage());
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Paiement simulé avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'amount' => $payment->amount,
                        'status' => 'completed',
                        'simulated' => true
                    ]
                ]);
            } else {
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => [
                        'simulated' => true,
                        'success' => false,
                        'error' => 'Paiement refusé par la banque simulée'
                    ]
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Paiement simulé échoué',
                    'data' => [
                        'payment_id' => $payment->id,
                        'error' => 'Paiement refusé par la banque'
                    ]
                ], 402);
            }

        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($paymentId, Request $request)
    {
        try {
            $user = $request->user();
            $payment = Payment::with('appointment.patient')->findOrFail($paymentId);

            // Vérifier les permissions
            if ($user instanceof \App\Models\Patient && 
                $payment->appointment->patient_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Non autorisé'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'gateway' => $payment->gateway,
                    'paid_at' => $payment->paid_at,
                    'appointment' => [
                        'id' => $payment->appointment->id,
                        'reference' => $payment->appointment->reference,
                        'status' => $payment->appointment->status
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook CinetPay
     */
    public function cinetPayNotify(Request $request)
    {
        try {
            $data = $request->all();
            
            if ($data['cpm_result'] === '00') { // Succès
                $payment = Payment::where('transaction_id', $data['cpm_trans_id'])->first();
                
                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'gateway_response' => $data
                    ]);

                    $payment->appointment->update([
                        'payment_status' => 'paid'
                    ]);
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Liste des paiements
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user instanceof \App\Models\Patient) {
                $payments = Payment::whereHas('appointment', function($q) use ($user) {
                    $q->where('patient_id', $user->id);
                })->with('appointment.doctor')->latest()->paginate(20);
            } elseif ($user instanceof \App\Models\Doctor) {
                $payments = Payment::whereHas('appointment', function($q) use ($user) {
                    $q->where('doctor_id', $user->id);
                })->with('appointment.patient')->latest()->paginate(20);
            } else {
                // Admin - tous les paiements
                $payments = Payment::with(['appointment.patient', 'appointment.doctor'])
                                 ->latest()->paginate(20);
            }

            return response()->json([
                'status' => 'success',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}