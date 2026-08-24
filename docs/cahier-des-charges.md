# JUSTICIA — Cahier des charges

> Source : Google Drive (fichier `1xRWKjIyJSB3azigGOSPypAnBQ9FHcu8_`), récupéré le 2026-08-24.
> Version 1.0 — Août 2026 — Statut Draft.
> Conservé ici tel quel pour référence hors-ligne par l'équipe de développement.

Système de gestion de la chaîne pénale : Interpellation · Garde à vue · Identification ·
Parquet · Instruction · Jugement · Exécution des peines · Casier judiciaire.

- **Pile technique imposée** : Backend Laravel (API REST, SOLID) — MySQL — Frontend NextJS (Clean Code, TypeScript).
- **Utilisateurs cibles** : Police judiciaire/gendarmerie, parquet, cabinets d'instruction, juridictions de jugement, greffes, administration pénitentiaire, service du casier judiciaire.

## 1. Présentation du projet

JUSTICIA couvre l'intégralité de la chaîne pénale : de l'interpellation d'une personne et sa
garde à vue, en passant par son identification, la constitution du dossier, la décision du
parquet, l'éventuelle instruction, le jugement (condamnation, relaxe, acquittement) ou le
relâchement à toute étape, jusqu'à l'exécution des peines et l'alimentation du casier
judiciaire. Chaque personne et chaque affaire disposent d'un identifiant unique suivi de bout
en bout, garantissant la traçabilité complète et la continuité de l'information entre les
services. Le système est conçu pour s'adapter au code de procédure pénale du pays de
déploiement (délais, actes, voies de recours paramétrables).

## 2. Contexte et objectifs

**Contexte** : la chaîne pénale repose largement sur des registres papier et dossiers
physiques transmis de service en service, d'où dossiers égarés, délais dépassés faute
d'alerte, détentions prolongées indûment, extraits de casier lents, absence de statistiques
fiables.

**Objectifs** :
- Traçabilité complète et infalsifiable de chaque affaire et personne mise en cause.
- Respect des délais légaux (GAV, détention provisoire, recours) via alertes automatiques.
- Suppression des ruptures d'information entre police, parquet, juridictions, prisons, casier.
- Alimentation automatique du casier judiciaire depuis les décisions définitives.
- Protection des droits des personnes (présomption d'innocence, confidentialité, exactitude,
  mises à jour relaxe/non-lieu/réhabilitation).
- Statistiques judiciaires fiables pour le ministère et les chefs de juridiction.
- Réduction des délais de traitement et du stock d'affaires en attente.

## 3. Principes directeurs

- **Légalité** : le système n'automatise aucune décision judiciaire ; il consigne les décisions
  prises par l'autorité compétente et en tire les conséquences (délais, notifications).
- **Présomption d'innocence** : le statut de chaque personne est explicite à tout moment ; une
  personne relâchée/relaxée ne doit jamais apparaître comme condamnée.
- **Confidentialité** : secret de l'enquête/instruction traduit en habilitations strictes ;
  chacun ne voit que les affaires de son ressort et de sa compétence.
- **Traçabilité** : chaque consultation et chaque acte journalisé de façon inviolable (qui,
  quoi, quand) ; pièces horodatées et non modifiables après signature.
- **Continuité** : fonctionnement y compris en connectivité dégradée dans les unités éloignées.

## 4. Acteurs et profils utilisateurs

| Profil | Institution | Droits principaux |
|---|---|---|
| Officier de police judiciaire (OPJ) | Police / Gendarmerie | Interpellations, GAV, identifications, PV, transmission au parquet |
| Chef d'unité | Police / Gendarmerie | Supervision des registres de l'unité, validation des transmissions |
| Procureur / Substitut | Parquet | Réception des procédures, orientation des poursuites, réquisitions, suivi |
| Juge d'instruction | Cabinet d'instruction | Dossiers d'information, actes d'instruction, mandats, ordonnances |
| Juge / Président d'audience | Juridiction de jugement | Consultation des dossiers audiencés, enregistrement des décisions |
| Greffier | Greffe | Enregistrement des affaires, audiencement, minutes, notifications, casier |
| Agent pénitentiaire (greffe pénitentiaire) | Administration pénitentiaire | Écrous, situations pénales, calculs de peine, levées d'écrou |
| Agent du casier judiciaire | Service du casier | Enregistrement des fiches, délivrance des bulletins, rectifications |
| Chef de juridiction / Ministère | Pilotage | Statistiques agrégées, tableaux de bord (sans accès nominatif hors besoin) |
| Administrateur habilité | DSI Justice | Paramétrage, gestion des habilitations, supervision technique |

