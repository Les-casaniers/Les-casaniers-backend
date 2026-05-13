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


CREATE TABLE `configurations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Produit concerné (un produit peut avoir plusieurs configurations)
  `produit_id` BIGINT UNSIGNED NOT NULL,

  -- Utilisateur propriétaire (NULL si invité)
  `utilisateur_id` BIGINT UNSIGNED NULL,

  -- Type / nom de configuration (liste + champ libre via "autre")
  `nom_configuration` ENUM(
    'cpu','carte_mere','gpu','ram','ssd','hdd','stockage','alimentation','boitier',
    'refroidissement','ventilateur','ecran','clavier','souris','os','reseau','autre'
  ) NOT NULL,

  -- Champ libre seulement si nom_configuration = 'autre'
  `nom_configuration_autre` VARCHAR(190) NULL,

  `devise` CHAR(3) NULL DEFAULT 'MGA',

  -- Prix total calculé
  `prix_total` DECIMAL(12,2) NULL DEFAULT 0.00,

  -- Liste des composants en JSON
  `composants_json` JSON NOT NULL,

  `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  KEY `idx_config_produit` (`produit_id`),
  KEY `idx_config_user` (`utilisateur_id`),

  CONSTRAINT `fk_config_produit`
    FOREIGN KEY (`produit_id`)
    REFERENCES `produits` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_config_pc_utilisateur`
    FOREIGN KEY (`utilisateur_id`)
    REFERENCES `utilisateurs` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,

  CONSTRAINT `chk_nom_configuration_autre`
    CHECK (
      (`nom_configuration` = 'autre' AND `nom_configuration_autre` IS NOT NULL AND `nom_configuration_autre` <> '')
      OR
      (`nom_configuration` <> 'autre' AND `nom_configuration_autre` IS NULL)
    )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

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
CREATE TABLE favoris (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  utilisateur_id BIGINT UNSIGNED NOT NULL,
  produit_id BIGINT UNSIGNED NOT NULL,

  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

  -- Empêche de mettre le même produit en favoris plusieurs fois pour le même utilisateur
  UNIQUE KEY uq_utilisateur_produit (utilisateur_id, produit_id),

  CONSTRAINT fk_favoris_utilisateur
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_favoris_produit
    FOREIGN KEY (produit_id) REFERENCES produits(id)
    ON DELETE CASCADE
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

  -- Statut global du panier (au niveau "ligne", on répète la valeur)
  statut ENUM('actif','commande','abandonne') NOT NULL DEFAULT 'actif',

  -- Données de l’item (ligne panier)
  produit_id BIGINT UNSIGNED NULL,
  configuration_id BIGINT UNSIGNED NULL,
  titre VARCHAR(255) NOT NULL,
  prix_unitaire DECIMAL(12,2) NULL,
  quantite INT UNSIGNED NOT NULL DEFAULT 1,

  date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_paniers_utilisateur
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
    ON DELETE SET NULL,

  CONSTRAINT fk_paniers_produit
    FOREIGN KEY (produit_id) REFERENCES produits(id)
    ON DELETE SET NULL,

  -- Optionnel (recommandé) : empêche d'avoir 2 lignes identiques pour le même utilisateur
  -- si tu veux plutôt "cumuler" la quantité au lieu de dupliquer les lignes.
  UNIQUE KEY uq_panier_item (utilisateur_id, statut, produit_id, configuration_id)
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
CREATE TABLE `devis` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `utilisateur_id` BIGINT UNSIGNED NULL,
  `panier_id` BIGINT UNSIGNED NULL,

  `statut` ENUM('brouillon','envoye','accepte','refuse','expire')
    NOT NULL DEFAULT 'brouillon',

  `note` TEXT NULL,

  `montant_total` DECIMAL(12,2) NULL DEFAULT 0.00,
  `devise` CHAR(3) NOT NULL DEFAULT 'MGA',

  `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  KEY `idx_devis_user` (`utilisateur_id`),
  KEY `idx_devis_panier` (`panier_id`),
  KEY `idx_devis_statut` (`statut`),

  CONSTRAINT `fk_devis_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_devis_panier`
    FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `commandes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- identifiant pour regrouper les items d'une même commande
  `commande_uuid` CHAR(36) NOT NULL,

  `utilisateur_id` BIGINT UNSIGNED NULL,
  `panier_id` BIGINT UNSIGNED NULL,
  `devis_id` BIGINT UNSIGNED NULL,

  `statut` ENUM('en_attente','payee','en_traitement','expediee','terminee','annulee','remboursee')
    NOT NULL DEFAULT 'en_attente',

  `sous_total` DECIMAL(12,2) NULL DEFAULT 0.00,
  `livraison` DECIMAL(12,2) NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NULL DEFAULT 0.00,
  `devise` CHAR(3) NOT NULL DEFAULT 'MGA',

  `adresse_expedition_id` BIGINT UNSIGNED NULL,
  `adresse_facturation_id` BIGINT UNSIGNED NULL,

  -- item
  `produit_id` BIGINT UNSIGNED NULL,
  `titre` VARCHAR(255) NOT NULL,
  `reference` VARCHAR(80) NULL,
  `prix_unitaire` DECIMAL(12,2) NULL DEFAULT 0.00,
  `quantite` INT UNSIGNED NOT NULL DEFAULT 1,
  `meta_json` JSON NULL,

  `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  KEY `idx_commande_uuid` (`commande_uuid`),
  KEY `idx_commande_user` (`utilisateur_id`),
  KEY `idx_commande_statut` (`statut`),
  KEY `idx_commande_panier` (`panier_id`),
  KEY `idx_commande_devis` (`devis_id`),
  KEY `idx_commande_produit` (`produit_id`),

  CONSTRAINT `fk_commandes_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_panier`
    FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_devis`
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_adresse_exped`
    FOREIGN KEY (`adresse_expedition_id`) REFERENCES `adresses_utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_adresse_fact`
    FOREIGN KEY (`adresse_facturation_id`) REFERENCES `adresses_utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_produit`
    FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `commandes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- identifiant pour regrouper les items d'une même commande
  `commande_uuid` CHAR(36) NOT NULL,

  `utilisateur_id` BIGINT UNSIGNED NULL,
  `panier_id` BIGINT UNSIGNED NULL,
  `devis_id` BIGINT UNSIGNED NULL,

  `statut` ENUM('en_attente','payee','en_traitement','expediee','terminee','annulee','remboursee')
    NOT NULL DEFAULT 'en_attente',

  `sous_total` DECIMAL(12,2) NULL DEFAULT 0.00,
  `livraison` DECIMAL(12,2) NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NULL DEFAULT 0.00,
  `devise` CHAR(3) NOT NULL DEFAULT 'MGA',

  `adresse_expedition_id` BIGINT UNSIGNED NULL,
  `adresse_facturation_id` BIGINT UNSIGNED NULL,

  -- item
  `produit_id` BIGINT UNSIGNED NULL,
  `titre` VARCHAR(255) NOT NULL,
  `reference` VARCHAR(80) NULL,
  `prix_unitaire` DECIMAL(12,2) NULL DEFAULT 0.00,
  `quantite` INT UNSIGNED NOT NULL DEFAULT 1,
  `meta_json` JSON NULL,

  `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  KEY `idx_commande_uuid` (`commande_uuid`),
  KEY `idx_commande_user` (`utilisateur_id`),
  KEY `idx_commande_statut` (`statut`),
  KEY `idx_commande_panier` (`panier_id`),
  KEY `idx_commande_devis` (`devis_id`),
  KEY `idx_commande_produit` (`produit_id`),

  CONSTRAINT `fk_commandes_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_panier`
    FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_devis`
    FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_adresse_exped`
    FOREIGN KEY (`adresse_expedition_id`) REFERENCES `adresses_utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_adresse_fact`
    FOREIGN KEY (`adresse_facturation_id`) REFERENCES `adresses_utilisateurs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,

  CONSTRAINT `fk_commandes_produit`
    FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
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


-- mysql -h les-casaniers-tonny-e980.b.aivencloud.com -P 27187 -u avnadmin -p defaultdb < database/script.sql