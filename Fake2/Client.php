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
     * Get the fake2 repository manager
     * @return \Mreschke\Repository\Fake2\Fake2
     */
    protected function fake2()
    {
        //Non inherited manager, which is fake2
        return $this->manager($inherited = false);
    }

    /**
     * Get the fake repository manager
     * @return \Mreschke\Repository\Fake\Fake
     */
    protected function fake()
    {
        // Inherited manager, which is fake
        return $this->manager();
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