## 5. La chaîne pénale (vue d'ensemble)

À chaque étape, une sortie de la chaîne est possible (relâchement, classement sans suite,
non-lieu, relaxe, acquittement) et est enregistrée avec le même soin qu'une condamnation.

1. **Interpellation** (OPJ) — motif, circonstances, droits notifiés.
2. **Garde à vue** (OPJ/Parquet) — placement, registre, délais/prolongations, auditions, fin de mesure.
3. **Identification** (OPJ) — identité, photo, signalement, rapprochement fichier des personnes.
4. **Constitution du dossier** (OPJ) — PV, auditions, pièces, scellés, transmission parquet.
5. **Orientation** (Procureur) — classement sans suite, alternatives, citation directe, ouverture d'information.
6. **Instruction** (Juge d'instruction) — mise en examen, actes, mandats, détention provisoire/contrôle judiciaire, ordonnance de règlement.
7. **Jugement** (Juridiction + Greffe) — audiencement, audiences, renvois, décision.
8. **Recours** (Parties + Greffe) — appel, opposition, cassation, délais et effets suspensifs.
9. **Exécution** (Parquet + Pénitentiaire) — peines, écrou, aménagements, fin de peine.
10. **Casier judiciaire** (Service du casier) — condamnations définitives, bulletins, effacements.

## 6. Périmètre fonctionnel

### 6.1 Interpellation et garde à vue
- Enregistrement de l'interpellation (date/heure, lieu, unité, agents, motif — référentiel des
  infractions, circonstances, personnes liées).
