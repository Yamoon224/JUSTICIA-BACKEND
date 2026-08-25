# Module Instruction

Périmètre : cahier des charges §6.6 (Instruction).

**Implémenté (Phase 4)** :
- Dossier d'information, ouvert automatiquement par l'orientation
  « ouverture d'information » du parquet
  (`Parquet\Actions\OrienterAction`).
- Affectation à un juge d'instruction du ressort (`AffecterJugeInstructionAction`).
- Mise en examen / témoin assisté, avec date tracée sur le pivot
  `affaire_personne` (§6.2) — jamais sur la fiche personne.
- Actes d'instruction (interrogatoire, confrontation, transport,
  commission rogatoire, expertise) avec suivi (retour reçu, rapport déposé).
- Mandats (comparution, amener, dépôt, arrêt) : émission, diffusion,
  exécution tracées séparément.
- Mesures de sûreté : contrôle judiciaire (obligations) et détention
  provisoire, dont le délai maximal est résolu depuis le référentiel
  `delais_legaux` via `App\Domain\Support\ResolveurDelaiLegal` (partagé
  avec GardeAVue) — jamais codé en dur. Renouvellement et mainlevée
  tracés. Moteur d'alertes (`DetecterEcheancesDetentionAction`) : une
  détention à échéance sans décision est signalée en priorité absolue.
- Ordonnance de règlement (renvoi ou non-lieu), seule décision qui
  clôture le dossier ; répercute le bon statut sur l'affaire — un
  non-lieu ne laisse jamais de trace de condamnation (§3).
- Cloisonnement par ressort (`DossierInstructionPolicy`, indépendante
  d'`AffairePolicy::update` qui exige `affaires.gerer`, celle des OPJ).

**Restant à faire** (Phase 5+) :
- Lien renvoi → audiencement (rôle, enrôlement) : l'affaire passe au
  statut `audiencee`, l'enrôlement effectif reste à construire.
- Notification effective des parties (mise en examen, ordonnance) au-delà
  de la trace d'audit — canal technique à brancher (§10.1-D).
- Tableau de bord du cabinet (dossiers en cours, détenus, actes en
  retard) au-delà du filtre `mon_portefeuille`.
