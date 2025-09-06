<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - {{ $payment->transaction_id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #28a745;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .document-title {
            text-align: center;
            background: #d4edda;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
        }
        
        .document-title h2 {
            margin: 0;
            color: #155724;
            font-size: 18px;
        }
        
        .transaction-id {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
        }
        
        .amount-section {
            text-align: center;
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 2px solid #28a745;
        }
        
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
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
            color: #28a745;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 35%;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            vertical-align: top;
        }
        
        .success-stamp {
            position: absolute;
            top: 100px;
            right: 50px;
            transform: rotate(-15deg);
            border: 3px solid #28a745;
            color: #28a745;
            font-weight: bold;
            font-size: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body { margin: 0; }
            .info-section { break-inside: avoid; }
            .success-stamp { position: fixed; }
        }
    </style>
</head>
<body>
    <div class="success-stamp">PAYÉ</div>
    
    <div class="header">
        <h1>🏥 PLATEFORME MÉDICALE</h1>
        <div class="subtitle">Reçu de Paiement Officiel</div>
    </div>
    
    <div class="document-title">
        <h2>💳 REÇU DE PAIEMENT</h2>
    </div>
    
    <div class="transaction-id">
        Transaction ID : {{ $payment->transaction_id }}
    </div>
    
    <div class="amount-section">
        <div style="font-size: 16px; color: #666; margin-bottom: 5px;">MONTANT PAYÉ</div>
        <div class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">
            Payé le {{ $payment->paid_at->format('d/m/Y à H:i') }}
        </div>
    </div>
    
    <!-- Détails du Paiement -->
    <div class="info-section">
        <h3>💰 DÉTAILS DU PAIEMENT</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Mode de paiement :</div>
                <div class="info-value">{{ ucfirst($payment->payment_method) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Passerelle :</div>
                <div class="info-value">{{ ucfirst($payment->gateway) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut :</div>
                <div class="info-value" style="color: #28a745; font-weight: bold;">✅ COMPLÉTÉ</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de paiement :</div>
                <div class="info-value">{{ $payment->paid_at->format('d/m/Y à H:i') }}</div>
            </div>
        </div>
    </div>
    
    <!-- Informations du Rendez-vous -->
    <div class="info-section">
        <h3>📅 RENDEZ-VOUS ASSOCIÉ</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Référence RDV :</div>
                <div class="info-value">{{ $appointment->reference }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date du RDV :</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Médecin :</div>
                <div class="info-value">{{ $doctor->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Spécialité :</div>
                <div class="info-value">{{ $doctor->specialty->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse cabinet :</div>
                <div class="info-value">{{ $doctor->address }}, {{ $doctor->city }}</div>
            </div>
        </div>
    </div>
    
    <!-- Informations Patient -->
    <div class="info-section">
        <h3>👤 INFORMATIONS PATIENT</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet :</div>
                <div class="info-value">{{ $patient->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email :</div>
                <div class="info-value">{{ $patient->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone :</div>
                <div class="info-value">{{ $patient->phone }}</div>
            </div>
        </div>
    </div>
    
    <!-- Section de garantie -->
    <div class="info-section" style="background: #fff3cd; border-color: #ffeaa7;">
        <h3 style="color: #856404;">⚠️ INFORMATIONS IMPORTANTES</h3>
        <div style="font-size: 11px; color: #856404; line-height: 1.6;">
            <p style="margin: 5px 0;">• Ce reçu fait foi de paiement pour la consultation médicale.</p>
            <p style="margin: 5px 0;">• Conservez ce document pour vos dossiers personnels.</p>
            <p style="margin: 5px 0;">• En cas de questions, contactez notre service client.</p>
            <p style="margin: 5px 0;">• Ce paiement est non remboursable sauf cas de force majeure.</p>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>Reçu généré le :</strong> {{ $generated_at->format('d/m/Y à H:i') }}</p>
        <p style="margin: 5px 0;">Ce reçu confirme que le paiement a été effectué avec succès.</p>
        <p style="margin: 5px 0;">Conservez ce document comme preuve de paiement officielle.</p>
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #dee2e6;">
        <p style="font-weight: bold;">📧 Email: support@medical-app.com | 📞 Tel: +221 77 XXX XX XX</p>
        <p>© {{ date('Y') }} Plateforme Médicale - Tous droits réservés</p>
    </div>
</body>
</html>