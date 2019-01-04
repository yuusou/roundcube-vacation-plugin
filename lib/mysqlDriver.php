<?php declare (strict_types = 1);
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
class Mysql extends Driver
{
    private $_dsn = [];
    private $_db;

    /**
     * Initialization procedure for driver.
     * 
     * @return void
     */
    protected function init()
    {
        $this->_dsn = rcube_db::parse_dsn($this->settings["dsn"]);
        $this->_db = rcube_db::factory($this->settings["dsn"], "", false);

        $this->_db->db_connect("w");
        if ($this->error = $this->_db->is_error()) {
            throw new Exception($this->error);
        }

        $this->settings["goto"] = sprintf("%s@%s", preg_replace("/(@(?!.*@))/", "#", $this->settings["email"]), $this->settings["transportDomain"]);

        if ($this->settings["domainIdQuery"] !== "") {
            $query = $this->_parse($this->settings["domainIdQuery"]);
            $result = $this->_db->query($query);
            if ($row = $this->_db->fetch_array($result)) {
                $this->settings["domainId"] = $row[0];
            } else {
                $this->settings["domainId"] = $this->settings["domain"];
            }
        }
    }

    /**
     * Existing alias list.
     * 
     * @return string alias;
     */
    public function getAlias()
    {
        $alias = "";

        $query = $this->_parse($this->settings["selectQuery"]);
        $result = $this->_db->query($query);
        if ($this->error = $this->_db->is_error()) {
            throw new Exception($this->error);
        }

        if ($row = $this->_db->fetch_assoc($result)) {
            $alias = $row[0];

            $alias = str_replace(" ", "", $alias);
            $alias = substr($alias, strrpos(($this->settings["goto"] . ","), "@") + 1);
            $alias = substr($alias, strrpos(($this->settings["email"] . ","), "@") + 1);
        }

        return $alias;
    }

    /**
     * Existing vacation settings.
     * 
     * @return array vacation settings;
     */
    public function getVacation()
    {
        $vacation = [
            "subject" => "", 
            "body" => "", 
            "activeFrom" => "", 
            "activeUntil" => "", 
            "active" => ""
        ];

        $query = "SELECT subject, body, activefrom AS activeFrom, activeuntil AS activeUntil, active FROM vacation WHERE email=?";
        $result = $this->_db->query(
            $query,
            $this->settings["email"]
        );
        if ($this->error = $this->_db->is_error()) {
            throw new Exception($this->error);
        }

        if ($row = $this->_db->fetch_assoc($result)) {
            $vacation = array_merge($vacation, $row);
            ($vacation["active"] !== "0") ?: $vacation["active"] = null;
        }

        return $vacation;
    }

    /**
     * Save vacation function for specific driver.
     * 
     * @param array $vacation contains user-defined paramaters.
     * 
     * @return void
     */
    public function setVacation(array $vacation)
    {
        $query = "UPDATE vacation SET subject=?, body=?, activefrom=?, activeuntil=?, domain=?, modified=NOW(), active=? WHERE email=?";
        $this->_db->query(
            $query,
            rcube_db::escape($vacation["subject"]),
            rcube_db::escape($vacation["body"]),
            rcube_db::escape($vacation["activeFrom"]),
            rcube_db::escape($vacation["activeUntil"]),
            $this->settings["domain"],
            $vacation["active"],
            $this->settings["email"]
        );
        if ($this->error = $this->_db->is_error()) {
            throw new Exception($this->error);
        }

        if ($this->_db->affected_rows() !== 1) {
            $query = "INSERT INTO vacation VALUES (?, ?, ?, ?, ?, '', ?, 0, NOW(), NOW(), ?";
            $this->_db->query(
                $query,
                $this->settings["email"],
                rcube_db::escape($vacation["subject"]),
                rcube_db::escape($vacation["body"]),
                rcube_db::escape($vacation["activeFrom"]),
                rcube_db::escape($vacation["activeUntil"]),
                $this->settings["domain"],
                $vacation["active"]
            );
            if ($this->error = $this->_db->is_error()) {
                throw new Exception($this->error);
            }
        }

        /*
         * %f = %e (not on vacation)
         *      %g (keepCopy = "0")
         *      %g,%e (keepCopy = "1")
         *      %g,forwards (forward = "1")
         *      %g,%e,forwards (keepCopy = "1" and forward = "1")
         *      %e,forwards not possible (when not on vacation, forwards ignored).
         */
        if ($vacation["active"] === "0") {
            $this->settings["forward"] = $this->settings["email"];
        } else {
            $forward = $this->settings["goto"];

            if ($this->settings["keepCopy"] === "1") {
                $forward = sprintf("%s, %s", $forward, $this->settings["email"]);
            }

            if ($this->settings["forward"] !== "0") {
                $forward = sprintf("%s, %s", $forward, $vacation["forward"]);
            }

            $this->settings["forward"] = $forward;
        }

        $query = $this->_parse($this->settings["updateQuery"]);
        $this->_db->query($query);
        if ($this->error = $this->_db->is_error()) {
            throw new Exception($this->error);
        }
    }

    /**
     * Replaces config query strings filled in.
     * 
     * @param string $query query from config file.
     * 
     * @return string SQL query with substituted parameters
     */
    private function _parse(string $query)
    {
        return str_replace(
            ["%m", "%e", "%d", "%i", "%g", "%f"],
            [
                $this->_dsn["database"],
                $this->settings["email"],
                $this->settings["domain"],
                $this->settings["domainId"],
                $this->settings["goto"],
                $this->settings["forward"]
            ],
            $query
        );
    }
} ?>