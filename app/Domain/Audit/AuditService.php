<?php

namespace App\Domain\Audit;

use App\Domain\Contracts\Auditable;
use App\Domain\Contracts\Horodatable;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Journal d'audit central inviolable (§8, §9) : chaque entrée est scellée par
 * chaînage cryptographique — son hash dépend du hash de l'entrée précédente,
 * ce qui rend toute falsification ou suppression a posteriori détectable
 * (une rupture de chaîne saute aux yeux dès qu'on la revérifie).
 *
 * C'est la SEULE voie d'écriture légitime vers la table audit_logs.
 */
class AuditService
{
    public function __construct(
        private readonly Horodatable $horodatage,
    ) {}

    /**
     * Consigne un événement (consultation ou acte) dans le journal d'audit.
     *
     * @param  array<string, mixed>  $payloadSupplementaire
     */
    public function consigner(
        string $action,
        ?Model $auditable = null,
        ?User $acteur = null,
        ?string $motif = null,
        array $payloadSupplementaire = [],
    ): AuditLog {
        $acteur ??= Request::user();

        $payload = $auditable instanceof Auditable
            ? [...$auditable->auditableRepresentation(), ...$payloadSupplementaire]
            : $payloadSupplementaire;

        return DB::transaction(function () use ($action, $auditable, $acteur, $motif, $payload) {
            // Verrouille la dernière entrée pour garantir un chaînage sans
            // rupture même sous écritures concurrentes.
            $derniere = AuditLog::query()->orderByDesc('id')->lockForUpdate()->first();
            $previousHash = $derniere?->hash;

            $uuid = (string) Str::uuid();
            $horodatage = $this->horodatage->maintenant();

            $hash = $this->calculerHash($previousHash, [
                'uuid' => $uuid,
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'user_id' => $acteur?->getKey(),
                'motif' => $motif,
                'payload' => $payload,
                'horodatage' => $horodatage->toIso8601String(),
            ]);

            return AuditLog::query()->create([
                'uuid' => $uuid,
                'user_id' => $acteur?->getKey(),
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'ressort_id' => $acteur?->ressort_id,
                'motif' => $motif,
                'ip_address' => Request::ip(),
                'user_agent' => (string) Request::header('User-Agent'),
                'payload' => $payload,
                'previous_hash' => $previousHash,
                'hash' => $hash,
                'created_at' => $horodatage,
            ]);
        });
    }

    /**
     * Revérifie l'intégralité de la chaîne et retourne le nombre d'entrées
     * contrôlées. Lève une exception au premier maillon rompu.
     */
    public function verifierChaine(): int
    {
        $previousHash = null;
        $compte = 0;

        AuditLog::query()->orderBy('id')->chunkById(500, function ($lot) use (&$previousHash, &$compte): void {
            foreach ($lot as $log) {
                $hashAttendu = $this->calculerHash($previousHash, [
                    'uuid' => $log->uuid,
                    'action' => $log->action,
                    'auditable_type' => $log->auditable_type,
                    'auditable_id' => $log->auditable_id,
                    'user_id' => $log->user_id,
                    'motif' => $log->motif,
                    'payload' => $log->payload,
                    'horodatage' => $log->created_at->toIso8601String(),
                ]);

                abort_unless(
                    hash_equals($hashAttendu, $log->hash) && $log->previous_hash === $previousHash,
                    500,
                    "Rupture de la chaîne d'audit détectée à l'entrée #{$log->id}.",
                );

                $previousHash = $log->hash;
                $compte++;
            }
        });

        return $compte;
    }

    /**
     * @param  array<string, mixed>  $donnees
     */
    private function calculerHash(?string $previousHash, array $donnees): string
    {
        return hash('sha256', ($previousHash ?? '').json_encode($donnees, JSON_THROW_ON_ERROR));
    }
}
