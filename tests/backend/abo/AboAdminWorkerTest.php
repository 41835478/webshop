<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \Symfony\Component\HttpFoundation\File\UploadedFile;

class AboAdminWorkerTest extends BrowserKitTest {

    use DatabaseTransactions;

    protected $worker;
    protected $generator;
    protected $facture;

    public function setUp()
    {
        parent::setUp();

        $this->worker    = \App::make('App\Droit\Abo\Worker\AboWorkerInterface');
        $this->generator = \App::make('App\Droit\Generate\Pdf\PdfGeneratorInterface');
        $this->facture   = \App::make('App\Droit\Abo\Repo\AboFactureInterface');

        $user = factory(App\Droit\User\Entities\User::class)->create();

        $user->roles()->attach(1);
        $this->actingAs($user);

        DB::beginTransaction();
    }

    public function tearDown()
    {
        Mockery::close();
        DB::rollBack();
        parent::tearDown();
    }

    public function testBindFactures()
    {
        $abo      = $this->makeAbosGetProduct();
        $product  = $abo->products->first();
        $factures = $this->facture->getAll($product->id);

        // Directory for edition => product_id
        $dir        = 'files/abos/facture/'.$product->id;
        $name       = 'factures_'.$product->reference.'_'.$product->edition_clean;
        $filename   = 'files/abos/bound/'.$abo->id.'/'.$name.'.pdf';
        $factureDir = public_path($dir);

        $this->clean($abo,$factureDir);

        $this->assertEquals(3,$factures->count());

        // Make 3 facture pdf
        foreach ($factures as $facture){
            $this->generator->makeAbo('facture', $facture);
        }

        // Get all files in directory
        $files = \File::files($factureDir);

        $this->assertEquals(3, count($files));

        // Merge 3 factures
        $this->worker->merge($files, $name, $abo->id);

        $this->assertEquals(3, $this->getNumPagesPdf(public_path($filename)));
    }

    public function clean($abo,$factureDir)
    {
        // Clean directories, empty or make
        array_map('unlink', glob('files/abos/bound/'.$abo->id));

        if (!\File::exists($factureDir)) { \File::makeDirectory($factureDir, 0777 , true); }
        $this->delTree($factureDir);
    }

    public function makeAbosGetProduct()
    {
        // Make abo
        $make   = new \tests\factories\ObjectFactory();
        $abo    = $make->makeAbo();

        // Make 3 abonnements
        $abonnement1 = $make->makeUserAbonnement($abo);
        $abonnement2 = $make->makeUserAbonnement($abo);
        $abonnement3 = $make->makeUserAbonnement($abo);

        // Make 3 factures
        $make->abonnementFacture($abonnement1);
        $make->abonnementFacture($abonnement2);
        $make->abonnementFacture($abonnement3);

        return $abo;
    }

    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return true;
    }

    public function getNumPagesPdf($filepath) {
        $fp = @fopen(preg_replace("/\[(.*?)\]/i", "", $filepath), "r");
        $max = 0;
        if (!$fp) {
            return "Could not open file: $filepath";
        } else {
            while (!@feof($fp)) {
                $line = @fgets($fp, 255);
                if (preg_match('/\/Count [0-9]+/', $line, $matches)) {
                    preg_match('/[0-9]+/', $matches[0], $matches2);
                    if ($max < $matches2[0]) {
                        $max = trim($matches2[0]);
                        break;
                    }
                }
            }
            @fclose($fp);
        }

        return $max;
    }

}
