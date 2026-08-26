# Module Exécution

Périmètre : cahier des charges §6.9 (Exécution des peines et détention).

**Implémenté (Phase 6)** :
- Dossier d'exécution (`DossierExecution`), ouvert manuellement via
  « mise à exécution » d'une décision de condamnation
  (`MettreAExecutionAction`) — exige que la décision soit une
  `condamnation` **et** définitive (`Decision::estDefinitive()`, §6.7 :
  délai de recours expiré sans recours enregistré). C'est l'unique porte
  d'entrée du module : pas d'ouverture automatique depuis Audiencement,
  car toutes les décisions ne deviennent pas définitives au même moment
  (délai de pourvoi/appel).
- Écrou (`Ecrou`) : numéro généré (`ECR-<annee>-<ressort>-<sequence>`,
  même schéma que `Affaire::genererNumero()`), échéance
  (`date_fin_prevue`) calculée à l'écrou puis **recalculée à chaque
  remise de peine** (`EnregistrerRemiseDePeineAction`) — valeur stable
  et directement interrogeable par le moteur d'alertes plutôt que dérivée
  à la volée. Cycle de vie : détention → libération
  (`LibererAction`, avec motif terme/aménagement/grâce, termine aussi le
  dossier d'exécution), transferts d'établissement tracés
  (`TransfertEcrou`), aménagements de peine tracés séparément
  (`AmenagementPeine` : libération conditionnelle, semi-liberté,
  placement extérieur — décision, pas exécution automatique).
- Peines non privatives de liberté, chacune 0..1 par dossier
  d'exécution puisqu'une condamnation peut cumuler plusieurs peines :
  amende (transmission au Trésor puis recouvrement,
  `TransmettreAmendeAction`/`MarquerAmendeRecouvreeAction`), travail
  d'intérêt général (heures requises/effectuées, clôture automatique au
  seuil, `AffecterTigAction`/`EnregistrerHeuresTigAction`), sursis avec
  mise à l'épreuve (obligations, levée, `PlacerSousMiseALEpreuveAction`/
  `LeverMiseALEpreuveAction`).
- Moteur d'alertes (`DetecterEcheancesLiberationAction`), même forme que
  GardeAVue/Instruction : seuils information (15 j), avertissement (3 j),
  dépassement (échéance passée sans libération).
- Référentiel établissement pénitentiaire
  (`GET /referentiels/etablissements-penitentiaires`, filtré par ressort
  sauf `administration.gerer`), avec seed minimal de démonstration.
- `GET /execution/decisions-a-executer` : liste des condamnations
  définitives pas encore mises à exécution, scopée par ressort. C'est le
  véritable point d'entrée du service pénitentiaire vers la mise à
  exécution — celui-ci n'a pas accès au dossier d'audiencement lui-même
  (`DossierAudiencementPolicy` exige `audiencement.gerer`, pas
  `execution.gerer`), donc ne peut pas partir de là.
- Cloisonnement par ressort (`DossierExecutionPolicy`), résolu en
  traversant `decision.dossierAudiencement.affaire.ressort_id`.
- Par construction, aucune des routes de ce module n'accepte de
  `personne_id` en entrée : la personne est toujours dérivée du dossier
  d'exécution (`$dossier->personne_id`), ce qui élimine par conception
  la classe de bug IDOR rencontrée dans d'autres modules.
- `MettreAExecutionAction` déclenche dans la même transaction
  l'inscription au casier judiciaire (§6.10,
  `App\Domain\Casier\Actions\EnregistrerCondamnationCasierAction`) : c'est
  le seul point du socle où le caractère définitif d'une condamnation est
  effectivement vérifié par un acte humain.

**Décisions de périmètre explicites (pas des oublis)** :
- `detention_provisoire_imputee_jours` est saisi manuellement à l'écrou,
  et non recalculé automatiquement depuis les `MesureSurete` d'Instruction
  — tous les parcours n'y passent pas (citation directe, comparution
  immédiate) et un rapprochement fiable dépasse le socle actuel.
- Aucune génération de certificat (présence, détention) : le projet n'a
  pas encore d'infrastructure de génération de documents (PV, minutes
  compris) — capacité transverse à construire plus tard.

**Restant à faire (Phase 8+)** :
- Notification effective des parties/établissements au-delà de la trace
  d'audit (§10.1-D).
- Tableau de bord pénitentiaire (échéances proches, effectifs par
  établissement) au-delà des filtres de liste actuels.
