<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
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
        .header .fosa {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #e67e22;
        }
        .order-details p {
            margin: 8px 0;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .products-table th,
        .products-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .products-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-box {
            background: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            text-align: right;
            margin: 20px 0;
        }
        .total-box strong {
            font-size: 18px;
            color: #e67e22;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #e67e22;
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
        .status {
            display: inline-block;
            padding: 4px 12px;
            background-color: #f39c12;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="fosa">🐱</div>
            <h1>Merci pour votre commande !</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $utilisateur->prenom }} {{ $utilisateur->nom }},</h2>
            <p>Nous avons bien reçu votre commande et nous vous en remercions.</p>
            
            <div class="order-details">
                <p><strong>📋 Numéro de commande :</strong> {{ $commande->commande_uuid }}</p>
                <p><strong>📅 Date :</strong> {{ $commande->date_creation->format('d/m/Y à H:i') }}</p>
                <p><strong>📊 Statut :</strong> <span class="status">En attente</span></p>
            </div>

            <h3>📦 Détail de votre commande</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produits as $produit)
                    <tr>
                        <td>{{ $produit->titre }}</td>
                        <td>{{ $produit->quantite }}</td>
                        <td>{{ number_format($produit->prix_unitaire, 0, ',', ' ') }} {{ $commande->devise }}</td>
                        <td>{{ number_format($produit->quantite * $produit->prix_unitaire, 0, ',', ' ') }} {{ $commande->devise }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">Aucun produit trouve pour cette commande.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="total-box">
                <p><strong>Sous-total :</strong> {{ number_format($commande->sous_total, 0, ',', ' ') }} {{ $commande->devise }}</p>
                <p><strong>Livraison :</strong> {{ $commande->livraison > 0 ? number_format($commande->livraison, 0, ',', ' ') . ' ' . $commande->devise : 'Gratuite' }}</p>
                <p><strong>Total :</strong> <strong>{{ number_format($commande->total, 0, ',', ' ') }} {{ $commande->devise }}</strong></p>
            </div>

            <p>💡 <strong>Prochaines étapes :</strong></p>
            <ul>
                <li>✅ Notre équipe prépare votre commande</li>
                <li>✅ Vous serez notifié dès l'expédition</li>
                <li>✅ Livraison rapide dans toute Madagascar</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ url('/mes-commandes') }}" class="btn">📱 Suivre ma commande</a>
            </div>

            <p style="margin-top: 20px;">Une question ? Notre équipe est à votre disposition :</p>
            <p>📞 +261 34 12 345 67 | ✉️ contact@lescasaniers.mg</p>
        </div>
        <div class="footer">
            <p>Les Casaniers - Tananarive, Madagascar</p>
            <p>🐱 Votre expert PC à Madagascar</p>
            <p>&copy; {{ date('Y') }} Les Casaniers. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
