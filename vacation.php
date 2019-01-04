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

 // Load required dependencies
require "lib/driver.php";
require "lib/vacationInit.php";

/**
 * Vacation plugin that adds a new tab to the settings section
 * to enable forward / out of office replies.
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
class Vacation extends rcube_plugin
{
    private $_username = "";
    private $_email = "";
    private $_domain = "";
    private $_vacation = [];
    private $_vacationInit;
    private $_driver;

    /**
     * Initialization method.
     * 
     * @return bool false if no vacation for this user.
     * 
     * @todo Localization.
     * @todo better way to fetch domain if missing from username.
     */
    public function init()
    {
        $rcmail = rcmail::get_instance();

        $this->load_config();
        $this->add_texts("localization/");
        $this->_username = $rcmail->user->get_username();

        // Set email and domain.
        if (filter_var($this->_username, FILTER_VALIDATE_EMAIL)) {
            $this->_domain = substr($this->_username, strrpos($this->_username, "@") + 1);
            $this->_email = $this->_username;
        } else {
            $this->_domain = $_SERVER["HTTP_HOST"];
            $this->_email = sprintf("%s@%s", $this->_username, $this->_domain);
        }

        // Initiate vacation.
        try {
            $this->_vacationInit = (new vacationInit($this->_email, $this->_domain));
        } catch (Exception $e) {
            $this->_raiseError($e->getMessage());
        }

        // Initiate driver for current domain.
        if (!$this->_vacationInit->hasVacation()) {
            return false;
        } else {
            try {
                $this->_driver = $this->_vacationInit->getDriver();
            } catch (Exception $e) {
                $this->_raiseError($e->getMessage());
            }
        }

        $this->_vacation = $this->_driver->getVacation();
        

        $this->include_script("https://cdn.jsdelivr.net/npm/flatpickr@latest/dist/flatpickr.js");
        $this->include_script("https://cdn.jsdelivr.net/npm/flatpickr@latest/dist/plugins/rangePlugin.js");
        $this->include_script("vacation.js");
        $this->include_stylesheet("skins/default/vacation.css");

        $this->register_action("plugin.vacation", [$this, "vacationInit"]);
        $this->register_action("plugin.vacationSave", [$this, "vacationSave"]);
        $this->register_action("plugin.vacationAliases", [$this->_driver, "getVacationAliases"]);
        $this->register_handler("plugin.vacationForm", [$this, "vacationForm"]);
    }

    /**
     * Page initialization.
     * 
     * @return void
     */
    public function vacationInit()
    {
        $rcmail = rcmail::get_instance();

        $rcmail->output->set_pagetitle($this->gettext("vacation"));
        $rcmail->output->send("vacation.vacation");
    }

    /**
     * Save vacation.
     * 
     * @return void
     */
    public function vacationSave()
    {
        $rcmail = rcmail::get_instance();

        $this->_vacation["subject"] = rcube_utils::get_input_value("_vacationSubject", rcube_utils::INPUT_POST, true);
        $this->_vacation["body"] = rcube_utils::get_input_value("_vacationBody", rcube_utils::INPUT_POST, true);
        $this->_vacation["activeFrom"] = rcube_utils::get_input_value("_vacationActiveFrom", rcube_utils::INPUT_POST, true);
        $this->_vacation["activeUntil"] = rcube_utils::get_input_value("_vacationActiveUntil", rcube_utils::INPUT_POST, true);
        $this->_vacation["active"] = ((null != rcube_utils::get_input_value("_vacationActive", rcube_utils::INPUT_POST)) ? "1" : "0");

        try {
            $this->_driver->setVacation($this->_vacation);
            $rcmail->output->show_message($this->gettext("success_changed"));
        } catch (Exception $e) {
            //$rcmail->output->show_message($this->gettext("failed"), "error");
            $rcmail->output->show_message($e->getMessage());
        }

        $this->vacationInit();
    }

    /**
     * Save vacation.
     * 
     * @return void
     */
    public function vacationForm()
    {
        $out = "";
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_env("product_name", $rcmail->config->get("product_name"));

        // Load default body & subject if present.
        if (empty($this->_vacation["subject"])) {
            $this->_vacation["subject"] = $this->gettext("defaultSubject");
        }

        if (empty($this->_vacation["body"])) {
            $this->_vacation["body"] = $this->gettext("defaultBody");
        }

        // return the complete edit form as table

        $out .= "<style>.uibox{overflow-y:scroll;}</style>";
        $out .= "<fieldset><legend>" . $this->gettext("outofoffice") . " ::: " . $this->_username . "</legend>" . "\n";
        $out .= "<table class='propform'><tbody>";
        // show autoresponder properties

        // Auto-reply enabled
        $fieldId = "vacationActive";
        $inputActive = new html_checkbox(
            ["name" => "_" . $fieldId, "id" => $fieldId, "value" => 1]
        );
        $out .= sprintf(
            "<tr><td class=title'><label for='%s'>%s</label></td><td>%s</td></tr>\n",
            $fieldId,
            rcube_utils::rep_specialchars_output($this->gettext("active")),
            $inputActive->show($this->_vacation["active"])
        );

        // Subject
        $fieldId = "vacationSubject";
        $inputSubject = new html_inputfield(["name" => "_" . $fieldId, "id" => $fieldId, "size" => 90]);
        $out .= sprintf(
            "<tr><td class=title'><label for='%s'>%s</label></td><td>%s</td></tr>\n",
            $fieldId,
            rcube_utils::rep_specialchars_output($this->gettext("subject")),
            $inputSubject->show($this->_vacation["subject"])
        );

        // Date active from
        $fieldId = "vacationActiveFrom";
        $inputActiveFrom = new html_inputfield(["name" => "_" . $fieldId, "id" => $fieldId, "size" => 45]);
        $out .= sprintf(
            "<tr><td class=title'><label for='%s'>%s</label></td><td>%s</td></tr>\n",
            $fieldId,
            rcube_utils::rep_specialchars_output($this->gettext("activeFrom")),
            $inputActiveFrom->show($settings["activeFrom"])
        );

        // Date active until
        $fieldId = "vacationActiveUntil";
        $inputActiveUntil = new html_inputfield(
            ["name" => "_" . $fieldId, "id" => $fieldId, "size" => 45]
        );
        $out .= sprintf(
            "<tr><td class=title'><label for='%s'>%s</label></td><td>%s</td></tr>\n",
            $fieldId,
            rcube_utils::rep_specialchars_output($this->gettext("activeUntil")),
            $inputActiveUntil->show($this->_vacation["activeUntil"])
        );

        // Out of office body
        $fieldId = "vacationBody";
        $inputBody = new html_textarea(
            ["name" => "_" . $fieldId, "id" => $fieldId, "cols" => 88, "rows" => 20]
        );
        $out .= sprintf(
            "<tr><td class=title'><label for='%s'>%s</label></td><td>%s</td></tr>\n",
            $fieldId,
            rcube_utils::rep_specialchars_output($this->gettext("body")),
            $inputBody->show($this->_vacation["body"])
        );

        /* We only use aliases for .forward and only if it"s enabled in the config*/
        if ($this->v->useAliases()) {
            $size = 75;

            // If there are no multiple identities, hide the button and add increase the size of the textfield
            $hasMultipleIdentities = $this->v->vacation_aliases("buttoncheck");
            (!$hasMultipleIdentities == "") ?: $size += 15;

            $fieldId = "vacationAliases";
            $inputAlias = new html_inputfield(
                ["name" => "_" . $fieldId, "id" => $fieldId, "size" => $size]
            );
            $out .= "<tr><td class=\"title\">" . $this->gettext("separate_alias") . "</td></tr>";

            // Inputfield with button
            $out .= sprintf(
                "<tr><td class=title'><label for='%s'>%s</label></td><td>%s",
                $fieldId,
                rcube_utils::rep_specialchars_output($this->gettext("alias")),
                $inputAlias->show($this->_driver->getAlias())
            );
            if ($hasMultipleIdentities != "") {
                $out .= sprintf(
                    "<input type='button' id='aliaslink' class='button' value='%s'/>",
                    rcube_utils::rep_specialchars_output($this->gettext("aliasesbutton"))
                );
            }
            $out .= "</td></tr>";

        }
        $out .= "</tbody></table>" . PHP_EOL . "</fieldset>";

        $out .= "<fieldset><legend>" . $this->gettext("forward") . "</legend>";
        $out .= "<table class='propform'><tbody>";

        // Information on the forward in a seperate fieldset.
        if (!isset($this->inicfg["disable_forward"]) || (isset($this->inicfg["disable_forward"]) && $this->inicfg["disable_forward"] == false)) {
            // Forward mail to another account
            $fieldId = "vacation_forward";
            $input_autoresponderforward = new html_inputfield(["name" => "_vacation_forward", "id" => $fieldId, "size" => 90]);
            $out .= sprintf(
                "<tr><td class='title'><label for='%s'>%s</label></td><td>%s<br/>%s</td></tr>\n",
                $fieldId,
                rcube_utils::rep_specialchars_output($this->gettext("forwardingaddresses")),
                $input_autoresponderforward->show($settings["forward"]),
                $this->gettext("separate_forward")
            );

        }
        $out .= "</tbody></table></fieldset>\n";

        $rcmail->output->add_gui_object("vacationForm", "vacationForm");
        return $out;
    }

    /**
     * Display error message.
     * 
     * @param string $error message to be displayed.
     * 
     * @return void
     */
    private function _raiseError($error)
    {
        error_log($error);

        rcube::raise_error(
            [
                "code" => 601,
                "type" => "php",
                "file" => __FILE__,
                "message" => $error
            ], true, true
        );
    }
} ?>