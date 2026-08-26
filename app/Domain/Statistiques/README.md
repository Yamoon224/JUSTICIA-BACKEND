# Module Statistiques

Périmètre : cahier des charges §6.11-6.12 (délais/alertes et pilotage statistique).

**Implémenté (Phase 8)** :
- Tableau de bord agrégé (`GenererTableauDeBordAction`,
  `GET /statistiques/tableau-de-bord`) : uniquement des lectures dérivées
  des tables existantes — aucune migration, aucune écriture, aucun état
  propre à ce module. Compte les affaires par statut, les mesures de garde
  à vue et de détention provisoire en cours/en échéance dépassée, la
  répartition des orientations du parquet, l'avancement de l'audiencement,
  les dossiers d'exécution et écrous en cours, et les condamnations
  actives/réhabilitées/amnistiées au casier.
- Délais moyens de traitement (garde à vue en heures, instruction et
  délai avant jugement en jours), calculés en PHP (Carbon) plutôt qu'en
  SQL — `DATEDIFF` n'est pas portable entre MySQL (production) et SQLite
  (moteur de test), et le volume d'un socle reste compatible avec un
  calcul en mémoire.
- Cloisonnement par ressort (§8), même principe que le reste du socle : un
  agent muni de `statistiques.consulter` (ex. `chef_juridiction`) est
  cantonné à son propre ressort quoi qu'il transmette en paramètre ; seul
  `administration.gerer` peut choisir un ressort arbitraire ou l'agrégat
  national (aucun `ressort_id`, c'est-à-dire aucun filtre).
- Le casier judiciaire (§6.10) reste toujours national dans sa section du
  tableau de bord, y compris dans une vue par ressort — cohérent avec le
  choix architectural de la Phase 7 (pas de colonne `ressort_id` sur
  `casier_condamnations`).
- `GET /referentiels/ressorts` : liste des ressorts territoriaux, pour le
  sélecteur du tableau de bord côté administrateur.

**Décisions de périmètre explicites (pas des oublis)** :
- Pas de séries temporelles ni d'export (CSV/PDF) : seulement un
  instantané courant. Un historique de tendance supposerait un
  mécanisme de snapshot périodique, hors socle actuel.
- Les délais moyens n'excluent pas les valeurs aberrantes (un dossier
  resté ouvert des années à cause d'une donnée de démonstration
  fausserait la moyenne) — acceptable en environnement de recette, à
  revoir avant un déploiement réel.

**Restant à faire (Phase 9+)** :
- Export et historisation pour un pilotage dans la durée.
- Statistiques dédiées par cour d'appel (agrégation intermédiaire entre
  ressort et national, en s'appuyant sur `Ressort::enfants()`).
