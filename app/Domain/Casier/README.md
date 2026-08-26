# Module Casier

Périmètre : cahier des charges §6.10 (Casier judiciaire).

**Implémenté (Phase 7)** :
- Inscription automatique (`Condamnation`, `EnregistrerCondamnationCasierAction`),
  déclenchée par `App\Domain\Execution\Actions\MettreAExecutionAction` — la
  mise à exécution est le seul point du socle où le caractère définitif
  d'une décision est effectivement vérifié par un acte humain, donc le
  point naturel pour alimenter le casier. Aucune route de création
  manuelle. Chaque ligne capture un **instantané** (numéro d'affaire,
  juridiction, libellé et catégorie de l'infraction la plus grave, peine
  principale, sursis) plutôt que de dériver ces champs à la volée depuis
  la décision source : le contenu d'un bulletin doit rester stable et
  opposable même si le dossier d'origine évolue ensuite.
- Réhabilitation judiciaire (`RehabiliterAction`, décision d'une
  juridiction sur requête de la personne) et réhabilitation de plein
  droit (`DetecterRehabilitationsDePleinDroitAction`, planifiée
  quotidiennement, `casier:verifier-rehabilitations`) : constatée sans
  décision humaine une fois le délai légal écoulé (référentiel
  `delais_legaux`, type d'acte `rehabilitation_plein_droit`) et à
  condition qu'aucune autre condamnation active plus récente n'existe
  pour la même personne.
- Amnistie (`AmnistierAction`) : décision légale/réglementaire explicite,
  texte de référence obligatoire — jamais automatique, à la différence
  de la réhabilitation de plein droit.
- Bulletins B1/B2/B3 (`GenererBulletinAction`) avec des règles de
  filtrage distinctes par statut/catégorie de condamnation (voir le
  docblock de l'Action pour le détail — **simplification assumée du
  socle, à faire valider par la chancellerie avant recette**).
- Contrôle d'accès strict (§6.10) : la génération d'un bulletin est une
  **consultation nominative**, gouvernée par la permission dédiée
  `casier.consulter_nominatif` — plus stricte que `casier.gerer`, qui ne
  permet que la gestion des mentions déjà connues (réhabilitation
  judiciaire, amnistie). Chaque génération de bulletin est journalisée et
  motivée (`Consultation`, motif obligatoire), avec un historique
  consultable séparément (`GET /casier/personnes/{personne}/consultations`).
- **Registre national, pas cloisonné par ressort** (`CondamnationPolicy`)
  — à la différence de tout le reste du socle : une condamnation
  prononcée dans un ressort doit rester visible/gérable depuis n'importe
  quel autre lors d'une consultation ou d'une gestion des mentions.

**Décisions de périmètre explicites (pas des oublis)** :
- Les règles de filtrage B1/B2/B3 sont une simplification délibérée
  (voir `GenererBulletinAction`) — les règles réelles du casier
  judiciaire ivoirien distinguent des cas plus fins selon la nature
  exacte de la peine, à affiner avec un juriste avant recette.
- La réhabilitation de plein droit ne revérifie que les condamnations
  encore *actives* de la même personne pour détecter une récidive dans
  l'intervalle — pas l'historique complet (une condamnation elle-même
  réhabilitée/amnistiée entre-temps n'est pas reconsidérée).

**Restant à faire (Phase 8+)** :
- Délivrance sécurisée d'un extrait imprimable/signé (aucune
  infrastructure de génération de documents dans le projet à ce stade —
  même limitation que pour l'Exécution).
- Notification effective de la personne concernée en cas de
  réhabilitation de plein droit (§10.1-D).
