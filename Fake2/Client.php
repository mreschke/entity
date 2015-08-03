<?php namespace Mreschke\Repository\Fake2;

use App;
use Mreschke\Repository\Fake\Client as FakeClient;

/**
 * Fake2 Client Extends Fake Client
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Client extends FakeClient
{
	/**
	 * Get the repository manager
	 * @return object
	 */
	protected function manager()
	{
		// Return the manager from Laravels IoC Singleton
		return App::make('Mreschke\Repository\Fake2');
	}

	/**
	 * Alias to manager
	 * @return object
	 */
	protected function fake2()
	{
		return $this->manager();
	}

	/**
	 * Get the Fake repository manager
	 * @return object
	 */
	protected function fake()
	{
		// We use the Fake API here in Fake2 Client since Fake2 Client extends Fake Client
		return App::make('Mreschke\Repository\Fake');
	}

	/**
	 * Join client info entity
	 * @return $this
	 */
	public function joinInfo()
	{
		$this->store->joinInfo();
		return $this;
	}

	/**
	 * Get lazy client info
	 * @return \Mreschke\Repository\Fake2\ClientInfo
	 */
	public function info()
	{
		if (isset($this->id) && !isset($this->info)) {
			$this->info = $this->fake2->clientInfo->where('clientID', $this->id)->first();
		}
		return @$this->info;
	}

}
