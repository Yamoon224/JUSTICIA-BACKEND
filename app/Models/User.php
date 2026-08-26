<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Contracts\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Agent habilité JUSTICIA (OPJ, magistrat, greffier, agent pénitentiaire,
 * agent du casier, administrateur...). Le compte est strictement individuel
 * et nominatif (§8) ; le profil applicatif est porté par un rôle
 * spatie/laravel-permission, et le périmètre de visibilité par le
 * rattachement à un service et un ressort (§4, §8).
 */
#[Fillable([
    'matricule', 'nom', 'prenom', 'email', 'password', 'service_id', 'ressort_id',
    'actif', 'suspendu_at', 'cree_par', 'valide_par', 'valide_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
            'suspendu_at' => 'datetime',
            'valide_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Ressort, $this>
     */
    public function ressort(): BelongsTo
    {
        return $this->belongsTo(Ressort::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cree_par');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validePar(): BelongsTo
    {
        return $this->belongsTo(self::class, 'valide_par');
    }

    public function estValide(): bool
    {
        return $this->valide_at !== null;
    }

    public function nomComplet(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function auditableRepresentation(): array
    {
        return [
            'user_id' => $this->id,
            'matricule' => $this->matricule,
        ];
    }
}
