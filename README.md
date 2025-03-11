# Cahier des Charges - Outil de Ticketing en Laravel + Livewire

# 1. Présentation du projet

L'outil de ticketing est une application développée en Laravel et Livewire permettant de gérer les demandes clients envoyées par email. L'application lit une boîte mail (POP3/IMAP) et convertit automatiquement les emails entrants en tickets, avec gestion des statuts, des réponses et des workflows.

## 2. Fonctionnalités principales

### 2.1 Création des tickets

- Lecture automatique des emails d'une boîte mail POP3/IMAP.
- Création d'un ticket à partir de chaque nouvel email reçu.
- Extraction automatique du nom et de l'adresse email du client.
- Gestion des pièces jointes :
    - Images affichées en ligne et téléchargeables séparément.
    - Types de fichiers autorisés : Images standards, PDF, Word, Excel, PowerPoint, ZIP.
- Les réponses des clients aux emails sont ajoutées automatiquement au ticket correspondant.

### 2.2 Gestion des tickets

- Affectation d'un **statut** au ticket, inspiré de Freshdesk :
    - "Ouvert"
    - "En attente"
    - "Résolu"
    - "Fermé"
- Réponse directe aux clients depuis l’interface, avec historique des échanges.
- Possibilité d’ajouter des **commentaires internes** visibles uniquement par les agents.
- Ajout de **pièces jointes** aux réponses et aux commentaires.
- Transfert d’un ticket ou d’une réponse à une autre adresse email.
- Filtres et recherche simple avec tri par date et texte.

### 2.3 Workflows automatisés

- Un **administrateur** peut configurer des workflows pour :
    - Classer automatiquement les tickets en fonction de **mots-clés** présents dans le sujet ou le corps de l’email.
    - Alerter par email une ou plusieurs adresses spécifiques lors de la création d'un ticket.
    - Attribuer un **label** au ticket basé sur le workflow appliqué.
- Un agent voit uniquement les tickets associés aux labels de workflow qui lui sont attribués.

### 2.4 Gestion des utilisateurs et des accès

- **Administrateur** : Accès total à tous les tickets et fonctionnalités.
- **Agent** : Accès restreint aux tickets en fonction des labels de workflow qui lui sont affectés.
- **Client** : Accès uniquement à ses propres tickets ou aux tickets de sa société (si définie en base de données et liée à son compte).
- Un **client** peut créer son compte s’il a déjà un ticket existant dans la base avec son email.
- Un **agent** doit définir son mot de passe à sa première connexion.

### 2.5 Archivage des tickets

- Archivage automatique des **tickets résolus** après **3 jours**.
- Moteur de recherche simple avec filtres par date et texte.

## 3. Sécurité

### 3.1 Authentification et accès

- Mots de passe complexes (minimum 12 caractères, majuscules, minuscules, chiffres et caractères spéciaux).
- Authentification à deux facteurs (optionnelle) pour les administrateurs et agents.
- Vérification d’email pour les nouvelles inscriptions clients.
- Restriction des tentatives de connexion (blocage après plusieurs échecs).
- Expiration automatique des sessions après une période d’inactivité.

### 3.2 Sécurité des emails entrants

- Filtrage des emails suspects pour éviter le spam et le phishing.
- Nettoyage du contenu des emails pour prévenir les attaques XSS.
- Vérification stricte des types de fichiers joints (vérification du type MIME et de l’extension réelle).

### 3.3 Protection des données

- Chiffrement des mots de passe avec `bcrypt`.
- Permissions strictes : les agents ne voient que les tickets autorisés.
- Journalisation des actions sensibles (connexions, modifications de tickets, etc.).

### 3.4 Sécurité des communications

- Forçage HTTPS pour toutes les connexions.
- Headers de sécurité : CSP, X-Frame-Options, HSTS, X-Content-Type-Options.
- Protection contre les attaques CSRF et XSS avec les middlewares Laravel.

### 3.5 Sécurité des workflows et notifications

- Anti-spam sur les alertes email pour éviter un trop grand nombre de notifications.
- Validation stricte des règles de workflow pour éviter des classements erronés.

## 4. Points à approfondir

- Faut-il ajouter une gestion des SLA (accords de niveau de service) pour définir des temps de réponse maximaux ? non
- Souhaite-t-on intégrer un module de statistiques sur les tickets traités, temps de résolution moyen, etc. ? oui

## 5. Informations techniques

Utiliser Laravel 11 avec Jetstream pour renforcer la sécurité 
Livewire pour toutes les interactions de l’UI afin d’avoir un comportement de type “Single Page Application”
Utiliser TailwindCss pour dessiner les interfaces et si besoin Javascript et/ou AlpineJS pour rendre dynamique les affichages
