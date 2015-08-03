<?php namespace Mreschke\Repository\Fake2;

use App;
use Mreschke\Repository\Manager;

/**
 * Fake2 Api and Repository Connection Manager
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Fake2 extends Manager
{
	/**
	 * Base namespace
	 * @var string
	 */
	protected $namespace = 'Mreschke\\Repository\\Fake2';

	/**
	 * Base config key
	 * @var string
	 */
	protected $configKey = 'mreschke.repository.fake2';

}
