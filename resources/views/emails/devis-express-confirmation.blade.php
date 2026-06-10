<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation Devis Express</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .info-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✨ Merci pour votre confiance !</h1>
    </div>
    
    <div class="content">
        <h2>Bonjour {{ $devis->nom }},</h2>
        
        <p>Nous avons bien reçu votre demande de devis express.</p>
        
        <div class="info-box">
            <strong>📋 Récapitulatif de votre demande :</strong><br>
            <strong>Besoin :</strong> {{ $devis->besoin }}<br>
            @if($devis->budget)
                <strong>Budget :</strong> {{ $devis->budget }}<br>
            @endif
            @if($devis->date_souhaitee)
                <strong>Date souhaitée :</strong> {{ \Carbon\Carbon::parse($devis->date_souhaitee)->format('d/m/Y') }}<br>
            @endif
        </div>
        
        <p>Notre équipe va étudier votre demande et vous apportera une réponse personnalisée <strong>sous 24 heures ouvrées</strong>.</p>
        
        <p>En attendant, n'hésitez pas à :</p>
        <ul>
            <li>📞 Nous contacter par téléphone au <strong>034 29 356 242</strong></li>
            <li>💬 Nous écrire sur <strong>WhatsApp</strong> au même numéro</li>
            <li>🌐 Consulter notre catalogue sur <a href="https://lescasaniers.mg">notre site</a></li>
        </ul>
        
        <a href="https://wa.me/261329356242" class="btn">💬 Nous contacter sur WhatsApp</a>
        
        <p style="margin-top: 30px; font-size: 14px;">Cordialement,<br>
        <strong>L'équipe Les Casaniers</strong></p>
    </div>
    
    <div class="footer">
        <p>Cet email est un accusé de réception automatique. Merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} Les Casaniers Madagascar. Tous droits réservés.</p>
    </div>
</body>
</html>