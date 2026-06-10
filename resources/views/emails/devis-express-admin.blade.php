<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouveau Devis Express</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #dc2626;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 22px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e5e7eb;
        }
        .client-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .label {
            font-weight: bold;
            color: #374151;
            width: 120px;
            display: inline-block;
        }
        .badge {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔔 Nouvelle demande de devis express</h1>
    </div>
    
    <div class="content">
        <p><span class="badge">Nouvelle demande</span></p>
        
        <p><strong>Une nouvelle demande de devis express vient d'être soumise sur le site.</strong></p>
        
        <div class="client-info">
            <h3 style="margin-top: 0;">📋 Informations client</h3>
            
            <div class="info-row">
                <span class="label">👤 Nom :</span>
                <span>{{ $devis->nom }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">📧 Email :</span>
                <span><a href="mailto:{{ $devis->email }}">{{ $devis->email }}</a></span>
            </div>
            
            <div class="info-row">
                <span class="label">📞 Téléphone :</span>
                <span><a href="tel:{{ $devis->telephone }}">{{ $devis->telephone }}</a></span>
            </div>
            
            @if($devis->entreprise)
            <div class="info-row">
                <span class="label">🏢 Entreprise :</span>
                <span>{{ $devis->entreprise }}</span>
            </div>
            @endif
            
            <div class="info-row">
                <span class="label">🎯 Besoin :</span>
                <span><strong>{{ $devis->besoin }}</strong></span>
            </div>
            
            @if($devis->budget)
            <div class="info-row">
                <span class="label">💰 Budget :</span>
                <span>{{ $devis->budget }}</span>
            </div>
            @endif
            
            @if($devis->date_souhaitee)
            <div class="info-row">
                <span class="label">📅 Date souhaitée :</span>
                <span>{{ \Carbon\Carbon::parse($devis->date_souhaitee)->format('d/m/Y') }}</span>
            </div>
            @endif
            
            @if($devis->message)
            <div class="info-row">
                <span class="label">💬 Message :</span>
                <div style="margin-top: 8px; background: #f3f4f6; padding: 10px; border-radius: 6px;">
                    {{ $devis->message }}
                </div>
            </div>
            @endif
        </div>
        
        <p><strong>📅 Date de la demande :</strong> {{ $devis->created_at->format('d/m/Y à H:i') }}</p>
        
        <div class="actions">
            <a href="{{ url('/admin/devis-express/' . $devis->id) }}" class="btn">📋 Voir la demande dans l'admin</a>
            <a href="mailto:{{ $devis->email }}" class="btn" style="background: #10b981;">✉️ Répondre au client</a>
            <a href="https://wa.me/{{ $devis->telephone }}" class="btn" style="background: #25D366;">💬 Contacter sur WhatsApp</a>
        </div>
    </div>
</body>
</html>