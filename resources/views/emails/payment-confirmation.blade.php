@extends('emails.layout')

@section('content')
    <div class="greeting">
        Parfait, {{ $patient->first_name }} !
    </div>
    
    <div class="alert alert-success">
        <strong>✅ Votre paiement a été confirmé avec succès</strong>
        <br>Votre rendez-vous est maintenant entièrement sécurisé.
    </div>
    
    <div class="info-card">
        <h3>💳 Détails du paiement</h3>
        <div class="info-row">
            <span class="info-label">Montant payé :</span>
            <span class="info-value"><strong>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Transaction ID :</span>
            <span class="info-value">{{ $payment->transaction_id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Mode de paiement :</span>
            <span class="info-value">{{ ucfirst($payment->payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date de paiement :</span>
            <span class="info-value">{{ $payment->paid_at->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut :</span>
            <span class="info-value" style="color: #28a745; font-weight: bold;">✅ CONFIRMÉ</span>
        </div>
    </div>
    
    <div class="info-card">
        <h3>📅 Rendez-vous associé</h3>
        <div class="info-row">
            <span class="info-label">Référence :</span>
            <span class="info-value">{{ $appointment->reference }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date et heure :</span>
            <span class="info-value"><strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Médecin :</span>
            <span class="info-value">{{ $doctor->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Adresse :</span>
            <span class="info-value">{{ $doctor->address }}, {{ $doctor->city }}</span>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong>📋 Ce qui vous attend maintenant :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>✅ Votre place est réservée et payée</li>
            <li>🔔 Vous recevrez un rappel 24h avant</li>
            <li>📄 Vos justificatifs sont disponibles en téléchargement</li>
            <li>📞 Le cabinet peut vous contacter si nécessaire</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/api/pdf/payments/' . $payment->id . '/receipt') }}" class="button button-success">
            📄 Télécharger mon reçu de paiement
        </a>
        
        <a href="{{ url('/api/pdf/appointments/' . $appointment->id . '/receipt') }}" class="button">
            📋 Télécharger mon justificatif RDV
        </a>
    </div>
    
    <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">
        <h4 style="margin: 0 0 10px 0; color: #155724;">🎉 Merci pour votre confiance !</h4>
        <p style="margin: 0; color: #155724;">
            Votre rendez-vous est confirmé et payé. Nous nous réjouissons de vous accueillir 
            chez {{ $doctor->full_name }} le {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}.
        </p>
    </div>
    
    <div style="text-align: center; color: #666; font-size: 12px; margin-top: 20px;">
        <p>Conservez ce reçu comme preuve de paiement.<br>
        En cas de question, contactez-nous à support@medical-app.com</p>
    </div>
@endsection