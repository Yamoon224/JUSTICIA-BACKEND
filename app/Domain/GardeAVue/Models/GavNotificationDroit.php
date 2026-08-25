<?php

namespace App\Domain\GardeAVue\Models;

use App\Domain\Contracts\Notifiable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mesure_id', 'droit', 'notifie_at', 'mode_de_remise', 'enregistre_par'])]
class GavNotificationDroit extends Model implements Notifiable
{
    // La pluralisation par défaut d'Eloquent donnerait "gav_notification_droits".
    protected $table = 'gav_notifications_droits';

    protected function casts(): array
    {
        return [
            'notifie_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MesureGardeAVue, $this>
     */
    public function mesure(): BelongsTo
    {
        return $this->belongsTo(MesureGardeAVue::class, 'mesure_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }

    public function estNotifie(): bool
    {
        return $this->notifie_at !== null;
    }

    public function marquerNotifie(string $modeDeRemise): void
    {
        $this->forceFill(['notifie_at' => now(), 'mode_de_remise' => $modeDeRemise])->save();
    }
}
