<?php namespace Mreschke\Repository\Fake2\Stores\Mongo;

use Mreschke\Repository\Fake\Stores\Mongo\ClientStore as FakeClientStore;

/**
 * Fake2 Client Store extends Fake Client Store
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ClientStore extends FakeClientStore
{
	/**
	 * Initialize Store
	 * @return void
	 */
	protected function init() {
		parent::init();
	}

	/**
	 * Merge client info subentity
	 * @param  \Illuminate\Support\Collection $clients
	 */
	protected function mergeInfo($clients)
	{
		$infos = $this->manager->clientInfo->where('clientID', 'in', $clients->lists('id'))->get();
		foreach ($infos as $clientID => $info) {
			$clients[$clientID]->info = $info;
		}
	}

}
