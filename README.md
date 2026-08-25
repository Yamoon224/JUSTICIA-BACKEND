# JUSTICIA — Backend (API Laravel)

API REST de **JUSTICIA**, système de gestion de la chaîne pénale
(interpellation → garde à vue → identification → parquet → instruction →
jugement → exécution des peines → casier judiciaire). Cahier des charges
complet : [docs/cahier-des-charges.md](docs/cahier-des-charges.md).

Consommée exclusivement par le frontend [JUSTICIA-WEB](https://github.com/Yamoon224/JUSTICIA-WEB)
(NextJS), qui agit en BFF et ne parle jamais directement à MySQL.

## Pile technique

PHP 8.3 · Laravel 13 · MySQL 8 · Redis (queues & cache, via predis) ·
Sanctum (auth par token) · spatie/laravel-permission (rôles/habilitations).

## État d'avancement

- ✅ **Phase 2 — Socle technique** : auth, habilitations par rôle/ressort,
  référentiels de base, journal d'audit inviolable (append-only, scellé par
  chaînage cryptographique).
- ✅ **Phase 3 — Enquête & garde à vue** (§6.1-6.4) : personnes (fichier
  central, fusion tracée), affaires (dossier, PV immuables, scellés avec
  chaîne de conservation), garde à vue (délais résolus depuis le
  référentiel, régime mineur automatique, moteur d'alertes) — voir
  `app/Domain/{GardeAVue,Personnes,Affaires}`.
- ✅ **Phase 4 — Parquet & instruction** (§6.5-6.6, §13 du planning) :
  - Parquet : bureau des arrivées, affectation à un magistrat, orientation
    des poursuites (7 issues, motif obligatoire pour un classement),
    réquisitions — voir `app/Domain/Parquet`.
  - Instruction : dossier d'information ouvert automatiquement à
    l'orientation, mise en examen, actes, mandats, mesures de sûreté
    (contrôle judiciaire, détention provisoire avec délais résolus depuis
    le référentiel), ordonnance de règlement (renvoi/non-lieu) — voir
    `app/Domain/Instruction`.
- ⏳ Phases 5 à 9 (Jugement & recours, Exécution, Casier, Statistiques,
  Recette, Pilote) : à venir.

## Démarrage

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

Prérequis : MySQL 8 et Redis actifs (voir `.env.example`). Compte de
démonstration créé par le seed : `matricule=ADMIN-0001`, `password=password`
(à changer avant toute donnée réelle — §11 du cahier des charges).

## Tests

```bash
php artisan test
```

La suite tourne sur SQLite en mémoire (voir `phpunit.xml`) : aucune
dépendance à MySQL/Redis pour les tests.

## Conventions (§10.1 du cahier des charges)

- Un module métier étanche par domaine sous `app/Domain/<Module>`
  (`GardeAVue`, `Personnes`, `Affaires`, `Parquet`, `Instruction`,
  `Audiencement`, `Execution`, `Casier`), chacun avec ses `Actions/` (un acte
  de procédure = une classe dédiée) et ses `Models/`.
- Contrats fins injectés (`app/Domain/Contracts`) : `Horodatable`,
  `Auditable`, `Signable`, `Notifiable` — le cœur métier ne dépend jamais
  d'une implémentation technique directement.
- Toute consultation ou modification sensible passe par
  `App\Domain\Audit\AuditService::consigner()` — jamais d'écriture directe
  sur `audit_logs`.
- Contrôleurs minces, validation dans des `FormRequest`, réponses via des
  `JsonResource` sans enveloppe `data` (voir `AppServiceProvider`).
- Laravel Pint bloquant avant tout commit (`./vendor/bin/pint`).
