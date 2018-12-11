<?php declare(strict_types = 1);
/**
 * Vacation plugin for Roundcube.
 * 
 * PHP version 7
 *
 * @category Class
 * @package  Plugins
 * @uses     rcube_plugin
 * @author   Jasper Slits <jaspersl@gmail.com>
 * @author   Roman Plessl <roman@plessl.info>
 * @author   Andre Oliveira <me@andreoliveira.io>
 * @license  http://opensource.org/licenses/gpl-3.0 GNU/GPLv3
 * @link     https://github.com/yuusou/new-roundcube-vacation-plugin
 * @todo     See README.TXT
 */

/**
 * Driver bass class for available drivers.
 *
 * @category Class
 * @package  Plugins
 * @uses     rcube_plugin
 * @author   Jasper Slits <jaspersl@gmail.com>
 * @author   Roman Plessl <roman@plessl.info>
 * @author   Andre Oliveira <me@andreoliveira.io>
 * @license  http://opensource.org/licenses/gpl-3.0 GNU/GPLv3
 * @version  Release: 3
 * @link     https://github.com/yuusou/new-roundcube-vacation-plugin
 * @todo     See README.TXT
 */
abstract class Driver
{
    protected $error = false;
    protected $settings = [];
    
    /**
     * Class constructor
     * 
     * @param array $settings received from plugin initiator.
     * 
     * @return bool driver created correctly.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        try {
            $this->init();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Initialization procedure for driver.
     * 
     * @return void;
     */
    abstract protected function init();

    /**
     * Existing alias list.
     * 
     * @return mixed false or string.
     */
    abstract public function getAlias();

    /**
     * Existing vacation settings.
     * 
     * @return mixed false or array.
     */
    abstract public function getVacation();

    /**
     * Save vacation function for specific driver.
     * 
     * @param array $vacation contains user-defined paramaters.
     * 
     * @return void
     */
    abstract public function setVacation(array $vacation);
} ?>