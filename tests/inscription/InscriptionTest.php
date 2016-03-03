<?php

class InscriptionTest extends TestCase {

    protected $mock;
    protected $colloque;
    protected $groupe;
    protected $interface;
    protected $worker;

    public function setUp()
    {
        parent::setUp();

        $this->mock = Mockery::mock('App\Droit\Inscription\Repo\InscriptionInterface');
        $this->app->instance('App\Droit\Inscription\Repo\InscriptionInterface', $this->mock);

        $this->groupe = Mockery::mock('App\Droit\Inscription\Repo\GroupeInterface');
        $this->app->instance('App\Droit\Inscription\Repo\GroupeInterface', $this->groupe);

        $this->worker = Mockery::mock('App\Droit\Inscription\Worker\InscriptionWorkerInterface');
        $this->app->instance('App\Droit\Inscription\Worker\InscriptionWorkerInterface', $this->worker);

        $this->colloque = Mockery::mock('App\Droit\Colloque\Repo\ColloqueInterface');
        $this->app->instance('App\Droit\Colloque\Repo\ColloqueInterface', $this->colloque);

        $user = App\Droit\User\Entities\User::find(710);

        $this->actingAs($user);
    }

    public function tearDown()
    {
         \Mockery::close();
    }

    /**
	 *
	 * @return void
	 */
	public function testRegisterNewInscription()
	{
        $this->WithoutEvents();

        $input = ['type' => 'simple', 'colloque_id' => 71, 'user_id' => 710, 'inscription_no' => '71-2015/1', 'price_id' => 290];

        $inscription = factory(App\Droit\Inscription\Entities\Inscription::class)->make();

        $this->worker->shouldReceive('register')->once()->andReturn($inscription);

        $response = $this->call('POST', '/admin/inscription', $input);

        $this->assertRedirectedTo('/admin/inscription/colloque/71');

	}

    /**
     *
     * @return void
     */
    public function testRegisterMultipleNewInscription()
    {
        $this->WithoutEvents();

        $input = ['type' => 'multiple', 'colloque_id' => 71, 'user_id' => 1, 'participant' => ['Jane Doe', 'John Doa'], 'price_id' => [290, 290] ];

        $group       = factory(App\Droit\Inscription\Entities\Groupe::class)->make();
        $inscription = factory(App\Droit\Inscription\Entities\Inscription::class)->make();

        $this->groupe->shouldReceive('create')->once()->andReturn($group);
        $this->worker->shouldReceive('register')->twice()->andReturn($inscription);

        $response = $this->call('POST', '/admin/inscription',$input);

        $this->assertRedirectedTo('/admin/inscription/colloque/71');

    }

    public function testLastInscriptions()
    {
        $inscriptions = factory(\App\Droit\Inscription\Entities\Inscription::class, 2)->make([
            'user_id' => 710 // thats a real user else the load method for the inscription will throw an error
        ]);

        $this->mock->shouldReceive('getAll')->with(5)->once()->andReturn($inscriptions);

        $response = $this->call('GET', 'admin/inscription');

        $this->assertViewHas('inscriptions');
    }

    /**
     * Inscription from frontend
     * @return void
     */
    public function testRegisterInscription()
    {
        $this->WithoutEvents();

        $input = ['type' => 'simple', 'colloque_id' => 71, 'user_id' => 710, 'inscription_no' => '71-2015/1', 'price_id' => 290];

        $inscription = factory(App\Droit\Inscription\Entities\Inscription::class)->make();

        $this->worker->shouldReceive('register')->once()->andReturn($inscription);

        $response = $this->call('POST', 'registration', $input);

        $this->assertRedirectedTo('/');
    }

    public function testGenerateDoc()
    {
        $annexes = ['bon','facture', 'bv'];

        $inscription        = factory(App\Droit\Inscription\Entities\Inscription::class)->make();
        $inscription->price = factory(App\Droit\Price\Entities\Price::class)->make(['price' => 10000]);

        // make all documents
        foreach($annexes as $annexe)
        {
            $result = $this->make($annexe,$inscription);
            $this->assertTrue($result);
        }
    }

    public function testGenerateDocFree()
    {
        $inscription        = factory(App\Droit\Inscription\Entities\Inscription::class)->make();
        $inscription->price = factory(App\Droit\Price\Entities\Price::class)->make(['price' => 0]);

        // No need to make facture or bv if the price is 0

        $result = $this->make('bon',$inscription);
        $this->assertTrue($result);

        $result = $this->make('facture',$inscription);
        $this->assertFalse($result);

        $result = $this->make('bv',$inscription);
        $this->assertFalse($result);
    }

    public function make($annexe,$inscription)
    {
        if($annexe == 'bon' || ($inscription->price_cents > 0 && ($annexe == 'facture' || $annexe == 'bv')))
        {
            return true;
        }

        return false;
    }


    /**
     * Inscription regenerate documents
     * @return void
     */
    public function testRegenerateDocumentsInscription()
    {
        $this->WithoutEvents();

        $input = ['type' => 'simple', 'colloque_id' => 71, 'user_id' => 710, 'inscription_no' => '71-2015/1', 'price_id' => 290];

        $inscription = factory(App\Droit\Inscription\Entities\Inscription::class)->make();

        $this->worker->shouldReceive('register')->once()->andReturn($inscription);

        $response = $this->call('GET', 'inscription/generate', $input);

        $this->assertRedirectedTo('/');
    }

}
