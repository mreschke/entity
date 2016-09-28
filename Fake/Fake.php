<?php namespace Mreschke\Repository\Fake;

use App;
use Mreschke\Repository\Manager;

/**
 * Fake Api and Repository Connection Manager
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
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
}
