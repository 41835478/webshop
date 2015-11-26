<?php namespace App\Droit\Abo\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Abo_users extends Model{

    use SoftDeletes;

    protected $table = 'abo_users';

    protected $fillable = array('abo_id','numero','exemplaires','adresse_id','tiers_id','price','reference','remarque','status','renouvellement','plan');

    public function abo()
    {
        return $this->belongsTo('App\Droit\Abo\Entities\Abo','abo_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Droit\Adresse\Entities\Adresse','adresse_id');
    }

    public function tiers()
    {
        return $this->belongsTo('App\Droit\Adresse\Entities\Adresse','tiers_id');
    }

    public function factures()
    {
        return $this->hasMany('App\Droit\Abo\Entities\Abo_factures','abo_user_id','id');
    }

}