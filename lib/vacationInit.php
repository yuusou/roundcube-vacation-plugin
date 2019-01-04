<?php declare(strict_types = 1);
/**
 * Vacation plugin for Roundcube.
 * 
 * PHP version 7
 *
 * @category Class
 * @package  Plugins
 * @uses     rcube_plugin
 * @author   Andre Oliveira <me@andreoliveira.io>
 * @author   Roman Plessl <roman@plessl.info>
 * @author   Jasper Slits <jaspersl@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GNU/GPLv3
 * @link     https://github.com/yuusou/new-roundcube-vacation-plugin
 * @todo     See README.TXT
 */

/**
 * Checks configuration and initiates the vacation plugin.
 * Settings need to be set explicitly.
 *
 * @category Class
 * @package  Plugins
 * @uses     rcube_plugin
 * @author   Andre Oliveira <me@andreoliveira.io>
 * @author   Roman Plessl <roman@plessl.info>
 * @author   Jasper Slits <jaspersl@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GNU/GPLv3
 * @version  Release: 3
 * @link     https://github.com/yuusou/new-roundcube-vacation-plugin
 * @todo     See README.TXT
 */
class VacationInit
{
    private $_error = false;
    private $_driver;
    private $_settings = [];
    private $_sections = [];

    // Allowed options in config.ini per driver.
    private $_options = [
        "none" => [],
        "mysql" => [
            "dsn" => "required",
            "transportDomain" => "required",
            "selectQuery" => "required",
            "updateQuery" => "required",
            "domainIdQuery" => "", 
            "keepCopy" => "1", 
            "forward" => "0",
        ]
    ];


    /**
     * Class constructor.
     * 
     * @param string $email  current user.
     * @param string $domain current user's domain.
     * 
     * @todo better error handling for domain settings.
     */
    public function __construct(string $email, string $domain)
    {
        $this->_settings = array_merge(
            $this->_settings, [
                "email" => $email,
                "domain" => $domain
            ]
        );

        // Parse ini and set default settings.
        $settings = $this->_parseIni("default");
        if ($settings === false) {
            throw new Exception($this->_error);
        }
        $this->_settings = array_merge($this->_settings, $settings);


        // Parse ini and set domain-specific settings.
        $settings = $this->_parseIni($domain);
        if ($settings === false) {
            $this->_error = false;
            return;
        }
        $this->_settings = array_merge($this->_settings, $settings);
    }

    /**
     * Parser for Ini file.
     * 
     * @param string $section default or domain name.
     * 
     * @return mixed false for errors or settings array.
     */
    private function _parseIni(string $section)
    {
        $settings = [];

        $iniFile = "plugins/vacation/config.ini";
        if (!is_readable($iniFile)) {
            $this->_error = sprintf("%s is not readable.", $iniFile);
            return false;
        }

        $this->_sections = parse_ini_file($iniFile, true);
        if (!array_key_exists($section, $this->_sections)) {
            $this->_error = sprintf("Failed to parse [%s].", $section);
            return false;
        }
        
        $settings = $this->_sections[$section];
        if (!array_key_exists($settings["driver"], $this->_options)) {
            $this->_error = sprintf("%s isn't a valid driver.", $settings["driver"]);
            return false;
        }

        $driver = $settings["driver"];
        foreach ($this->_options[$driver] as $key => $value) {
            if ($value == "required" && empty($settings[$key])) {
                $this->_error = sprintf("%s missing in [%s].", $key, $section);
                return false;
            } elseif ($value !== "required" && empty($settings[$key])) {
                $settings = array_merge($settings, [$key => $value]);
            }
        }

        return $settings;
    }

    /**
     * Checks for "none" driver.
     * 
     * @return bool false for "none".
     */
    public function hasVacation()
    {
        return ($this->_settings["driver"] !== "none");
    }

    /**
     * Get settings array.
     * 
     * @return object driver.
     */
    public function getDriver()
    {
        $driver = $this->_settings["driver"];
        $driverFile = sprintf("plugins/vacation/lib/%sDriver.php", $driver);

        if (!is_readable($driverFile)) {
            $this->_error = sprintf("%s is not readable.", $driverFile);
            return false;
        }

        include $driverFile;

        try {
            $driver = ucfirst($driver);
            $this->_driver = new $driver($this->_settings);
        } catch (Exception $e) {
            throw $e;
        }

        return $this->_driver;
    }
} ?>