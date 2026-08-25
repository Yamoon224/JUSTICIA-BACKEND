# Module Parquet

Périmètre : cahier des charges §6.5 (Parquet : orientation des poursuites).

**Implémenté (Phase 4)** :
- Bureau des arrivées : réception automatique à la transmission d'une
  affaire (`Affaires\Actions\TransmettreAuParquetAction`), affectation à un
  magistrat (`AffecterMagistratAction`).
- Orientation des poursuites (`OrienterAction`) : classement sans suite
  (motif obligatoire), rappel à la loi, médiation pénale, composition
  pénale, citation directe, ouverture d'information, comparution immédiate
  — décision toujours humaine, jamais automatique (§3).
- Réquisitions consignées aux différentes étapes (`EnregistrerRequisitionAction`).
- Cloisonnement par ressort (`DossierParquetPolicy`).

**Restant à faire** (Phase 5+ ou hors périmètre socle) :
- Suivi du portefeuille par magistrat au-delà du filtre `mon_portefeuille`
  (stock, anciennes, urgentes/détenus — dépend des délais de détention
  provisoire, Phase 4 Instruction).
- Notification des classements sans suite aux plaignants (nécessite un
  modèle de contact plaignant non encore prévu).
- Traitement des déferrements en temps réel (dépend de la clôture GAV avec
  issue `deferement`, déjà modélisée côté GardeAVue — le lien fonctionnel
  reste à construire).
- Lien citation directe / comparution immédiate → Audiencement (Phase 5).
