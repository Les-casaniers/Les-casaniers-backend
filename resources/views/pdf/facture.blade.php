<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->facture_ref }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #2563eb;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-box {
            margin-bottom: 15px;
        }
        .info-box .label {
            font-weight: bold;
            width: 120px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-line {
            margin-bottom: 5px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FACTURE</h1>
            <p>{{ $facture->facture_ref }}</p>
        </div>

        <div class="info-section">
            <div class="info-box">
                <span class="label">Date d'émission :</span>
                {{ date('d/m/Y', strtotime($facture->date_emission)) }}
            </div>
            <div class="info-box">
                <span class="label">Commande N° :</span>
                {{ $commande->commande_uuid }}
            </div>
            <div class="info-box">
                <span class="label">Statut :</span>
                {{ $facture->statut === 'payee' ? 'Payée' : 'En attente' }}
            </div>
            <div class="info-box">
                <span class="label">Méthode de paiement :</span>
                {{ ucfirst($facture->methode_paiement) }}
            </div>
        </div>

        <h3>Détail des articles</h3>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-right">Qté</th>
                    <th class="text-right">Prix unitaire</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produits as $produit)
                <tr>
                    <td>{{ $produit['nom'] }}</td>
                    <td class="text-right">{{ $produit['quantite'] }}</td>
                    <td class="text-right">{{ number_format($produit['prix_unitaire'], 0, ',', ' ') }} {{ $facture->devise }}</td>
                    <td class="text-right">{{ number_format($produit['quantite'] * $produit['prix_unitaire'], 0, ',', ' ') }} {{ $facture->devise }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-line">
                <strong>Sous-total :</strong> {{ number_format($commande->sous_total, 0, ',', ' ') }} {{ $facture->devise }}
            </div>
            <div class="total-line">
                <strong>Livraison :</strong> 
                @if($commande->livraison > 0)
                    {{ number_format($commande->livraison, 0, ',', ' ') }} {{ $facture->devise }}
                @else
                    Gratuite
                @endif
            </div>
            <div class="total-line grand-total">
                <strong>TOTAL :</strong> {{ number_format($facture->montant_total, 0, ',', ' ') }} {{ $facture->devise }}
            </div>
        </div>

        <div class="footer">
            <p>Merci de votre confiance !</p>
            <p>Les Casaniers Madagascar</p>
        </div>
    </div>
</body>
</html>