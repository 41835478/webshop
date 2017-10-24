<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SondageTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->artisan("db:seed");

        $user = factory(\App\Droit\User\Entities\User::class)->create();
        $user->roles()->attach(1);
        $this->actingAs($user);
    }

    public function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testCreateSondageMarketing()
    {
        // Create colloque
        $make     = new \tests\factories\ObjectFactory();
        $colloque = $make->colloque();

        $response = $this->withSession(['colloques' => collect([$colloque])]);

        $response = $response->get('admin/sondage/create');
        $response->assertStatus(200);

        $response = $this->post('admin/sondage', [
            'title' => 'Ceci est un titre',
            'description' => 'Ceci est une description',
            'marketing'   => 1,
            'valid_at'    => \Carbon\Carbon::now()->addDay(5)
        ]);

        $response->isRedirect(url('admin/sondage'));

        $response = $this->get('admin/sondage');
        $response->assertSee('Ceci est une description');
    }



   /*



    public function testReponseNormalPage()
    {
        // Create colloque
        $make     = new \tests\factories\ObjectFactory();
        $colloque = $make->colloque();

        // Create a sondage for the colloque
        $sondage = factory(App\Droit\Sondage\Entities\Sondage::class)->create([
            'colloque_id' => $colloque->id,
            'valid_at'    => \Carbon\Carbon::now()->addDay(5),
        ]);

        // Create and attach a questioin to sondage
        $question = factory(App\Droit\Sondage\Entities\Avis::class)->create(['type' => 'text','question' => 'One question' ,'choices' => null]);
        $sondage->avis()->attach($question->id, ['rang' => 1]);

        // Make the token with the infos
        $token = base64_encode(json_encode([
            'sondage_id' => $sondage->id,
            'email'      => 'cindy.leschaud@gmail.com'
        ]));

        // Visit the sondage page and test if the question exist on the page
        $this->withSession(['sondage' => $sondage, 'email' => 'cindy.leschaud@gmail.com', 'isTest' => null])
            ->visit('reponse/create/'.$token)
            //->see('One question')
            ->submitForm('Envoyer le sondage', [
                'sondage_id' => $sondage->id,
                'reponses'   => [$question->id => 'Ceci est une réponse'],
                'email'      => 'cindy.leschaud@gmail.com',
                'isTest'     => false
            ])
            ->seePageIs('reponse')
            ->see('Merci pour votre participation au sondage!');

        // See if the reponse is in the database
        $this->seeInDatabase('sondage_reponses', [
            'sondage_id' => $sondage->id,
            'email'      => 'cindy.leschaud@gmail.com',
            'isTest'     => null
        ]);

        // Return see the sondage, but it's already done so redirect to reponse page with message
        $this->visit('reponse/create/'.$token)
            ->seePageIs('reponse')
            ->see('Vous avez déjà répondu au sondage!');
    }

    public function testCreateQuestion()
    {
        $this->visit('admin/avis');
        $this->assertViewHas('avis');

        $this->click('addBtn')->seePageIs(url('admin/avis/create'));
        $this->select('text', 'type')
            ->type('Une nouvelle question', 'question')
            ->press('Envoyer');

        // See if the reponse is in the database
        $this->seeInDatabase('sondage_avis', [
            'type'    => 'text',
            'question' => 'Une nouvelle question'
        ]);
    }

    public function testSendToList()
    {
        Queue::fake();
        // Create colloque
        $make     = new \tests\factories\ObjectFactory();
        $colloque = $make->colloque();

        // Create a sondage for the colloque
        $sondage = factory(App\Droit\Sondage\Entities\Sondage::class)->create([
            'colloque_id' => $colloque->id,
            'valid_at'    => \Carbon\Carbon::now()->addDay(5),
        ]);

        // Create and attach a question to sondage
        $question = factory(App\Droit\Sondage\Entities\Avis::class)->create(['type' => 'text','question' => 'One question' ,'choices' => null]);
        $sondage->avis()->attach($question->id, ['rang' => 1]);

        // Create list
        $list   = factory(App\Droit\Newsletter\Entities\Newsletter_lists::class)->create(['title' => 'One liste', 'colloque_id' => $colloque->id]);
        $email1 = factory(App\Droit\Newsletter\Entities\Newsletter_emails::class)->create(['email' => 'contact@domain.com']);
        $email2 = factory(App\Droit\Newsletter\Entities\Newsletter_emails::class)->create(['email' => 'info@domain.com']);

        $list->emails()->save($email1);
        $list->emails()->save($email2);

        $this->visit('admin/sondage');
        $this->assertViewHas('sondages');
        $this->visit(url('admin/sondage/confirmation/'.$sondage->id));

        $this->assertViewHas('listes')->see($colloque->titre);
        $this->select($list->id, 'list_id')->press('Envoyer');

        Queue::assertPushed(\App\Jobs\SendSondage::class, function ($job) use ($sondage,$email1) {
            return $job->sondage->id === $sondage->id && $email1->email === $job->data['email'];
        });

        Queue::assertPushed(\App\Jobs\SendSondage::class, function ($job) use ($sondage,$email2) {
            return $job->sondage->id === $sondage->id && $email2->email === $job->data['email'];
        });
    }

    public function testSendTestEmail()
    {
        Mail::fake();
        // Create colloque
        $make     = new \tests\factories\ObjectFactory();
        $colloque = $make->colloque();

        // Create a sondage for the colloque
        $sondage = factory(App\Droit\Sondage\Entities\Sondage::class)->create([
            'colloque_id' => $colloque->id,
            'valid_at'    => \Carbon\Carbon::now()->addDay(5),
        ]);

        // Create and attach a question to sondage
        $question = factory(App\Droit\Sondage\Entities\Avis::class)->create(['type' => 'text','question' => 'One question' ,'choices' => null]);
        $sondage->avis()->attach($question->id, ['rang' => 1]);

        // filter to get all send orders
        $response = $this->call('POST', url('admin/sondage/send'), ['sondage_id' =>  $sondage->id,'email' => 'info@domain.ch']);

        $this->assertRedirectedTo('admin/sondage');
    }

    public function testMissingAvis()
    {
        // Create colloque
        $make     = new \tests\factories\ObjectFactory();
        $colloque = $make->colloque();

        // Create a sondage for the colloque
        $sondage = factory(App\Droit\Sondage\Entities\Sondage::class)->create([
            'colloque_id' => $colloque->id,
            'valid_at'    => \Carbon\Carbon::now()->addDay(5),
        ]);

        try {
            $response = $this->call('POST', url('admin/sondage/send'), ['sondage_id' =>  $sondage->id, 'email' => 'info@domain.ch']);

        } catch (Exception $e) {
            $this->assertType('App\Exceptions\MissingException', $e);
        }
    }*/
}
