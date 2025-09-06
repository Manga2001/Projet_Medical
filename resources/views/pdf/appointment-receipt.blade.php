<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justificatif de Rendez-vous - {{ $appointment->reference ?? 'N/A' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2c5aa0;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .document-title {
            text-align: center;
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .document-title h2 {
            margin: 0;
            color: #2c5aa0;
            font-size: 18px;
        }
        
        .reference {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
            margin: 10px 0;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .info-section h3 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 35%;
            vertical-align: top;
        }
        
        .info-value {
            display: inline-block;
            width: 60%;
            vertical-align: top;
        }
        
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        
        .payment-info {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .verification-url {
            font-size: 10px;
            word-break: break-all;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 PLATEFORME MÉDICALE</h1>
        <div class="subtitle">Système de Gestion des Rendez-vous Médicaux</div>
    </div>
    
    <div class="document-title">
        <h2>📋 JUSTIFICATIF DE RENDEZ-VOUS</h2>
    </div>
    
    <div class="reference">
        N° de référence : {{ $appointment->reference ?? 'N/A' }}
    </div>
    
    <!-- Informations Patient -->
    <div class="info-section">
        <h3>👤 INFORMATIONS PATIENT</h3>
        <div class="info-row">
            <span class="info-label">Nom complet :</span>
            <span class="info-value">{{ $patient->full_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email :</span>
            <span class="info-value">{{ $patient->email ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $patient->phone ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de naissance :</span>
            <span class="info-value">
                @if($patient && $patient->date_of_birth)
                    {{ $patient->date_of_birth->format('d/m/Y') }}
                @else
                    Non renseigné
                @endif
            </span>
        </div>
    </div>
    
    <!-- Informations Médecin -->
    <div class="info-section">
        <h3>👨‍⚕️ INFORMATIONS MÉDECIN</h3>
        <div class="info-row">
            <span class="info-label">Médecin :</span>
            <span class="info-value">{{ $doctor->full_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Spécialité :</span>
            <span class="info-value">{{ $specialty->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Adresse cabinet :</span>
            <span class="info-value">{{ $doctor->address ?? 'N/A' }}, {{ $doctor->city ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $doctor->phone ?? 'N/A' }}</span>
        </div>
    </div>
    
    <!-- Détails du Rendez-vous -->
    <div class="info-section">
        <h3>📅 DÉTAILS DU RENDEZ-VOUS</h3>
        <div class="info-row">
            <span class="info-label">Date et heure :</span>
            <span class="info-value">
                @if($appointment && $appointment->appointment_date)
                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}
                @else
                    N/A
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Motif :</span>
            <span class="info-value">{{ $appointment->reason ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut :</span>
            <span class="info-value">
                <span class="status status-{{ $appointment->status ?? 'pending' }}">
                    {{ $appointment->status_label ?? 'En attente' }}
                </span>
            </span>
        </div>
        @if($appointment && $appointment->confirmed_at)
        <div class="info-row">
            <span class="info-label">Confirmé le :</span>
            <span class="info-value">{{ $appointment->confirmed_at->format('d/m/Y à H:i') }}</span>
        </div>
        @endif
    </div>
    
    <!-- Informations de Paiement -->
    @if($payment && $payment->status === 'completed')
    <div class="payment-info">
        <h3 style="margin: 0 0 10px 0; color: #28a745;">💳 PAIEMENT EFFECTUÉ</h3>
        <div class="info-row">
            <span class="info-label">Montant :</span>
            <span class="info-value amount">{{ number_format($payment->amount ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="info-row">
            <span class="info-label">Mode de paiement :</span>
            <span class="info-value">{{ ucfirst($payment->payment_method ?? 'N/A') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Transaction ID :</span>
            <span class="info-value">{{ $payment->transaction_id ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payé le :</span>
            <span class="info-value">
                @if($payment && $payment->paid_at)
                    {{ $payment->paid_at->format('d/m/Y à H:i') }}
                @else
                    N/A
                @endif
            </span>
        </div>
    </div>
    @else
    <div class="info-section">
        <h3>💰 INFORMATIONS DE PAIEMENT</h3>
        <div class="info-row">
            <span class="info-label">Montant consultation :</span>
            <span class="info-value amount">{{ number_format($appointment->amount ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="info-row">
            <span class="info-label">Mode de paiement :</span>
            <span class="info-value">
                @if($appointment && $appointment->payment_method === 'online')
                    Paiement en ligne
                @else
                    Paiement au cabinet
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut paiement :</span>
            <span class="info-value">
                <span class="status status-{{ $appointment->payment_status ?? 'pending' }}">
                    {{ ucfirst($appointment->payment_status ?? 'pending') }}
                </span>
            </span>
        </div>
    </div>
    @endif
    
    <!-- Section de vérification -->
    <div class="qr-section">
        <h3 style="margin: 0 0 10px 0;">🔍 VÉRIFICATION DU DOCUMENT</h3>
        <p style="margin: 5px 0;">Utilisez le lien ci-dessous pour vérifier l'authenticité de ce document :</p>
        <div class="verification-url">{{ $qr_code_data ?? 'N/A' }}</div>
    </div>
    
    <div class="footer">
        <p><strong>Document généré le :</strong> 
            @if($generated_at)
                {{ $generated_at->format('d/m/Y à H:i') }}
            @else
                {{ now()->format('d/m/Y à H:i') }}
            @endif
        </p>
        <p>Ce document est un justificatif officiel de rendez-vous médical.</p>
        <p>© {{ date('Y') }} Plateforme Médicale - Tous droits réservés</p>
        
        @if(isset($preview) && $preview)
        <div style="background: #fff3cd; color: #856404; padding: 10px; margin-top: 20px; border-radius: 4px;">
            <strong>MODE PRÉVISUALISATION</strong> - Ce document n'est pas officiel
        </div>
        @endif
    </div>
</body>
</html>