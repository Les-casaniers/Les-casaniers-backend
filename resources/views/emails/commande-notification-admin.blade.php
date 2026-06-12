<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle commande</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert-box {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .order-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Nouvelle commande reçue !</h1>
        </div>
        <div class="content">
            <div class="alert-box">
                <p><strong>🎉 Une nouvelle commande vient d'être passée !</strong></p>
            </div>

            <div class="order-details">
                <p><strong>📋 Numéro de commande :</strong> {{ $commande->commande_uuid }}</p>
                <p><strong>👤 Client :</strong> {{ $utilisateur->prenom }} {{ $utilisateur->nom }}</p>
                <p><strong>📧 Email :</strong> {{ $utilisateur->email }}</p>
                <p><strong>📞 Téléphone :</strong> {{ $utilisateur->telephone ?? 'Non renseigné' }}</p>
                <p><strong>📅 Date :</strong> {{ $commande->date_creation->format('d/m/Y à H:i') }}</p>
                <p><strong>💰 Total :</strong> <strong style="color: #e67e22;">{{ number_format($commande->total, 0, ',', ' ') }} Ar</strong></p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/DashboardAdmin/commandes') }}" class="btn">📊 Voir dans l'admin</a>
            </div>

            <p style="margin-top: 20px;">Connectez-vous à l'interface d'administration pour gérer cette commande.</p>
        </div>
        <div class="footer">
            <p>Les Casaniers - Tananarive, Madagascar</p>
            <p>🐱 Votre expert PC à Madagascar</p>
        </div>
    </div>
</body>
</html>