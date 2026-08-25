# Module Audiencement

Périmètre : cahier des charges §6.7 (Audiencement et jugement) et §6.8
(Voies de recours).

**Implémenté (Phase 5)** :
- Dossier d'audiencement ouvert automatiquement dès qu'une affaire atteint
  le statut `audiencee` — citation directe ou comparution immédiate
  décidées par le parquet (§6.5), ou renvoi ordonné par le juge
  d'instruction (§6.6). Voir `App\Domain\Audiencement\Actions\OuvrirDossierAudiencementAction`,
  appelée depuis les deux modules amont.
- Enrôlement (juridiction, chambre, date, président, greffier — tous du
  ressort de l'affaire) : un acte du greffe, jamais automatique.
- Renvois d'audience motivés, tracés (append-only, jamais une correction
  silencieuse de la date).
- Décision par prévenu (condamnation, relaxe, acquittement, dispense de
  peine) : répercute immédiatement le statut sur le pivot
  `affaire_personne` (§6.2, §3 — présomption d'innocence).
- Caractère définitif calculé à la volée (`Decision::estDefinitive()`)
  depuis le délai de recours résolu par référentiel et l'absence de
  recours recevable non résolu — jamais un simple booléen qui pourrait
  devenir incohérent avec les faits.
- Voies de recours (appel, opposition, pourvoi en cassation) : recevabilité
  **calculée**, jamais déclarée, au regard du délai de la décision visée.
  Intégration de l'issue (confirmation, infirmation, cassation avec renvoi).

**Restant à faire** (Phase 6+ ou hors périmètre socle) :
- La cascade complète vers l'exécution et le casier judiciaire à
  expiration du délai ou après intégration d'un recours (§14) suit
  l'implémentation de ces modules — non automatisable tant qu'ils
  n'existent pas.
- Une infirmation ne modifie pas automatiquement le statut de la personne :
  la juridiction saisie doit rendre une nouvelle décision via
  `EnregistrerDecisionAction`, comme tout jugement.
- Minute (rédaction, signature président + greffier, répertoire,
  délivrance des extraits/grosses) — non implémentée dans ce périmètre.
- Rôle d'audience imprimable, extractions des détenus, calendrier par
  chambre avec capacité — l'enrôlement actuel se limite à une date et une
  composition par affaire.
- Détection périodique des décisions devenues définitives : `estDefinitive()`
  se calcule à la demande, aucun job planifié ne la déclenche encore
  (même limite que le moteur d'alertes GAV/détention provisoire).
