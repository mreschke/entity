<?php namespace Mreschke\Repository\Fake2;

use App;

/**
 * Fake2 Client Info
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class ClientInfo extends Fake2Entity
{
    public $clientID;          // Actual db column
    public $region;            // Actual db column
    public $saleDate;          // Actual db column
}
