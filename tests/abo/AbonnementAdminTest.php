<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \Symfony\Component\HttpFoundation\File\UploadedFile;

class AbonnementAdminTest extends TestCase {

    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        DB::beginTransaction();

        //Login as admin
        $user = factory(App\Droit\User\Entities\User::class,'admin')->create();

        $user->roles()->attach(1);
        $this->actingAs($user);
    }

    public function tearDown()
    {
        Mockery::close();
        DB::rollBack();
        parent::tearDown();
    }
    
    public function testAboMakeNewUsers()
    {
        $make = new \tests\factories\ObjectFactory();
        $abo     = $make->makeAbo();
        $adresse = $make->user();

        $this->visit('admin/abonnements/'.$abo->id);
        $this->assertViewHas('abo');

        $this->click('addAbonne');

        $this->seePageIs('admin/abonnement/create/'.$abo->id);

        $data = [
            'abo_id'         => $abo->id,
            'numero'         => 1,
            'exemplaires'    => 1,
            'adresse_id'     => $adresse->adresses->first()->id,
            'status'         => 'abonne',
            'renouvellement' => 'auto',
        ];

        $response = $this->call('POST', '/admin/abonnement', $data);

        $this->seeInDatabase('abo_users', [
            'abo_id'     => $abo->id,
            'adresse_id' => $adresse->adresses->first()->id,
        ]);
    }

    public function testFactureUserEdition()
    {
        $make = new \tests\factories\ObjectFactory();
        $abo     = $make->makeAbo();

        $this->visit('admin/factures/'.$abo->current_product->id);
        $this->assertViewHas('factures');
    }

    public function testRappelsUser()
    {
        $make = new \tests\factories\ObjectFactory();

        $abonnement = $make->makeAbonnement();
        $make->abonnementFacture($abonnement);

        $this->visit(url('admin/rappel/'.$abonnement->abo->current_product->id));
        $this->assertViewHas('factures');
    }

    public function testEditFacture()
    {
        $make = new \tests\factories\ObjectFactory();

        $abonnement = $make->makeAbonnement();
        $make->abonnementFacture($abonnement);

        $facture = $abonnement->factures->first();

        $this->visit(url('admin/facture/'.$facture->id));
        $this->type('2016-12-31', 'payed_at');
        $this->type('2016-12-31', 'created_at');
        $this->press('editFacture');

        $this->seeInDatabase('abo_factures', [
            'id'       => $facture->id,
            'payed_at' => '2016-12-31',
            'created_at' => '2016-12-31',
        ]);
    }

    public function testEditFactureDisplayInAbonnement()
    {
        $make = new \tests\factories\ObjectFactory();

        $abonnement = $make->makeAbonnement();
        $make->abonnementFacture($abonnement);

        $facture = $abonnement->factures->first();

        $this->visit(url('admin/facture/'.$facture->id));
        $this->type('2016-12-31', 'payed_at');
        $this->press('editFacture');

        $this->seeInDatabase('abo_factures', [
            'id'       => $facture->id,
            'payed_at' => '2016-12-31'
        ]);

        $this->visit(url('admin/abonnement/'.$facture->abo_user_id));
        $this->see('Payé le 2016-12-31');

    }

    public function testDesinscriptionPage()
    {
        $make = new \tests\factories\ObjectFactory();
        $abo  = $make->makeAbo();

        $this->visit(url('admin/abo/desinscription/'.$abo->id));
        $this->assertViewHas('abo');
    }

    public function testDesinscriptionAboUser()
    {
        $make = new \tests\factories\ObjectFactory();

        $abonnement = $make->makeAbonnement();
        $make->abonnementFacture($abonnement);

        $this->visit(url('admin/abonnements/'.$abonnement->abo_id));
        $this->assertViewHas('abo');

        // desinscription
        $this->press('deleteAbo_'.$abonnement->id);

        $this->notSeeInDatabase('abo_users', [
            'id'         => $abonnement->id,
            'deleted_at' => null
        ]);

        $this->visit(url('admin/abo/desinscription/'.$abonnement->abo_id));
        $this->assertViewHas('abo');

        // restore abo
        $this->press('restore_'.$abonnement->id);

        $this->seeInDatabase('abo_users', [
            'id'         => $abonnement->id,
            'deleted_at' => null
        ]);

    }
}
