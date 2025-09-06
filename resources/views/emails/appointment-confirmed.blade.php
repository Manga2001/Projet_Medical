@extends('emails.layout')

@section('content')
    <div class="greeting">
        Excellent nouvelle, {{ $patient->first_name }} !
    </div>
    
    <div class="alert alert-success">
        <strong>✅ Votre rendez-vous a été confirmé par {{ $doctor->full_name }}</strong>
        <br>Votre consultation est maintenant officiellement programmée.
    </div>
    
    <div class="info-card">
        <h3>📅 Détails confirmés</h3>
        <div class="info-row">
            <span class="info-label">Référence :</span>
            <span class="info-value"><strong>{{ $appointment->reference }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date et heure :</span>
            <span class="info-value"><strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y à H:i') }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Confirmé le :</span>
            <span class="info-value">{{ $appointment->confirmed_at->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Statut :</span>
            <span class="info-value">🟢 {{ $appointment->status_label }}</span>
        </div>
    </div>
    
    <div class="info-card">
        <h3>👨‍⚕️ Votre médecin</h3>
        <div class="info-row">
            <span class="info-label">Médecin :</span>
            <span class="info-value">{{ $doctor->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Spécialité :</span>
            <span class="info-value">{{ $specialty->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Adresse :</span>
            <span class="info-value">{{ $doctor->address }}, {{ $doctor->city }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone :</span>
            <span class="info-value">{{ $doctor->phone }}</span>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong>📍 Informations importantes :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Arrivez 15 minutes avant l'heure de votre rendez-vous</li>
            <li>Munissez-vous de votre pièce d'identité</li>
            <li>Apportez vos anciens examens médicaux si pertinents</li>
            <li>En cas d'empêchement, prévenez au moins 24h à l'avance</li>
        </ul>
    </div>
    
    @if($appointment->payment_status === 'pending' && $appointment->payment_method === 'cash')
    <div class="info-card">
        <h3>💰 Paiement au cabinet</h3>
        <p>Le paiement sera effectué directement au cabinet lors de votre consultation.</p>
        <div class="info-row">
            <span class="info-label">Montant à prévoir :</span>
            <span class="info-value"><strong>{{ number_format($appointment->amount, 0, ',', ' ') }} FCFA</strong></span>
        </div>
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/api/pdf/appointments/' . $appointment->id . '/receipt') }}" class="button">
            📄 Télécharger le justificatif
        </a>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; color: #2c5aa0;">🔔 Rappel automatique</h4>
        <p style="margin: 0; font-size: 14px; color: #666;">
            Vous recevrez un email de rappel 24h avant votre rendez-vous avec toutes les informations nécessaires.
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <p style="color: #28a745; font-weight: 600; font-size: 16px;">
            🎉 Nous avons hâte de vous accueillir !
        </p>
    </div>
@endsection