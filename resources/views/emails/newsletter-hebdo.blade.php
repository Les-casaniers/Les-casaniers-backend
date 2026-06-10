<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Newsletter Les Casaniers</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a1a; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .offer { border-left: 4px solid #1a1a1a; padding: 15px; margin: 15px 0; background: #f9f9f9; }
        .btn { background: #1a1a1a; color: white; padding: 12px 25px; text-decoration: none; display: inline-block; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🇲🇬 LES CASANIERS</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $user->prenom ?? $user->nom ?? 'Cher client' }} !</h2>
            <p>Voici nos nouveautés et offres de cette semaine :</p>
            
            @foreach($offres as $offre)
            <div class="offer">
                <strong>✓ {{ $offre['titre'] }}</strong>
                @if(isset($offre['description']))
                <p style="margin: 5px 0 0 0; font-size: 14px;">{{ $offre['description'] }}</p>
                @endif
            </div>
            @endforeach

            <div style="text-align: center; margin: 30px 0;">
                <a href="https://les-casaniers-frontend.vercel.app/boutique" class="btn">Voir tous les produits →</a>
            </div>
            
            <p>À bientôt,<br><strong>L'équipe Les Casaniers</strong></p>
        </div>
        <div class="footer">
            <p>© 2026 Les Casaniers Madagascar</p>
        </div>
    </div>
</body>
</html>