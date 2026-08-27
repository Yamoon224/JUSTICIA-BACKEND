<?php

namespace Tests\Feature\Documents;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\Scelle;
use App\Domain\Documents\Models\Document;
use App\Domain\Personnes\Models\Personne;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Versement et lecture des pièces (§6.2 photos/pièces d'identité, §6.3
 * pièces d'affaire cotées, §6.4 photos de scellé, §9 stockage chiffré).
 */
class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
        Storage::fake('pieces');
    }

    private function ressort(string $suffixe = 'A'): Ressort
    {
        return Ressort::query()->create(['code' => "TRIB-{$suffixe}", 'nom' => "Tribunal {$suffixe}", 'type' => 'tribunal']);
    }

    private function opj(Ressort $ressort): User
    {
        $service = Service::query()->firstOrCreate(['code' => 'PJ'], ['nom' => 'Police judiciaire', 'type' => 'police']);
        $opj = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $opj->assignRole('opj');

        return $opj;
    }

    private function personne(): Personne
    {
        return Personne::query()->create([
            'identifiant_unique' => (string) Str::uuid(), 'type' => 'physique', 'nom' => 'Kouassi',
        ]);
    }

    private function affaire(User $opj): Affaire
    {
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');

        return Affaire::query()->findOrFail($affaireId);
    }

    public function test_versement_d_une_photo_sur_une_personne_est_stocke_chiffre_et_journalise(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $personne = $this->personne();
        $fichier = UploadedFile::fake()->image('photo.jpg', 200, 200);

        $response = $this->actingAs($opj)->postJson("/api/v1/personnes/{$personne->id}/documents", [
            'fichier' => $fichier,
            'categorie' => 'photo',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('categorie', 'photo');
        $response->assertJsonPath('nom_original', 'photo.jpg');
        $response->assertJsonPath('cote', null);
        $this->assertNotEmpty($response->json('hash_integrite'));

        $document = Document::query()->findOrFail($response->json('id'));
        $this->assertTrue(Storage::disk('pieces')->exists($document->chemin_stockage));

        // Le contenu sur disque n'est jamais le contenu en clair (§8, §9) :
        // seul le déchiffrement applicatif (StockageDocumentsChiffre) permet
        // de le retrouver.
        $brut = Storage::disk('pieces')->get($document->chemin_stockage);
        $this->assertNotSame(file_get_contents($fichier->getRealPath()), $brut);
        $this->assertSame(
            file_get_contents($fichier->getRealPath()),
            Crypt::decryptString($brut)
        );

        $this->assertDatabaseHas('audit_logs', ['action' => 'documents.versement']);
    }

    public function test_versement_d_une_piece_sur_une_affaire_incremente_la_cotation(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $affaire = $this->affaire($opj);

        $premiere = $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire->id}/documents", [
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf'),
        ]);
        $seconde = $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire->id}/documents", [
            'fichier' => UploadedFile::fake()->image('scan.png'),
        ]);

        $premiere->assertCreated()->assertJsonPath('categorie', 'piece_versee')->assertJsonPath('cote', 1);
        $seconde->assertCreated()->assertJsonPath('cote', 2);
    }

    public function test_versement_sur_un_scelle_reprend_l_habilitation_de_l_affaire_qui_le_porte(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $opjA = $this->opj($ressortA);
        $affaire = $this->affaire($opjA);
        $scelle = Scelle::query()->create([
            'affaire_id' => $affaire->id, 'numero_scelle' => 'SC-1', 'description' => 'Test',
            'statut' => 'en_depot', 'created_by' => $opjA->id,
        ]);

        $intrus = $this->opj($ressortB);
        $this->actingAs($intrus)->postJson("/api/v1/scelles/{$scelle->id}/documents", [
            'fichier' => UploadedFile::fake()->image('scelle.jpg'),
        ])->assertForbidden();

        $proprietaire = $this->opj($ressortA);
        $this->actingAs($proprietaire)->postJson("/api/v1/scelles/{$scelle->id}/documents", [
            'fichier' => UploadedFile::fake()->image('scelle.jpg'),
        ])->assertCreated()->assertJsonPath('categorie', 'photo');
    }

    public function test_telechargement_dechiffre_verifie_l_integrite_et_journalise_avec_motif(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $personne = $this->personne();
        $fichier = UploadedFile::fake()->image('photo.jpg');
        $contenuOriginal = file_get_contents($fichier->getRealPath());

        $documentId = $this->actingAs($opj)->postJson("/api/v1/personnes/{$personne->id}/documents", [
            'fichier' => $fichier, 'categorie' => 'photo',
        ])->json('id');

        $telechargement = $this->actingAs($opj)->get("/api/v1/documents/{$documentId}?motif=Verification");

        $telechargement->assertOk();
        $this->assertSame($contenuOriginal, $telechargement->getContent());
        $this->assertSame('image/jpeg', $telechargement->headers->get('Content-Type'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'documents.consultation']);
    }

    /**
     * CONSTAT DE SÉCURITÉ (revue du 2026-08-27) : le fichier des personnes
     * n'est pas cloisonné par ressort (§6.2) — la seule contrepartie est que
     * toute consultation soit motivée et journalisée, comme pour la fiche
     * personne elle-même (ConsulterPersonneRequest) ou un bulletin du
     * casier (GenererBulletinRequest). Le téléchargement d'un document lié
     * à une personne doit donc exiger un motif au même titre.
     */
    public function test_le_telechargement_d_un_document_de_personne_exige_un_motif(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $personne = $this->personne();

        $documentId = $this->actingAs($opj)->postJson("/api/v1/personnes/{$personne->id}/documents", [
            'fichier' => UploadedFile::fake()->image('photo.jpg'), 'categorie' => 'photo',
        ])->json('id');

        $this->actingAs($opj)->getJson("/api/v1/documents/{$documentId}")->assertStatus(422);
        $this->actingAs($opj)->getJson("/api/v1/documents/{$documentId}?motif=Verification")->assertOk();
    }

    /**
     * À la différence d'une personne, une affaire est déjà cloisonnée par
     * ressort (AffairePolicy) : le motif reste une aide au contexte, pas la
     * seule contrepartie d'accès — il n'est donc pas rendu obligatoire ici.
     */
    public function test_le_telechargement_d_un_document_d_affaire_ne_requiert_pas_de_motif(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $affaire = $this->affaire($opj);

        $documentId = $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire->id}/documents", [
            'fichier' => UploadedFile::fake()->image('scan.jpg'),
        ])->json('id');

        $this->actingAs($opj)->getJson("/api/v1/documents/{$documentId}")->assertOk();
    }

    public function test_une_alteration_du_fichier_chiffre_est_detectee_a_la_lecture(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $personne = $this->personne();

        $documentId = $this->actingAs($opj)->postJson("/api/v1/personnes/{$personne->id}/documents", [
            'fichier' => UploadedFile::fake()->image('photo.jpg'), 'categorie' => 'photo',
        ])->json('id');

        $document = Document::query()->findOrFail($documentId);
        $dechiffre = Crypt::decryptString(Storage::disk('pieces')->get($document->chemin_stockage));
        // Falsification indétectable sans le contrôle d'intégrité applicatif :
        // le contenu reste déchiffrable (même clé), mais ne correspond plus
        // au hash enregistré au versement.
        Storage::disk('pieces')->put($document->chemin_stockage, Crypt::encryptString($dechiffre.'ALTERE'));

        $this->actingAs($opj)->get("/api/v1/documents/{$documentId}?motif=Verification")->assertStatus(500);
    }

    public function test_un_type_de_fichier_non_autorise_est_rejete(): void
    {
        $ressort = $this->ressort();
        $opj = $this->opj($ressort);
        $personne = $this->personne();

        $this->actingAs($opj)->postJson("/api/v1/personnes/{$personne->id}/documents", [
            'fichier' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
            'categorie' => 'photo',
        ])->assertStatus(422);
    }

    public function test_un_agent_hors_ressort_ne_peut_pas_verser_de_piece_sur_l_affaire(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $affaire = $this->affaire($this->opj($ressortA));
        $intrus = $this->opj($ressortB);

        $this->actingAs($intrus)->postJson("/api/v1/affaires/{$affaire->id}/documents", [
            'fichier' => UploadedFile::fake()->image('scan.jpg'),
        ])->assertForbidden();
    }
}
