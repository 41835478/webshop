<?php

namespace App\Droit\Generate\Export;

class ExportBadge
{
    protected $config;
    protected $range = null;
    
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setRange($range)
    {
        $this->range = $range;
    }
    
    public function export($inscriptions, $colloque = null)
    {
        $inscriptions = $inscriptions->map(function ($inscription) {
            if(!is_numeric($inscription->name_inscription)) {
                $name = explode(' ', $inscription->name_inscription);
                $name = count($name) > 2 ? $name[1].' '.$name[2] : end($name);
            }
            else{
                $name = $inscription->name_inscription;
            }
            return ['name' => $inscription->name_inscription, 'last_name' => str_slug($name)];
        });

        if($this->range){
            $inscriptions = $inscriptions->filter(function ($inscription, $key) {
                $first = $inscription['last_name'][0];
                return in_array($first, $this->range);
            });
        }

        $inscriptions = collect($inscriptions)->sortBy('last_name')->pluck('name')->toArray();

        $data   = $this->chunkData($inscriptions, $this->config['cols'], $this->config['etiquettes']);

        $config = $this->config + ['data' => $data, 'colloque' => $colloque];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed'=> TRUE
            ]
        ]);
        $pdf = \App::make('dompdf.wrapper');
        $pdf->getDomPDF()->setHttpContext($context);

        return $pdf->loadView('backend.export.badge', $config)->setPaper('a4')->download('Badges_colloque_' . $colloque->id . '.pdf');
    }

    public function chunkData($data,$cols,$nbr)
    {
        if(!empty($data))
        {
            $chunks = array_chunk($data,$cols);
            $chunks = array_chunk($chunks,$nbr/$cols);

            return $chunks;
        }
        return [];
    }
}