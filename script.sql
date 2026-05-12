CREATE DATABASE lescasaniers
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lescasaniers;

-- =========================
-- administrateurs
-- =========================
CREATE TABLE admin (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prenom VARCHAR(100) NOT NULL,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  telephone VARCHAR(30) NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  poste ENUM('admin','support','logistique') NOT NULL DEFAULT 'admin',
  statut ENUM('actif','desactive') NOT NULL DEFAULT 'actif',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================
-- UTILISATEURS / COMPTES
-- =========================

CREATE TABLE utilisateurs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prenom VARCHAR(100) NOT NULL,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  telephone VARCHAR(30) NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  statut ENUM('actif','desactive') NOT NULL DEFAULT 'actif',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: adresses_utilisateurs
-- Description: adresses (livraison/facturation) liées aux utilisateurs
-- Colonnes clés:
--  utilisateur_id: FK vers utilisateurs.id
--  etiquette: nom de l'adresse (ex: 'Domicile')
--  nom_complet, telephone, adresse_ligne1/2, ville, region, code_postal, pays
--  par_defaut_expedition / par_defaut_facturation: indicateurs
-- =========================
CREATE TABLE adresses_utilisateurs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NOT NULL,
  etiquette VARCHAR(50) NULL,
  nom_complet VARCHAR(190) NOT NULL,
  telephone VARCHAR(30) NULL,
  adresse_ligne1 VARCHAR(190) NOT NULL,
  adresse_ligne2 VARCHAR(190) NULL,
  ville VARCHAR(120) NOT NULL,
  region VARCHAR(120) NULL,
  code_postal VARCHAR(20) NULL,
  pays VARCHAR(80) NOT NULL DEFAULT 'Madagascar',
  par_defaut_expedition TINYINT(1) NOT NULL DEFAULT 0,
  par_defaut_facturation TINYINT(1) NOT NULL DEFAULT 0,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_adresses_utilisateurs_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- CATALOGUE (PRODUITS / CATEGORIES / ATTRIBUTS)
-- Table: categories
-- Description: arborescence des catégories produits
-- Colonnes:
--  parent_id: FK vers categories(id) pour structurer l'arbre
--  slug: identifiant lisible pour l'URL
--  nom: nom affiché
--  type: segmentation (pro, gaming, composants...)
-- =========================
CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  nom VARCHAR(190) NOT NULL,
  type ENUM('pro','gaming','composants','peripheriques','services','guides') NOT NULL,
  ordre_tri INT NOT NULL DEFAULT 0,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: produits
-- Description: fiches produits (PC, composants, services...)
-- Colonnes clés:
--  categorie_id: FK vers categories
--  reference: code interne / SKU
--  slug: URL
--  nom, description_courte, description: contenu texte
--  type_produit: classification
--  prix, devise: prix et devise
--  quantite_stock: gestion stock
--  actif: visible ou non
-- =========================
CREATE TABLE produits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categorie_id BIGINT UNSIGNED NOT NULL,
  reference VARCHAR(80) NULL UNIQUE,
  slug VARCHAR(190) NOT NULL UNIQUE,
  nom VARCHAR(255) NOT NULL,
  description_courte VARCHAR(500) NULL,
  description LONGTEXT NULL,
  type_produit ENUM('pc','portable','composant','peripherique','service') NOT NULL,
  prix DECIMAL(12,2) NULL,
  devise CHAR(3) NOT NULL DEFAULT 'MGA',
  quantite_stock INT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_produits_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- Table: images_produits
-- Description: URLs des images attachées aux produits
-- Colonnes:
--  produit_id: FK vers produits
--  url: chemin de l'image
--  alt: texte alternatif (SEO / accessibilité)
--  ordre: classement
-- =========================
CREATE TABLE images_produits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id BIGINT UNSIGNED NOT NULL,
  url VARCHAR(500) NOT NULL,
  alt VARCHAR(255) NULL,
  ordre INT NOT NULL DEFAULT 0,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_images_produits_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: attributs_produits
-- Description: paires clé/valeur pour les spécifications (ex: socket, TDP, DDR)
-- Colonnes:
--  cle_attr: nom de l'attribut (ex: socket, tdp, ddr_generation)
--  valeur_attr: valeur en texte (ex: LGA1700, 150W, DDR5)
-- =========================
CREATE TABLE attributs_produits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id BIGINT UNSIGNED NOT NULL,
  cle_attr VARCHAR(80) NOT NULL,
  libelle_attr VARCHAR(120) NULL,
  valeur_attr VARCHAR(255) NOT NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attributs_produit (produit_id, cle_attr),
  CONSTRAINT fk_attributs_produits_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: avis_clients
-- Description: retours et notes des clients pour la preuve sociale
-- Colonnes:
--  produit_id: FK vers produits
--  utilisateur_id: FK vers utilisateurs (peut être NULL pour avis anonymes)
--  note: valeur 1..5
--  publie: si l'avis est visible
-- =========================
CREATE TABLE avis_clients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id BIGINT UNSIGNED NOT NULL,
  utilisateur_id BIGINT UNSIGNED NULL,
  note TINYINT UNSIGNED NOT NULL,
  titre VARCHAR(190) NULL,
  corps TEXT NULL,
  publie TINYINT(1) NOT NULL DEFAULT 0,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_avis_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
  CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- LISTES DE SOUHAITS / FAVORIS
-- Table: listes_favoris
-- Description: listes de favoris par utilisateur (plusieurs listes possibles)
-- Colonnes: utilisateur_id (FK), nom, timestamps
-- Table: favoris_items
-- Description: association liste <-> produit
-- =========================
CREATE TABLE listes_favoris (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(120) NOT NULL DEFAULT 'Favoris',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_listes_favoris_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favoris_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  liste_id BIGINT UNSIGNED NOT NULL,
  produit_id BIGINT UNSIGNED NOT NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_liste_produit (liste_id, produit_id),
  CONSTRAINT fk_favoris_items_liste FOREIGN KEY (liste_id) REFERENCES listes_favoris(id) ON DELETE CASCADE,
  CONSTRAINT fk_favoris_items_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- PANIER
-- Table: paniers
-- Description: panier actif par utilisateur (ou panier anonyme suivi côté front)
-- Colonnes: utilisateur_id (nullable si invité), statut (actif/commande/abandonne)
-- Table: items_panier
-- Description: lignes de panier (produit ou configuration)
-- =========================
CREATE TABLE paniers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NULL,
  statut ENUM('actif','commande','abandonne') NOT NULL DEFAULT 'actif',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_paniers_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE items_panier (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  panier_id BIGINT UNSIGNED NOT NULL,
  produit_id BIGINT UNSIGNED NULL,
  configuration_id BIGINT UNSIGNED NULL,
  titre VARCHAR(255) NOT NULL,
  prix_unitaire DECIMAL(12,2) NULL,
  quantite INT UNSIGNED NOT NULL DEFAULT 1,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_panier_panier FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_panier_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- CONFIGURATEUR (PROFIL / ETAPES / CONFIGURATION / COMPATIBILITE)
-- Table: profils_configurateur
-- Description: profils préconfigurés (Gaming 1080p, Bureautique, etc.)
-- Table: etapes_configurateur
-- Description: étapes du configurateur (ex: cpu, carte_mere, ram...)
-- Table: configurations
-- Description: builds sauvegardées par l'utilisateur
-- Table: items_configuration
-- Description: composants sélectionnés pour une configuration
-- =========================
CREATE TABLE profils_configurateur (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  nom VARCHAR(190) NOT NULL,
  description VARCHAR(500) NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE etapes_configurateur (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  profil_id BIGINT UNSIGNED NULL,
  code VARCHAR(80) NOT NULL,
  nom VARCHAR(190) NOT NULL,
  ordre INT NOT NULL DEFAULT 0,
  requis TINYINT(1) NOT NULL DEFAULT 1,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_profil_etape (profil_id, code),
  CONSTRAINT fk_etapes_profil FOREIGN KEY (profil_id) REFERENCES profils_configurateur(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE configurations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NULL,
  profil_id BIGINT UNSIGNED NOT NULL,
  nom VARCHAR(190) NULL,
  statut ENUM('brouillon','pret','devis','commande') NOT NULL DEFAULT 'brouillon',
  prix_total DECIMAL(12,2) NULL,
  devise CHAR(3) NOT NULL DEFAULT 'MGA',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_configurations_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  CONSTRAINT fk_configurations_profil FOREIGN KEY (profil_id) REFERENCES profils_configurateur(id)
) ENGINE=InnoDB;

CREATE TABLE items_configuration (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  configuration_id BIGINT UNSIGNED NOT NULL,
  etape_id BIGINT UNSIGNED NULL,
  emplacement VARCHAR(80) NOT NULL, -- ex: cpu, carte_mere, gpu, ram, alim, refroidissement, stockage
  produit_id BIGINT UNSIGNED NULL,
  titre VARCHAR(255) NOT NULL,
  quantite INT UNSIGNED NOT NULL DEFAULT 1,
  prix_unitaire DECIMAL(12,2) NULL,
  meta_json JSON NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_configuration_emplacement (configuration_id, emplacement),
  CONSTRAINT fk_items_configuration_configuration FOREIGN KEY (configuration_id) REFERENCES configurations(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_configuration_etape FOREIGN KEY (etape_id) REFERENCES etapes_configurateur(id) ON DELETE SET NULL,
  CONSTRAINT fk_items_configuration_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: regles_compatibilite
-- Description: règles pour vérifier la compatibilité entre composants
-- Colonnes:
--  gauche_emplacement / droite_emplacement: emplacements à comparer (ex: cpu, carte_mere)
--  gauche_cle / droite_cle: clés d'attributs (ex: socket, ddr_generation)
--  operateur: type de comparaison (eq, neq, in...)
--  message_template: texte affiché par la mascotte si règle non respectée
-- =========================
CREATE TABLE regles_compatibilite (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(190) NOT NULL,
  gravite ENUM('info','avertissement','critique') NOT NULL DEFAULT 'avertissement',
  gauche_emplacement VARCHAR(80) NOT NULL,
  gauche_cle VARCHAR(80) NOT NULL,
  operateur ENUM('eq','neq','gte','lte','in') NOT NULL DEFAULT 'eq',
  droite_emplacement VARCHAR(80) NOT NULL,
  droite_cle VARCHAR(80) NOT NULL,
  message_template VARCHAR(500) NOT NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: messages_mascotte
-- Description: messages pré-écrits pour la mascotte selon le composant et la gravité
-- Colonnes: emplacement_composant (ex: cpu), ton (amical/pro), gravite (astuce/avertissement/critique), titre, corps
-- =========================
CREATE TABLE messages_mascotte (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  emplacement_composant VARCHAR(80) NOT NULL, -- cpu, carte_mere, gpu...
  ton ENUM('amical','pro','gaming') NOT NULL DEFAULT 'amical',
  gravite ENUM('astuce','avertissement','critique') NOT NULL DEFAULT 'astuce',
  titre VARCHAR(190) NULL,
  corps TEXT NOT NULL,
  exemple_reference VARCHAR(80) NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mascotte_emplacement (emplacement_composant, gravite)
) ENGINE=InnoDB;

-- =========================
-- DEVIS / COMMANDES (tunnel de conversion)
-- Table: devis
-- Description: demandes de devis générées depuis un panier ou une configuration
-- Colonnes clés: utilisateur_id (client), panier_id, configuration_id, montant_total
-- Table: commandes
-- Description: commandes passées
-- Colonnes clés: utilisateur_id, adresse_expedition_id, adresse_facturation_id, statut, total
-- =========================
CREATE TABLE devis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NULL,
  panier_id BIGINT UNSIGNED NULL,
  configuration_id BIGINT UNSIGNED NULL,
  statut ENUM('brouillon','envoye','accepte','refuse','expire') NOT NULL DEFAULT 'brouillon',
  nom_client VARCHAR(190) NOT NULL,
  email_client VARCHAR(190) NOT NULL,
  telephone_client VARCHAR(30) NULL,
  note TEXT NULL,
  montant_total DECIMAL(12,2) NULL,
  devise CHAR(3) NOT NULL DEFAULT 'MGA',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_devis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  CONSTRAINT fk_devis_panier FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE SET NULL,
  CONSTRAINT fk_devis_configuration FOREIGN KEY (configuration_id) REFERENCES configurations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE commandes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id BIGINT UNSIGNED NULL,
  panier_id BIGINT UNSIGNED NULL,
  devis_id BIGINT UNSIGNED NULL,
  statut ENUM('en_attente','payee','en_traitement','expediee','terminee','annulee','remboursee') NOT NULL DEFAULT 'en_attente',
  sous_total DECIMAL(12,2) NULL,
  livraison DECIMAL(12,2) NULL,
  total DECIMAL(12,2) NULL,
  devise CHAR(3) NOT NULL DEFAULT 'MGA',
  adresse_expedition_id BIGINT UNSIGNED NULL,
  adresse_facturation_id BIGINT UNSIGNED NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_commandes_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  CONSTRAINT fk_commandes_panier FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE SET NULL,
  CONSTRAINT fk_commandes_devis FOREIGN KEY (devis_id) REFERENCES devis(id) ON DELETE SET NULL,
  CONSTRAINT fk_commandes_adresse_exped FOREIGN KEY (adresse_expedition_id) REFERENCES adresses_utilisateurs(id) ON DELETE SET NULL,
  CONSTRAINT fk_commandes_adresse_fact FOREIGN KEY (adresse_facturation_id) REFERENCES adresses_utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE items_commande (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id BIGINT UNSIGNED NOT NULL,
  produit_id BIGINT UNSIGNED NULL,
  titre VARCHAR(255) NOT NULL,
  reference VARCHAR(80) NULL,
  prix_unitaire DECIMAL(12,2) NULL,
  quantite INT UNSIGNED NOT NULL DEFAULT 1,
  meta_json JSON NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_commande_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_commande_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- CONTENU: GUIDE DU FOSA / BLOG / SERVICES (maillage interne)
-- Table: contenus
-- Description: articles, guides, tutoriels, pages service
-- Colonnes: slug, titre, extrait, corps, type_contenu, publie, date_publication
-- Table: contenus_produits_liens
-- Description: association contenu -> produit pour maillage interne et CTA
-- =========================
CREATE TABLE contenus (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  titre VARCHAR(255) NOT NULL,
  extrait VARCHAR(500) NULL,
  corps LONGTEXT NOT NULL,
  type_contenu ENUM('guide','actualite','tutoriel','service') NOT NULL,
  publie TINYINT(1) NOT NULL DEFAULT 0,
  date_publication TIMESTAMP NULL,
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE contenus_produits_liens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contenu_id BIGINT UNSIGNED NOT NULL,
  produit_id BIGINT UNSIGNED NOT NULL,
  type_lien ENUM('recommande','mentionne','cta') NOT NULL DEFAULT 'mentionne',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_contenu_produit (contenu_id, produit_id),
  CONSTRAINT fk_contenus_liens_contenu FOREIGN KEY (contenu_id) REFERENCES contenus(id) ON DELETE CASCADE,
  CONSTRAINT fk_contenus_liens_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- MICRO‑COPY UI (boutons, erreurs, tooltips)
-- Table: textes_ui
-- Description: stocke les micro-textes utilisés dans l'interface (multilingue)
-- Colonnes: cle_nom (identifiant), contexte (où afficher), texte_valeur, ton, locale
-- =========================
CREATE TABLE textes_ui (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cle_nom VARCHAR(190) NOT NULL UNIQUE,
  contexte VARCHAR(120) NULL, -- ex: configurateur.etape.cpu, panier.vide, auth.login
  texte_valeur VARCHAR(500) NOT NULL,
  ton ENUM('neutre','amical','commercial') NOT NULL DEFAULT 'amical',
  locale VARCHAR(10) NOT NULL DEFAULT 'fr',
  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- mysql -h les-casaniers-tonny-e980.b.aivencloud.com -P 27187 -u avnadmin -p defaultdb < database/script.sql