- Notification des droits tracée et horodatée (silence, avocat, médecin, information d'un proche).
- Registre de garde à vue digital : placement horodaté, calcul automatique des échéances
  légales (durée + prolongations selon l'infraction), autorisations de prolongation du parquet.
- Alertes automatiques avant expiration des délais (paramétrables : 2h avant, 30 min avant) à
  l'OPJ et au chef d'unité ; dépassement signalé et journalisé.
- Suivi des actes durant la mesure (auditions avec heures début/fin/repos, examens médicaux,
  entretiens avocat, confrontations).
- Issue de la mesure obligatoire : remise en liberté, convocation ultérieure, déferrement — avec horodatage de fin.
- Registre imprimable au format légal.
- Mineurs : régime spécifique automatique selon l'âge (délais réduits, avis représentants
  légaux/juge des enfants, assistance obligatoire).

### 6.2 Identification des personnes
- Fichier central des personnes mises en cause, identifiant unique : état civil (identités
  déclarées multiples/alias), filiation, adresses, pièces présentées.
- Signalement descriptif, photos réglementaires (face/profil), empreintes digitales en option
  (rapprochement biométrique en évolution).
- Recherche multicritère, détection de doublons, rapprochement proposé (jamais automatique) et
  validé par un OPJ, fusion tracée.
- Consultation des antécédents strictement encadrée selon habilitation ; toute consultation
  journalisée avec motif.
- Statuts par affaire : suspect, témoin assisté, mis en examen, prévenu, accusé, condamné,
  relaxé, acquitté, non-lieu.
- Personnes morales mises en cause (entreprises) avec représentants légaux.

### 6.3 Dossier d'affaire et procès-verbaux
- Numéro d'affaire unique dès l'origine, conservé tout au long de la chaîne (numéros parquet/instruction chaînés).
- Dossier structuré : personnes (mis en cause, victimes, témoins, avocats), infractions
  retenues (référentiel légal avec textes applicables), pièces classées par cote.
- Rédaction assistée des PV depuis modèles conformes, reprise automatique des données.
- Numérisation/versement de pièces externes avec cotation automatique.
- Clôture et signature des PV : immuable après signature ; rectification via PV rectificatif référencé.
- Transmission électronique au parquet (bordereau, accusé de réception, traçabilité).
- Jonction et disjonction d'affaires tracées.

### 6.4 Pièces à conviction et scellés
- Enregistrement des scellés (numéro, description, photos, lieu de saisie, affaire liée).
- Chaîne de conservation (chain of custody) : chaque mouvement tracé (remettant, récepteur, horodatage).
- Emplacements de stockage, inventaires périodiques, écarts signalés.
- Décisions sur les scellés (restitution, confiscation, destruction) liées à la décision judiciaire.

### 6.5 Parquet : orientation des poursuites
- Bureau des arrivées : réception, enregistrement, affectation à un magistrat.
- Déferrements traités en temps réel, décision immédiate tracée.
- Orientations paramétrées selon le droit local (classement sans suite avec motif, rappel à la
  loi, médiation pénale, composition/CRPC, citation directe, réquisitoire introductif,
  comparution immédiate).
- Réquisitions consignées à chaque étape.
- Suivi du portefeuille par magistrat (stock, anciennes, urgentes/détenus).
- Notification des classements sans suite aux plaignants (voies de contestation).

### 6.6 Instruction
- Dossier d'information : mises en examen, témoins assistés, parties civiles.
- Actes : interrogatoires, confrontations, transports, commissions rogatoires (suivi retours),
  expertises (désignation, délais, dépôt rapports).
- Mandats (comparution, amener, dépôt, arrêt) : émission, diffusion, exécution tracées.
- Mesures de sûreté : contrôle judiciaire (obligations suivies), détention provisoire (calcul
  automatique délais max/renouvellement, alertes prioritaires avant expiration).
- Ordonnances (règlement, mise en liberté...) avec notification et délais de recours calculés.
- Tableau de bord du cabinet (dossiers en cours, détenus, actes en attente, expertises en retard).

### 6.7 Audiencement et jugement
- Calendrier des audiences par chambre/type, composition, capacité, enrôlement, convocations/citations avec suivi de remise.
- Rôle d'audience imprimable, extractions des détenus.
- Tenue d'audience : appel des affaires, renvois motivés, mise en délibéré, incidents.
- Décision par prévenu et par infraction (condamnation, relaxe, acquittement, dispense de peine, intérêts civils).
- Minute depuis modèles, signature (président + greffier), répertoire, extraits/grosses.
- Calcul automatique du caractère définitif (suivi délais de recours par partie) → déclenche exécution + envoi casier.

### 6.8 Voies de recours
- Enregistrement des recours (appel, opposition, cassation), recevabilité vérifiée automatiquement.
- Effets tracés (suspension éventuelle, transmission à la juridiction supérieure avec bordereau).
- Suivi appel/cassation, intégration de la décision (confirmation/infirmation/cassation-renvoi),
  mise à jour de toute la chaîne (exécution, casier).

### 6.9 Exécution des peines et détention
- Mise à exécution des condamnations définitives (écrou, amendes → Trésor, TIG, sursis avec
  mise à l'épreuve).
- Registre d'écrou digital (détention provisoire et condamnés).
- Calcul de la situation pénale (fin de peine, imputation détention provisoire, remises de
  peine/grâces, cumuls/confusions).
- Alertes de libération (fin de peine, levée d'écrou) ; aucune détention au-delà du titre sans signalement immédiat.
- Aménagements de peine (libération conditionnelle, semi-liberté, placements).
- Transferts entre établissements tracés ; certificats de présence/détention.

### 6.10 Casier judiciaire
- Alimentation automatique par condamnations définitives (contrôlées/validées par le service du casier).
- Bulletins B1 (autorité judiciaire, intégral) / B2 (administrations habilitées, filtré) / B3
  (intéressé, condamnations graves uniquement) — règles de filtrage paramétrables.
- Délivrance : guichet ou en ligne (portail citoyen en option), vérification d'identité,
  édition sécurisée (QR), registre des délivrances, tarification/paiement mobile money.
- Mises à jour de droit (réhabilitation légale/judiciaire, amnistie, révision, effacements) automatiques avec journal.
- Rectifications sur décision de justice uniquement, tracées.
- Contrôle strict des accès : consultation nominative journalisée (motif + identité), rapports d'accès réguliers.
- Interdictions et déchéances associées, consultables par autorités habilitées.

### 6.11 Notifications, délais et alertes légales
- Moteur central des délais : chaque acte générateur crée automatiquement les échéances légales
  applicables (paramétrées par type d'acte/infraction).
- Alertes hiérarchisées (information, avertissement, dépassement) selon gravité.
- Convocations/citations/notifications générées depuis modèles, suivi de remise conditionnant la régularité.
- Agenda personnel par magistrat/greffier (audiences, échéances, actes en attente).

### 6.12 Statistiques judiciaires et pilotage
- Tableaux de bord par juridiction (entrées/traitées/stock, délais moyens, taux de réponse pénale, détention provisoire).
- Statistiques nationales agrégées/anonymisées pour le ministère.
- Détection des goulots (affaires anciennes, expertises en retard, dossiers sans acte depuis N mois).
- Exports pour annuaires statistiques et organisations régionales/internationales.

### 6.13 Administration et paramétrage
- Référentiels nationaux : infractions/textes (versionnés, dates d'effet), juridictions/ressorts,
  unités police/gendarmerie, établissements pénitentiaires, motifs de classement, types de peines.
- Paramétrage des délais légaux par type de procédure (modifiable, date d'entrée en vigueur).
- Habilitations : profils types + affectations par ressort/service ; création/suspension de
  comptes à double validation.
- Modèles d'actes/documents administrés centralement.
- Journal d'audit central inviolable, outils de contrôle interne (revues d'accès, alertes anomalies).

## 7. Exigences non fonctionnelles

| Exigence | Description |
|---|---|
| Disponibilité | 24h/24, objectif 99,7 % ; procédures dégradées papier avec rattrapage de saisie |
| Performance | Ouverture dossier < 2s ; recherche personne < 3s sur millions de fiches ; bulletin casier < 10s |
| Connectivité | Fonctionnement sur liaisons faibles ; interfaces légères ; mode saisie différée + synchronisation contrôlée |
| Intégrité | Aucun acte signé modifiable ; horodatage de confiance centralisé ; historisation systématique (pas de suppression physique) |
| Volumétrie | Échelle nationale : millions de personnes/affaires, dizaines de milliers d'utilisateurs |
| Ergonomie | Interfaces par métier, en français, formation courte, saisie rapide des actes fréquents |
| Impression | Registres, PV, convocations, rôles, minutes, bulletins aux formats légaux |
| Sauvegarde & continuité | Sauvegardes chiffrées multi-sites, PRA testé annuellement, RPO ≤ 15 min, RTO ≤ 4 h |

## 8. Sécurité, habilitations et protection des données

- Hébergement souverain (territoire national, infrastructures agréées), aucun transfert hors juridiction.
- Authentification forte obligatoire (mot de passe robuste + 2FA ; carte agent en évolution) ; comptes individuels nominatifs.
- Habilitations par rôle, ressort territorial et service (OPJ → unité, greffier → juridiction, secret de l'instruction appliqué).
- Cloisonnement du casier judiciaire (service distinct, habilitation spéciale, consultation motivée journalisée).
- Journal d'audit inviolable (append-only, horodaté, scellé cryptographiquement), revues d'accès, alertes anomalies (consultations massives, hors ressort).
- Chiffrement TLS + chiffrement au repos ; postes durcis recommandés pour services sensibles.
- Protection des données personnelles (loi nationale, ex. loi ivoirienne n° 2013-450) : minimisation, exactitude, durées de conservation légales, droits des personnes.
- Protection spécifique mineurs/victimes (accès restreints, anonymisation statistiques).
- Signature électronique des actes (certificats, cible) ; transition = signature manuscrite + empreinte numérique.
- Tests d'intrusion indépendants avant mise en service puis périodiques ; homologation de sécurité avant production.

## 9. Architecture et choix techniques

Application web deux couches découplées — NextJS (« Web ») + API Laravel — avec MySQL, centre
de données souverain avec site de secours.

| Couche | Technologie | Justification |
|---|---|---|
| Frontend (« Web ») | NextJS (React) — TypeScript | Interfaces métier par profil, légères/rapides sur liaisons faibles, Clean Code |
| Backend / API | Laravel (PHP 8+) — API REST | Modélisation riche de la procédure, files, planification (délais), SOLID |
| Base de données | MySQL 8 (InnoDB) | Transactions ACID, réplication synchrone site de secours, historisation systématique |
| Moteur de délais | Planificateur Laravel + files Redis | Calcul/surveillance continue des échéances légales, alertes fiables |
| Recherche | Meilisearch / Elasticsearch | Recherche personnes/affaires rapide, tolérance orthographique |
| Documents | Génération PDF serveur + gabarits | PV, convocations, minutes, bulletins aux formats légaux, empreinte numérique |
| Stockage des pièces | Stockage objet chiffré interne | Pièces numérisées, photos, scellés, empreintes d'intégrité |
| Audit | Journal append-only scellé (chaînage cryptographique) | Preuve d'intégrité des traces |
| Hébergement | Data center souverain, conteneurs Docker, site de secours | Souveraineté, continuité, déploiements reproductibles |

Interopérabilité prévue par API sécurisées : état civil, Trésor public (amendes), fichiers de
police, administration pénitentiaire — chaque interconnexion sous convention/homologation propre.

## 10. Qualité logicielle : SOLID et Clean Code

### 10.1 Backend Laravel — SOLID
- **S** : contrôleurs minces ; chaque acte de procédure = une Action métier dédiée
  (`PlacerEnGardeAVueAction`, `DefererAuParquetAction`, `EnregistrerDecisionAction`,
  `DelivrerBulletinAction`) ; validation dans des FormRequests.
- **O** : variations procédurales (orientations, catégories de peines, règles de bulletins)
  modélisées par des classes extensibles paramétrées par les référentiels, sans modifier le cœur.
- **L** : générateurs d'actes (PV, convocations, minutes) respectent un contrat commun,
  interchangeables par type d'acte.
- **I** : interfaces fines (`Notifiable`, `Signable`, `Horodatable`, `Auditable`) plutôt que monolithiques.
- **D** : services dépendent d'interfaces injectées (horodatage, scellement d'audit, stockage) —
  le cœur procédural ignore les implémentations techniques.
- Modules métier étanches : `GardeAVue`, `Personnes`, `Affaires`, `Parquet`, `Instruction`,
  `Audiencement`, `Execution`, `Casier` — reflétant la séparation institutionnelle.
- Tests automatisés prioritaires : calcul des délais légaux, règles d'habilitation (zéro accès
  hors ressort), caractère définitif des décisions, filtrage des bulletins casier, immuabilité
  des actes signés.

### 10.2 Frontend NextJS — Clean Code
- TypeScript strict ; types des entités procédurales générés depuis la spécification OpenAPI.
- Composants par domaine métier (`features/garde-a-vue`, `features/audiencement`,
  `features/casier`...) à responsabilité unique.
- Hooks dédiés aux règles transverses (statuts de personnes, comptes à rebours des délais, habilitations).
- **Aucune règle juridique côté client** : le frontend affiche, le backend décide.
- Linting/formatage bloquants, revues de code obligatoires, tests des composants critiques
  (saisie d'audience, registre GAV).

### 10.3 Pratiques transverses
- Git avec revues obligatoires ; CI bloquante (tests + linting) ; environnement de recette
  iso-production avec données fictives réalistes (jamais de données réelles hors production).
- Spécification OpenAPI maintenue ; migrations MySQL versionnées et réversibles ; feature flags
  pour déploiements progressifs par juridiction pilote.
- Documentation des règles de gestion juridiques validée conjointement juristes + dev.

## 11. Contraintes

- Cadre légal : règles (délais, actes, bulletins) paramétrées et validées par la chancellerie ;
  réformes intégrables par paramétrage avec date d'effet.
- Homologation de sécurité obligatoire avant mise en production.
- Déploiement progressif : juridictions/unités pilotes, coexistence temporaire avec le papier,
  puis généralisation.
- Reprise de l'existant (affaires en cours, stock casier — numérisation) selon plan dédié.
- Conduite du changement : formation de milliers d'agents, réseau de formateurs relais.
- Souveraineté : hébergement national, réversibilité complète, code source propriété de l'État.
- Neutralité : aucune automatisation/orientation de décision judiciaire, aucun scoring des personnes.

## 12. Livrables

| Livrable | Description |
|---|---|
| Système JUSTICIA | Plateforme complète (Web NextJS + API Laravel + MySQL) déployée |
| Référentiels initialisés | Infractions, juridictions, unités, délais, modèles d'actes — validés par la chancellerie |
| Code source | Dépôts Git complets, propriété du commanditaire, documentation de réversibilité |
| Documentation | Technique (architecture, exploitation, PRA), fonctionnelle, homologation sécurité |
| Manuels et formation | Guides par profil, formation des formateurs relais |
| Reprise des données | Plan et outils de reprise (affaires en cours, casier) |
| Pilote | Déploiement accompagné, bilan formalisé avant généralisation |
| Cahier et PV de recette | Scénarios de test juridiquement validés, PV signé |

## 13. Planning prévisionnel (~40 semaines / 10 mois jusqu'à fin du pilote)

| Phase | Contenu | Durée |
|---|---|---|
| 1. Cadrage juridique & fonctionnel | Ateliers, formalisation des règles de gestion, maquettes | 6 sem. |
| 2. Socle technique & sécurité | API Laravel, MySQL, habilitations, audit scellé, moteur de délais, socle NextJS, infra souveraine | 5 sem. |
| 3. Enquête & garde à vue | Interpellations, registre GAV, identification, PV, scellés, transmission parquet | 6 sem. |
| 4. Parquet & instruction | Orientation, déferrements, dossiers d'information, mandats, détention provisoire, ordonnances | 6 sem. |
| 5. Jugement & recours | Audiencement, audiences, décisions, minutes, caractère définitif, appels/cassation | 5 sem. |
| 6. Exécution & casier | Exécution des peines, écrous, situations pénales, casier judiciaire et bulletins | 5 sem. |
| 7. Statistiques & finitions | Tableaux de bord, statistiques nationales, reprises de données, éditions légales | 3 sem. |
| 8. Recette & homologation | Recette juridique et fonctionnelle, tests de sécurité, homologation, corrections | 4 sem. |
| 9. Pilote | Déploiement sites pilotes, formation, assistance sur site, bilan | 6 sem. |

## 14. Modalités de validation et recette

- Validation formelle à la fin de chaque phase (comité mixte : chancellerie, magistrats référents, DSI).
- Cahier de recette couvrant les parcours complets : interpellation → GAV+prolongation →
  déferrement → comparution immédiate → condamnation → écrou → casier ; interpellation → GAV →
  relâchement (aucune trace indue au casier) ; information judiciaire → non-lieu ; jugement →
  relaxe → mise à jour immédiate des statuts ; appel avec infirmation → mise à jour
  exécution/casier ; délivrance des 3 bulletins avec règles de filtrage ; réhabilitation avec effacement.
- Tests spécifiques des délais légaux sur jeu de cas de référence validé par des juristes.
- Tests d'habilitation : zéro accès indu toléré (hors ressort/hors profil).
- Audit de sécurité et test d'intrusion indépendants ; homologation avant toute donnée réelle.
- Classification des anomalies (bloquante/majeure/mineure) ; recette sans anomalie
  bloquante/majeure ; anomalies touchant délais, statuts des personnes ou casier = bloquantes par nature.
- Bilan de pilote formalisé conditionnant la généralisation ; VSR de 90 jours sur sites pilotes.

## 15. Maintenance et évolutions

- Garantie corrective : 12 mois après recette du pilote.
- Contrat de maintenance : supervision 24/7, astreinte, MàJ sécurité, intégration des réformes
  par paramétrage, transfert de compétences vers la DSI du ministère.
- Évolutions envisagées : portail citoyen (bulletin B3 en ligne, paiement mobile money), portail
  avocats, biométrie d'identification, visio-audiences pour détenus, interconnexions régionales
  (entraide judiciaire), module justice civile et commerciale sur le même socle.

## 16. Glossaire

| Terme | Définition |
|---|---|
| OPJ | Officier de police judiciaire : habilité à conduire enquêtes et GAV |
| Garde à vue (GAV) | Rétention d'un suspect par la police, durée strictement encadrée |
| Déferrement | Présentation d'une personne au procureur à l'issue de la GAV |
| Classement sans suite | Décision du parquet de ne pas poursuivre, motivée |
| Information judiciaire / Instruction | Enquête approfondie conduite par un juge d'instruction |
| Mise en examen | Statut d'une personne contre laquelle existent des indices graves ou concordants |
| Détention provisoire | Incarcération avant jugement, exceptionnelle et limitée dans le temps |
| Contrôle judiciaire | Alternative à la détention imposant des obligations |
| Ordonnance de règlement | Décision du juge d'instruction clôturant l'information (renvoi/non-lieu) |
| Relaxe / Acquittement | Décision déclarant la personne non coupable |
| Minute | Original de la décision de justice conservé au greffe |
| Caractère définitif | État d'une décision insusceptible de recours, déclenchant exécution + casier |
| Écrou | Enregistrement d'une personne détenue dans un établissement pénitentiaire |
| TIG | Travail d'intérêt général |
| Casier judiciaire | Registre national des condamnations, bulletins B1/B2/B3 |
| Réhabilitation | Effacement légal/judiciaire d'une condamnation après délai sans nouvelle infraction |
| Chaîne de conservation | Traçabilité continue des pièces à conviction (chain of custody) |
| PRA | Plan de reprise d'activité après incident majeur |
