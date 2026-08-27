<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Profils utilisateurs et droits principaux (§4) : un rôle par profil métier,
 * et des permissions à la maille des modules fonctionnels (§6). Le
 * cloisonnement fin par ressort/service reste porté par les Policies
 * Eloquent (App\Policies), pas par ce référentiel.
 */
class RolesEtPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const ROLES_PERMISSIONS = [
        'opj' => ['gav.gerer', 'personnes.gerer', 'affaires.gerer'],
        'chef_unite' => ['gav.gerer', 'personnes.gerer', 'affaires.gerer', 'affaires.superviser'],
        'procureur' => ['parquet.gerer', 'affaires.consulter', 'personnes.consulter'],
        'juge_instruction' => ['instruction.gerer', 'affaires.consulter', 'personnes.consulter'],
        'juge_audience' => ['audiencement.gerer', 'affaires.consulter'],
        // personnes.consulter : gérer ou consulter le casier de quelqu'un
        // (§6.10) suppose de pouvoir d'abord le rechercher dans le fichier
        // national des personnes (§6.2) — même besoin que le procureur ou
        // le juge d'instruction, déjà titulaires de cette permission.
        'greffier' => ['audiencement.gerer', 'casier.gerer', 'affaires.consulter', 'personnes.consulter'],
        'agent_penitentiaire' => ['execution.gerer'],
        'agent_casier' => ['casier.gerer', 'casier.consulter_nominatif', 'personnes.consulter'],
        'chef_juridiction' => ['statistiques.consulter'],
    ];

    /**
     * Permissions qui n'appartiennent à aucun rôle métier opérationnel —
     * seul l'administrateur les détient.
     *
     * @var list<string>
     */
    private const PERMISSIONS_ADMINISTRATIVES = ['administration.gerer', 'habilitations.gerer'];

    public function run(): void
    {
        $permissions = collect(self::ROLES_PERMISSIONS)
            ->flatten()
            ->merge(self::PERMISSIONS_ADMINISTRATIVES)
            ->unique();

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission));

        foreach (self::ROLES_PERMISSIONS as $role => $permissionNames) {
            Role::findOrCreate($role)->syncPermissions($permissionNames);
        }

        // §4, §6.13 : l'administrateur est un superutilisateur — toutes les
        // permissions existantes, opérationnelles comme administratives, pas
        // une liste figée qui dériverait au fil des modules ajoutés. Le
        // cloisonnement par ressort ne s'applique jamais à lui non plus : les
        // Policies (AffairePolicy, DossierParquetPolicy, ...) contournent déjà
        // toutes `memeRessort()` dès que l'agent détient `administration.gerer`.
        Role::findOrCreate('administrateur')->syncPermissions($permissions->all());
    }
}
