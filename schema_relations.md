# Schema de relation (Les Casaniers)

Ce document decrit les relations entre les tables crees par les migrations fournies, puis donne un diagramme ER.

## Etapes

1. Creer les tables de base
   - admin
   - utilisateurs
   - categories
   - produits (FK categories)
   - images_produits (FK produits)
   - attributs_produits (FK produits)
2. Ajouter les tables fonctionnelles
   - avis_clients (FK produits, FK utilisateurs)
   - adresses_utilisateurs (FK utilisateurs)
   - favoris (FK utilisateurs, FK produits)
3. Creer le panier et ses liens
   - paniers (FK utilisateurs, FK produits, configuration_id)
4. Creer les configurations
   - configurations (FK produits, FK utilisateurs)
   - puis FK paniers.configuration_id -> configurations.id
5. Creer les devis et commandes
   - devis (FK utilisateurs, FK paniers)
   - commandes (FK utilisateurs, FK paniers, FK devis, FK adresses_utilisateurs)
6. Creer les factures
   - factures (FK commandes)
7. Creer les notifications admin
   - admin_notifications (pas de FK)

## Diagramme ER (Mermaid)

```mermaid
erDiagram
  admin {
    int id PK
    string prenom
    string nom
    string email
    string telephone
    string mot_de_passe
    string poste
    string statut
    datetime date_creation
    datetime date_modification
  }

  utilisateurs {
    int id PK
    string prenom
    string nom
    string email
    string telephone
    string mot_de_passe
    string statut
    datetime date_creation
    datetime date_modification
  }

  categories {
    int id PK
    int parent_id FK
    string nom
    string type
    int ordre_tri
    datetime date_creation
    datetime date_modification
  }

  produits {
    int id PK
    int categorie_id FK
    string reference
    string nom
    string description_courte
    string description
    string type_produit
    decimal prix
    string devise
    int quantite_stock
    boolean est_dispo
    boolean actif
    datetime date_creation
    datetime date_modification
  }

  images_produits {
    int id PK
    int produit_id FK
    string url
    string alt
    int ordre
    datetime date_creation
  }

  attributs_produits {
    int id PK
    int produit_id FK
    string cle_attr
    string libelle_attr
    string valeur_attr
    datetime date_creation
  }

  avis_clients {
    int id PK
    int produit_id FK
    int utilisateur_id FK
    int note
    string corps
    boolean publie
    datetime date_creation
    datetime date_modification
  }

  adresses_utilisateurs {
    int id PK
    int utilisateur_id FK
    string etiquette
    string nom_complet
    string telephone
    string adresse_ligne1
    string adresse_ligne2
    string ville
    string region
    string code_postal
    string pays
    boolean par_defaut_expedition
    boolean par_defaut_facturation
    datetime date_creation
    datetime date_modification
  }

  favoris {
    int id PK
    int utilisateur_id FK
    int produit_id FK
    datetime date_creation
  }

  paniers {
    int id PK
    int utilisateur_id FK
    string statut
    int produit_id FK
    int configuration_id FK
    string titre
    decimal prix_unitaire
    int quantite
    datetime date_creation
    datetime date_modification
  }

  configurations {
    int id PK
    int produit_id FK
    int utilisateur_id FK
    string nom_configuration
    string nom_configuration_autre
    string devise
    decimal prix_total
    json composants_json
    datetime date_creation
    datetime date_modification
  }

  devis {
    int id PK
    int utilisateur_id FK
    int panier_id FK
    string statut
    string note
    decimal montant_total
    string devise
    datetime date_creation
    datetime date_modification
  }

  commandes {
    int id PK
    string commande_uuid
    int utilisateur_id FK
    int panier_id FK
    int devis_id FK
    string statut
    decimal sous_total
    decimal livraison
    decimal total
    string devise
    int adresse_expedition_id FK
    int adresse_facturation_id FK
    int produit_id FK
    string titre
    string reference
    decimal prix_unitaire
    int quantite
    json meta_json
    datetime date_creation
    datetime date_modification
  }

  factures {
    int id PK
    int commande_id FK
    string facture_ref
    string statut
    decimal montant_total
    string devise
    string methode_paiement
    datetime date_emission
    datetime date_paiement
    string pdf_path
    datetime date_creation
    datetime date_modification
  }

  admin_notifications {
    int id PK
    string type
    string titre
    string message
    string lien
    string expediteur
    json meta
    boolean lue
    datetime date_creation
    datetime date_lecture
  }

  categories ||--o{ categories : parent
  categories ||--o{ produits : contient
  produits ||--o{ images_produits : images
  produits ||--o{ attributs_produits : attributs
  produits ||--o{ avis_clients : avis
  utilisateurs ||--o{ avis_clients : ecrit
  utilisateurs ||--o{ adresses_utilisateurs : adresses
  utilisateurs ||--o{ favoris : favoris
  produits ||--o{ favoris : favoris
  utilisateurs ||--o{ paniers : panier
  produits ||--o{ paniers : panier
  configurations ||--o{ paniers : configuration
  produits ||--o{ configurations : configurations
  utilisateurs ||--o{ configurations : configurations
  utilisateurs ||--o{ devis : devis
  paniers ||--o{ devis : devis
  utilisateurs ||--o{ commandes : commandes
  paniers ||--o{ commandes : commandes
  devis ||--o{ commandes : commandes
  adresses_utilisateurs ||--o{ commandes : adresse_expedition
  adresses_utilisateurs ||--o{ commandes : adresse_facturation
  produits ||--o{ commandes : produit
  commandes ||--|| factures : facture
```
