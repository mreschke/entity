<?php namespace Mreschke\Repository\Fake;

use App;

/**
 * Fake Client
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
class Client extends FakeEntity
{
	public $id = 0;			  // Actual db column
	public $guid = null;      // Actual db column
	public $name = null;      // Actual db column
	public $extract = 0;      // Actual db column
	public $hostKey = null;   // Actual db column

	// Depreacted and moved to lazy for legacy use ->host->name etc...
	#public $hostname = null;  // Actual db column
	#public $company;          // Joined column (whereable, not savable)
	#public $companyGuid;      // Joined column (whereable, not savable)
	#public $server;           // Joined virtual column (not whereable, not savable)
	#public $serverNum;        // Joined column (whereable, not savable)

	public $addressID = null; // Actual db column
	public $created;          // Actual db column
	public $updated;          // Actual db column
	public $disabled = false; // Actual db column

}
