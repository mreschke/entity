<?php namespace Mreschke\Repository\Fake;

use App;
use Mreschke\Repository\Manager;

/**
 * Fake Api and Repository Connection Manager
 * @copyright Dynatron Software, Inc.
 * @author Matthew Reschke <mreschke@dynatronsoftware.com>
 */
class Fake extends Manager
{
	/**
	 * Base namespace
	 * @var string
	 */
	protected $namespace = 'Mreschke\\Repository\\Fake';

	/**
	 * Base config key
	 * @var string
	 */
	protected $configKey = 'mreschke.repository.fake';

	/**
	 * The array of resolvable entities by entityKey
	 * @var array
	 */
	protected $resolvable = [
		'address',
		'client',
	];

}
