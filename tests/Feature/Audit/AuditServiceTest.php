<?php

namespace Tests\Feature\Audit;

use App\Domain\Audit\AuditService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Le journal d'audit doit être infalsifiable (§8, §9) : chaînage
 * cryptographique vérifiable, et impossibilité de modifier ou supprimer une
 * entrée existante (append-only).
 */
class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_chaine_de_hashs_reste_verifiable_apres_plusieurs_entrees(): void
    {
        $audit = $this->app->make(AuditService::class);
        $agent = User::factory()->create();

        $audit->consigner('test.action_un', acteur: $agent);
        $audit->consigner('test.action_deux', acteur: $agent);
        $audit->consigner('test.action_trois', acteur: $agent);

        $this->assertSame(3, $audit->verifierChaine());
    }

    public function test_une_alteration_d_une_entree_rompt_la_chaine(): void
    {
        $audit = $this->app->make(AuditService::class);
        $agent = User::factory()->create();

        $audit->consigner('test.action_un', acteur: $agent);
        $audit->consigner('test.action_deux', acteur: $agent);

        // Contourne le modèle (qui interdit les mises à jour) pour simuler
        // une falsification directe en base — c'est justement ce que le
        // chaînage doit permettre de détecter.
        AuditLog::query()->first()->forceFill(['action' => 'falsifie'])->saveQuietly();

        $this->expectException(HttpException::class);
        $audit->verifierChaine();
    }

    public function test_une_entree_d_audit_ne_peut_pas_etre_modifiee(): void
    {
        $audit = $this->app->make(AuditService::class);
        $log = $audit->consigner('test.action', acteur: User::factory()->create());

        $this->expectException(LogicException::class);
        $log->update(['action' => 'autre']);
    }

    public function test_une_entree_d_audit_ne_peut_pas_etre_supprimee(): void
    {
        $audit = $this->app->make(AuditService::class);
        $log = $audit->consigner('test.action', acteur: User::factory()->create());

        $this->expectException(LogicException::class);
        $log->delete();
    }
}
