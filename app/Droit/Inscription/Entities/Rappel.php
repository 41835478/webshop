<?php

namespace App\Droit\Inscription\Entities;

use Illuminate\Database\Eloquent\Model;

class Rappel extends Model
{
    protected $table = 'colloque_inscription_rappels';

    protected $fillable = ['user_id', 'group_id', 'inscription_id','colloque_id'];

    public function getDocRappelAttribute()
    {
        $path  = config('documents.colloque.rappel');
        $file  = $path.'rappel_'.$this->id.'.pdf';

        if (\File::exists(public_path($file)))
        {
            return $file;
        }

        return $file;
    }

    public function inscription()
    {
        return $this->belongsTo('App\Droit\Inscription\Entities\Inscription');
    }
}
