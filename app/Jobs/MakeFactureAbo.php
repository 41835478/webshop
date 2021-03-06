<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeFactureAbo extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $facture;
    protected $all;
    protected $abos;
    protected $product;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($abos, $product, $all)
    {
        $this->facture    = \App::make('App\Droit\Abo\Repo\AboFactureInterface');
        $this->all        = $all;
        $this->abos       = $abos;
        $this->product    = $product;

        setlocale(LC_ALL, 'fr_FR.UTF-8');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $generator = \App::make('App\Droit\Generate\Pdf\PdfGeneratorInterface');

        // All abonnements for the product
        if(!$this->abos->isEmpty())
        {
            foreach($this->abos as $abonnement)
            {
                if(!$abonnement->deleted_at){
                    // Do we already have a facture in the DB?
                    $facture = $this->facture->findByUserAndProduct($abonnement->id,  $this->product->id);

                    // If not and the abonnement is an abonne create a facture
                    if(!$facture && $abonnement->status == 'abonne')
                    {
                        $facture = $this->facture->create([
                            'abo_user_id' => $abonnement->id,
                            'product_id'  => $this->product->id,
                            'created_at'  => date('Y-m-d')
                        ]);
                    }

                    // If we want all factures to be remade or made if none exist
                    // does an pdf already exist? if not make one
                    // All is for the controller otherwise for sending
                    if($this->all || ($facture && !$facture->abo_facture)) {
                        $generator->makeAbo('facture', $facture);
                    }
                }
            }
        }

    }
}
