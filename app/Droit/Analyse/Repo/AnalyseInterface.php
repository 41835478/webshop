<?php namespace App\Droit\Analyse\Repo;

interface AnalyseInterface {

    public function getAll($include = [],$site = null);
	public function find($data);
	public function create(array $data);
	public function update(array $data);
	public function delete($id);

}
