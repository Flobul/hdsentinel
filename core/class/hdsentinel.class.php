<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class hdsentinel extends eqLogic
{
    public static $_hdsentinelVersion = '0.90';
    public static $_widgetPossibility = array('custom' => true);

    public static function getApiXmlResult($_xml, $_ip)
    {
        /**
         * Charge le fichier de configuration des commandes
         *
         * @param			$_xml     string     Tableau venant de HDSentinel en XML
         * @param			$_ip      string     Adresse IP
         * @return			          array      Tableau des commandes
         */
        log::add(__CLASS__, 'debug', 'Début getApiXmlResult'. json_encode($_xml['General_Information']));

        $array = array();
        if (isset($_xml['General_Information'])) {
            $array['name'] = $_xml['General_Information']['Computer_Information']['Computer_Name'];
            $array['logicalId'] = $_xml['General_Information']['Computer_Information']['MAC_Address'];
            $array['configuration']['addressip'] = $_ip;
            $_xml['General_Information']['Application_Information']['Current_Date_And_Time'] = self::convertCurrentDateAndTime($_xml['General_Information']['Application_Information']['Current_Date_And_Time']);
            if (!isset($_xml['General_Information']['Computer_Information']['Uptime'])) {
                $_xml['General_Information']['Computer_Information']['Uptime'] = $_xml['General_Information']['Computer_Information']['System_Uptime'];
            }

            $array['configuration'] = array_merge($_xml['General_Information']['Computer_Information'], $_xml['General_Information']['Application_Information'], $_xml['General_Information']['System_Information']);
        }
        log::add(__CLASS__, 'debug', 'Début equipement');
        $eqLogic = self::searchEqLogic($array['logicalId'], $_ip);
        if (!is_object($eqLogic)) {
            log::add('hdsentinel', 'info', 'Creation hdsentinel : '.$array['logicalId']);
            $eqLogic = new hdsentinel();
            $eqLogic->setEqType_name('hdsentinel');
            $eqLogic->setIsEnable(1);
        }
        log::add(__CLASS__, 'debug', 'Début a2o' .print_r($array,true));
        utils::a2o($eqLogic, $array);
        log::add(__CLASS__, 'debug', 'Fin a2o');

        try {
            $eqLogic->save();
        } catch(Exception $e) {
            $eqLogic->setName($eqLogic->getName().' '.config::genKey(3));
            $eqLogic->save();
        }
        $disk = array();
        for ($i = 0; $i <= 10; $i++) {
            if (array_key_exists('Physical_Disk_Information_Disk_'.$i, $_xml)) {
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Number'])) {
                    $disk[$i]['Hard_Disk_Number'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Number'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Device'])) {
                    $disk[$i]['Hard_Disk_Device'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Device'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Serial_Number'])) {
                    $disk[$i]['Hard_Disk_Serial_Number'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Hard_Disk_Serial_Number'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Total_Size'])) {
                    $disk[$i]['Total_Size'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Total_Size'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Current_Temperature'])) {
                    $disk[$i]['Current_Temperature'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Current_Temperature'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Maximum_temperature_during_entire_lifespan'])) {
                    $disk[$i]['Maximum_temperature_during_entire_lifespan'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Maximum_temperature_during_entire_lifespan'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Power_on_time'])) {
                    $disk[$i]['Power_on_time'] = self::translatePowerOnTime($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Power_on_time']);
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Estimated_remaining_lifetime'])) {
                    $disk[$i]['Estimated_remaining_lifetime'] = self::translateEstimatedRemainingLifetime($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Estimated_remaining_lifetime']);
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Health'])) {
                    $disk[$i]['Health'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Health'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Performance'])) {
                    $disk[$i]['Performance'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Performance'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Description'])) {
                    $disk[$i]['Description'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Description'];
                }
                if (isset($_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Lifetime_writes'])) {
                    $disk[$i]['Lifetime_writes'] = $_xml['Physical_Disk_Information_Disk_'.$i]['Hard_Disk_Summary']['Lifetime_writes'];
                }
            }
        }

        log::add(__CLASS__, 'debug', 'Début commandes');
        $all_cmds = self::loadCmdFromConf();
        foreach ($disk as $nb => $summaries) {
            foreach ($summaries as $summary => $value) {
                if ($value != '' && $value != '?' && !preg_match('/^Unknown/', $value)) {
                    $cmd = $eqLogic->searchCmd($summary . " " . $summaries['Hard_Disk_Number'], $summary . " " . $summaries['Hard_Disk_Number']);
                    if (!is_object($cmd)) {
                        if (isset($all_cmds[$summary])) {
                            $eqLogic->createCmdsFromConfig($all_cmds[$summary], $summaries['Hard_Disk_Number']);
                        }
                    } else {
                        if ($cmd->getSubType() == 'numeric') {
                            $split = explode(' ', $value);
                            $value = $split[0];
                            $unite = $split[1];
                            if ($unite != '') {
                                $cmd->setUnite($unite)->save();
                            }
                        }
                        log::add(__CLASS__, 'debug', 'Mise à jour de la commande de ' . $eqLogic->getName() . ' : ' .  $summary . ' - value: ' . $value . ' - unite: ' . $unite);
                        $cmd->event($value);
                    }
                }
            }
        }
    }

    public static function translatePowerOnTime($_string)
    {
        /**
         * Traduit le temps sous tension en français
         *
         * @param			$_string      string       Valeur en anglais
         * @return			              string       Valeur en français
         */
        $arrEng = array('days','day','hours','hour');
        $arrFra = array(__('jours', __FILE__),__('jour', __FILE__),__('heures', __FILE__),__('heure', __FILE__));
        return str_replace($arrEng, $arrFra, $_string);
    }

    public static function translateEstimatedRemainingLifetime($_string)
    {
        /**
         * Traduit le temps restant estimé en français
         *
         * @param			$_string      string       Valeur en anglais
         * @return			              string       Valeur en français
         */
        $arrEng = array('more than','days','hours','hour');
        $arrFra = array(__('plus de', __FILE__),__('jours', __FILE__),__('heures', __FILE__),__('heure', __FILE__));
        return str_replace($arrEng, $arrFra, $_string);
    }

    public static function convertCurrentDateAndTime($_string)
    {
        /**
         * Converti le format de date et heure du dernier rapport en temps conventionel jeedom
         *
         * @param			$_string      string       Temps reçu
         * @return			              string       Temps converti
         */
        $datetime = DateTime::createFromFormat("d-n-y H:i:s", $_string);
        if($datetime === false) {
            $datetime = DateTime::createFromFormat("d/m/Y H:i:s", $_string);
        }
        return $datetime->format('Y-m-d H:i:s');
    }

    public static function cronHourly()
    {
        /**
         * Cron démarré toutes les heures par jeedom
         * Récupère les logs distants et si démon auto redémarre le cron si inactif
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			|*Cette fonction ne retourne pas de valeur*|
         */
        foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
            if ($eqLogic->getConfiguration('windows', false)) {

                $url = 'http://'.$eqLogic->getConfiguration('addressip').':'. $eqLogic->getConfiguration('portssh').'/xml';
                $request_http = new com_http($url, ' ', $eqLogic->getConfiguration('password'));
                $result = $request_http->exec();

                $start = strpos($result,"<?xml");
                $end = '</Hard_Disk_Sentinel>';

                $result = substr($result, $start);
                $result = substr($result, 0, strpos($result,$end) + strlen($end));

                try {
                    $xml_action = new SimpleXMLElement($result);
                    $result = json_decode(json_encode($xml_action), true);
                    self::getApiXmlResult($result, $eqLogic->getConfiguration('addressip'));
                } catch (Exception $e) {
                    log::add(__CLASS__, 'info', __('Erreur XML', __FILE__));
                }

            } else {
                /*if ($eqLogic->getConfiguration('remoteDaemonAuto', '0') == 1) {
                    log::add(__CLASS__, 'info', 'Redémarrage cron remote ' . $eqLogic->getName());
                    $eqLogic->launchCron($eqLogic->getId());
                }
                $eqLogic->getLog();*/
            }
        }
    }

    public static function searchEqLogic($_logicalId, $_ip)
    {
        /**
         * Trouve la commande associée au logicalId ou au nom donné
         *
         * @param			$_logicalId		string		LogicalId de commande à trouver
         * @param			$_name			string		Nom de commande à trouver
         * @return			$cmd			object		Commande trouvée
         */
        $return = null;
        foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
            if ($eqLogic->getLogicalId() == $_logicalId || $eqLogic->getConfiguration('addressip') == $_ip) {
                $return = $eqLogic;
                break;
            }
        }
        return $return;
    }

    private static function loadCmdFromConf()
    {
        /**
         * Charge le fichier de configuration des commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			      array      Tableau des commandes
         */
        if (!is_file(dirname(__FILE__) . '/../../core/config/all_cmds.json')) {
            log::add(__CLASS__, 'debug', 'Fichier introuvable : all_cmds.json');
            return;
        }
        $content = file_get_contents(dirname(__FILE__) . '/../../core/config/all_cmds.json');
        if (!is_json($content)) {
            log::add(__CLASS__, 'debug', 'JSON invalide : all_cmds.json');
            return;
        }
        return json_decode($content, true);
    }

    private static function translate($word)
    {
        /**
         * Traduction des informations. (plus utilisé car langue selectionnée dans la requete)
         *
         * @param	$word		string		Mot en anglais.
         * @return	$word		string		Mot en Français (ou anglais, si traduction inexistante).
         */

        $translate = array(
            'Hard_Disk_Number' => __("Numérotation du disque", __FILE__),
            'Hard_Disk_Device' => __("Emplacement du disque", __FILE__),
            'Hard_Disk_Serial_Number' => __("Numéro de série du disque", __FILE__),
            'Total_Size' => __("Taille totale", __FILE__),
            'Current_Temperature' => __("Température actuelle", __FILE__),
            'Maximum_temperature_during_entire_lifespan' => __("Température maximale atteinte", __FILE__),
            'Power_on_time' => __("Temps sous tension", __FILE__),
            'Estimated_remaining_lifetime' => __("Durée de vie estimée", __FILE__),
            'Health' => __("Santé", __FILE__),
            'Lifetime_writes' => __("Écriture totale", __FILE__),
            'Performance' => __("Performance", __FILE__),
            'Description' => __("Déscription", __FILE__),
        );
        (array_key_exists($word, $translate)) ? $word = $translate[$word] : null;
        return $word;
    }

    public static function getApiHtmlResult($_html, $_ip)
    {
        /**
         * Crée le fichier html pour la page
         *
         * @param			$_html    string     Html du résultat
         * @param			$_ip      string     Adresse IP
         * @return			          array      Tableau des commandes
         */
        $data_path = dirname(__FILE__) . '/../../core/data';
        $eqLogic = self::searchEqLogic('', $_ip);
        if (is_object($eqLogic)) {
            file_put_contents($data_path . '/hdsentinel_'.$eqLogic->getId().'.html', $_html);
            return true;
        }
        return false;
    }

    public function preSave()
    {
        if ($this->getConfiguration('manually', false)) {
            $cmdRefresh = $this->getCmd('action', 'refresh');
            if (!is_object($cmdRefresh)) {
                log::add(__CLASS__, 'debug', 'Créaction de la commande Rafraîchir');
                $cmdRefresh = new hdsentinelCmd();
                $cmdRefresh->setLogicalId('refresh');
                $cmdRefresh->setEqLogic_id($this->getId());
                $cmdRefresh->setName(__('Rafraîchir',__FILE__));
                $cmdRefresh->setIsVisible(true);
                $cmdRefresh->setType('action');
                $cmdRefresh->setSubType('other');
                $cmdRefresh->setGeneric_type('DONT');
                $cmdRefresh->save();
            }
        }
    }

    public function getHtmlDisksFullResult()
    {
        $plugin = plugin::byId('hdsentinel');
        $cmd = $this->getSudoCmd();
        $cmd .='bash /home/' . $this->getConfiguration('user') . '/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh';
        $cmd .= ' -a ' . jeedom::getApiKey($plugin->getId());
        $cmd .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
        $cmd .= ' -o html';
        return $this->sendSshCmd([$cmd]);
    }

    public function createCmdsFromConfig($_cmd, $_diskNb)
    {
        /**
         * Crée la/les commande/s fournies par le fichier de conf et l'info qui arrive du disque
         *
         * @param			$_cmd       array       Array de commande
         * @param			$_diskNb    string      Numéro du disque pour saisie de logicalId et Nom
         * @return			|*Cette fonction ne retourne pas de valeur*|
         */
        foreach ($_cmd as $cmdDef) {
            $cmd = $this->getCmd('info', $cmdDef['logicalId'] . " " . $_diskNb);
            if (!is_object($cmd)) {
                log::add(__CLASS__, 'debug', 'Création : ' . $cmdDef["logicalId"] . ' - nom : ' . $cmdDef['name']);
                $cmd = new hdsentinelCmd();
                $cmd->setLogicalId($cmdDef['logicalId'] . " " . $_diskNb);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName($cmdDef['name'] . " " . $_diskNb);
                if (isset($cmdDef['isHistorized'])) {
                    $cmd->setIsHistorized($cmdDef["isHistorized"]);
                }
                if (isset($cmdDef['isVisible'])) {
                    $cmd->setIsVisible($cmdDef['isVisible']);
                }
                if (isset($cmdDef['template'])) {
                    foreach ($cmdDef['template'] as $key => $value) {
                        $cmd->setTemplate($key, $value);
                    }
                }
                $cmd->setType($cmdDef["type"]);
                $cmd->setSubType($cmdDef["subtype"]);
                if (isset($cmdDef["generic_type"])) {
                    $cmd->setGeneric_type($cmdDef["generic_type"]);
                }
                if (isset($cmdDef["unite"])) {
                    $cmd->setUnite($cmdDef["unite"]);
                }
                if (isset($cmdDef['configuration'])) {
                    foreach ($cmdDef['configuration'] as $key => $value) {
                        $cmd->setConfiguration($key, $value);
                    }
                }
                $cmd->save();
            }
        }
    }

    public function getNbDisksByEqLogic()
    {
        $i = 0;
        foreach ($this->getCmd('info') as $allCmd) {
            if (preg_match('/^Health$|^Health \d{1,2}$/', $allCmd->getLogicalId())) {
                $i++;
            }
        }
        return $i;
    }

    public function getImage()
    {
        /**
         * Renvoie l'url de l'image à partir de l'objet
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return		string		Url de l'image
         */
        $path = 'plugins/hdsentinel/core/config/images/';
        $files = ls(__DIR__ . '/../../../../'. $path, '*.png', false, array('files', 'quiet'));
        $return = "";
        foreach ($files as $file) {
            if (!preg_match('/'.strtr($file, array('.png' => '')).'/', $this->getConfiguration('groupName'))) {
                continue;
            }
            if (!preg_match('/'.strtr($file, array('.png' => '')).'/', $this->getName())) {
                continue;
            }
            $return = $path . rawurlencode($file);
        }
        return $return;
    }

    public function searchCmd($_logicalId, $_name)
    {
        /**
         * Trouve la commande associée au logicalId ou au nom donné
         *
         * @param			$_logicalId		string		LogicalId de commande à trouver
         * @param			$_name			string		Nom de commande à trouver
         * @return			$cmd			object		Commande trouvée
         */
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
            if ($liste_cmd->getLogicalId() == $_logicalId || $liste_cmd->getName() == $_name) {
                $cmd = $liste_cmd;
                break;
            }
        }
        return $cmd;
    }

    public function createCron()
    {
        /**
         * Crée le cron distant dans crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Retour de la commande
         */
        log::add(__CLASS__, 'info', __('Début création du cron distant', __FILE__));
        $plugin = plugin::byId('hdsentinel');
        $return = false;
        //$cmd1 = $this->getSudoCmd() . 'touch /etc/cron.daily/hdsentinel; echo $?';
        //$cmd2 = $this->getSudoCmd();
        $cmd2 = '';
        if ($this->getConfiguration('user') != 'root') {
            $cmd2 .= 'echo ' . $this->getConfiguration('password') . ' | su -c \'';
        }
        $cmd2 .= 'echo "' . $this->getConfiguration('autorefresh', '03 00 * * *') . ' ' . $this->getSudoCmd() . ' bash /home/' . $this->getConfiguration('user') . '/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh';
        $cmd2 .= ' -a ' . jeedom::getApiKey($plugin->getId());
        $cmd2 .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
        $cmd2 .= ' -o xml';
        $cmd2 .= ' >> /tmp/hdsentinel_log 2>&1 &"';
        $cmd2 .= ' > /etc/cron.daily/hdsentinel; echo $?;';
        if ($this->getConfiguration('user') != 'root') {
            $cmd2 .= '\'';
        }
        $return = $this->sendSshCmd([$cmd2]);
        $cmdLog = str_replace($this->getConfiguration('password'),'PASSWORD',$cmd2);
        $cmdLog = str_replace(jeedom::getApiKey($plugin->getId()),'APIKEY',$cmdLog);
        log::add(__CLASS__, 'info', __('Fin création du cron distant cmd1: ', __FILE__) . ' + cmd: ' . $cmdLog . ' = ' . $return);
        return $return;
    }

    public function getSudoCmd()
    {
        /**
         * Si non root, demande les privilège avant une commande
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Commande pour élever les privilèges de la commande à envoyer
         */
        $cmd = '';
        if ($this->getConfiguration('user') != 'root') {
            $cmd .= 'echo "' . $this->getConfiguration('password') . '" | sudo -S ';
        }
        return $cmd;
    }

    public function launchCron()
    {
        /**
         * Crée le cron distant dans crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Retour de la commande
         */
        log::add(__CLASS__, 'info', __('Début lancement du cron distant', __FILE__));

        $plugin = plugin::byId('hdsentinel');
        if (!$this->sendSshCmd(['ls /etc/cron.daily/hdsentinel | wc -l'])) {
            $this->sendSshCmd([ $this->getSudoCmd() . 'mkdir -p /etc/cron.daily/;']);
            log::add(__CLASS__, 'info', __('Création du cron distant ', __FILE__));

            if ($this->sendSshCmd(['ls /etc/synoinfo.conf | wc -l'])) {
                log::add(__CLASS__, 'info', __('Création du cron distant pour synology ', __FILE__));
                //$cmd = $this->getSudoCmd() . 'echo "* * * * * echo fuzuri | sudo -S  bash /home/'.$this->getConfiguration('user').'/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh -a APIKEY -i 192.168.0.29 -o xml >> /tmp/hdsentinel_log 2>&1 &" > /tmp/hdsentinel_cron';
                $cmd2 .= $this->getSudoCmd() . 'echo "' . $this->getConfiguration('autorefresh', '03 00 * * *') . ' ' . $this->getSudoCmd() . ' bash /home/' . $this->getConfiguration('user') . '/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh';
                $cmd2 .= ' -a ' . jeedom::getApiKey($plugin->getId());
                $cmd2 .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
                $cmd2 .= ' -o xml';
                $cmd2 .= ' >> /tmp/hdsentinel_log 2>&1 &"';
                $cmd2 .= ' > /tmp/hdsentinel_cron; '.$this->getSudoCmd().'mv /tmp/hdsentinel_cron /etc/cron.daily/hdsentinel; echo $?;';

                $this->sendSshCmd([$cmd2]);
            } else {
                $this->createCron();
            }
        }
        $cmd3 = '[ -f "/opt/bin/crontab" ] && (' . $this->getSudoCmd() . '/opt/bin/crontab -l | grep hdsentinel_to_jeedom_pub | wc -l) || (' . $this->getSudoCmd() . '/usr/bin/crontab -l | grep hdsentinel_to_jeedom_pub | wc -l)';

        if (!$this->sendSshCmd([$cmd3])) {
            log::add(__CLASS__, 'info', __('Lancement du cron distant', __FILE__));
            $cmd = '([ -f "/opt/bin/crontab" ] && (' . $this->getSudoCmd() . '/opt/bin/crontab /etc/cron.daily/hdsentinel) && echo $?) || ';
            $cmd .= '([ -f "/usr/bin/crontab" ] && (' . $this->getSudoCmd() . '/usr/bin/crontab /etc/cron.daily/hdsentinel) && echo $?)';
            return $this->sendSshCmd([$cmd]);
        }
        return false;
    }

    public function removeCron()
    {
        /**
         * Arrête le cron distant dans crontab (et supprime le fichier pour le relancer)
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Suppression du cron distant', __FILE__));
        $cmd1 = '[ -f "/opt/bin/crontab" ] && ((' . $this->getSudoCmd() . '/opt/bin/crontab -u root -l | grep -v "hdsentinel_to_jeedom_pub" | sudo -S /opt/bin/crontab -); echo $?) || ';
        $cmd1 .= '([ -f "/usr/bin/crontab" ] && (' . $this->getSudoCmd() . '/usr/bin/crontab -u root -l | grep -v "hdsentinel_to_jeedom_pub" | sudo -S /usr/bin/crontab -); echo $?)';
        $cmd2 = $this->getSudoCmd() . "rm /etc/cron.daily/hdsentinel; echo $?;";
        return $this->sendSshCmd([$cmd1,$cmd2]);
    }

    public function stopCron()
    {
        /**
         * Arrête le cron distant dans crontab (garde le fichier pour le relancer)
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Arrêt du cron distant', __FILE__));
        $cmd = '[ -f "/opt/bin/crontab" ] && ((' . $this->getSudoCmd() . '/opt/bin/crontab -u root -l | grep -v "hdsentinel_to_jeedom_pub" | sudo -S /opt/bin/crontab -); echo $?) || ';
        $cmd .= '([ -f "/usr/bin/crontab" ] && (' . $this->getSudoCmd() . '/usr/bin/crontab -u root -l | grep -v "hdsentinel_to_jeedom_pub" | sudo -S /usr/bin/crontab -); echo $?)';
        return $this->sendSshCmd([$cmd]);
    }


    public function statusCron()
    {
        /**
         * Renvoi le status du cron dans le crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Statut du cron distant', __FILE__));
        $cmd = '([ -f "/opt/bin/crontab" ] && (' . $this->getSudoCmd() . '/opt/bin/crontab -u root -l | grep hdsentinel_to_jeedom_pub | wc -l)) || ';
        $cmd .= '([ -f "/usr/bin/crontab" ] && (' . $this->getSudoCmd() . '/usr/bin/crontab -u root -l | grep hdsentinel_to_jeedom_pub | wc -l))';
        return $this->sendSshCmd([$cmd]);
    }

    public function installDependancy()
    {
        /**
         * Envoie de commandes à l'appareil distant
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Installation des dépendances', __FILE__));
        $cmd = $this->getSudoCmd();
        $cmd .= 'bash /home/'.$this->getConfiguration('user').'/hdsentinel/ressources/install_apt.sh  >> ' . '/tmp/hdsentinel_dependancy' . ' 2>&1 &';
        return $this->sendSshCmd([$cmd]);
    }

    public function getLogDependancy($_dependancy='')
    {
        /**
         * Récupère le log d'installation des dépendances sur l'appareil distant
         *
         * @param			$_dependancy     string       Pour attriber un nom au log
         * @return			                 bool         Retour de la commande
         */
        $name = $this->getName();
        $local = dirname(__FILE__) . '/../../../../log/hdsentinel_'.str_replace(' ', '-', $name).$_dependancy;
        log::add(__CLASS__, 'info', __('Suppression de la log ', __FILE__) . $local);
        exec('rm -f '. $local);
        log::add(__CLASS__, 'info', __('Récupération de la log distante', __FILE__));
        if ($this->getFiles($local, '/tmp/hdsentinel_dependancy'.$_dependancy)) {
            $this->sendSshCmd(['cat /dev/null > /tmp/hdsentinel_dependancy'.$_dependancy]);
            return true;
        }
        return false;
    }

    public function getLog($_dependancy='')
    {
        /**
         * Récupère le log du cron sur l'appareil distant
         *
         * @param			$_dependancy     string       Pour attriber un nom au log
         * @return			                 bool         Retour de la commande
         */
        $name = $this->getName();
        $local = dirname(__FILE__) . '/../../../../log/hdsentinel_log_'.str_replace(' ', '-', $name).$_dependancy;
        log::add(__CLASS__, 'info', __('Suppression de la log ', __FILE__) . $local);
        exec('rm -f '. $local);
        log::add(__CLASS__, 'info', __('Récupération de la log distante', __FILE__));
        if ($this->getFiles($local, '/tmp/hdsentinel_log'.$_dependancy)) {
            $this->sendSshCmd(['cat /dev/null > /tmp/hdsentinel_log'.$_dependancy]);
            return true;
        }
        return false;
    }

    public function sendFile()
    {
        /**
         * Envoi les scripts du cron et d'installation
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$result        array         Résultat des 2 scripts envoyés
         */
        $result = array();
        $cmd = $this->getSudoCmd();
        $user = $this->getConfiguration('user');
        log::add(__CLASS__, 'debug', __('Envoi de fichier ', __FILE__) . $this->getName());
        $script_path = dirname(__FILE__) . '/../../ressources/';
		exec('tar -zcvf /tmp/folder-hdsentinel.tar.gz ' . $script_path);

        log::add(__CLASS__, 'info', __('Création du dossier des scripts', __FILE__));
		$result['dir'] = $this->sendSshCmd([$cmd.'rm -Rf /home/'.$user.'/hdsentinel',$cmd.'mkdir -p /home/'.$user.'/hdsentinel;echo $?']);
        log::add(__CLASS__, 'info', 'Envoi du fichier /tmp/folder-hdsentinel.tar.gz');

		if ($this->sendSshFiles('/tmp/folder-hdsentinel.tar.gz','/home/'.$user.'/folder-hdsentinel.tar.gz')) {
			log::add(__CLASS__,'info',__('Décompression du dossier distant',__FILE__));
			$result['uncompress'] = $this->sendSshCmd([$cmd . 'tar -zxf /home/'.$user.'/folder-hdsentinel.tar.gz -C /home/'.$user.'/hdsentinel;echo $?;',$cmd.'rm -f /home/'.$user.'/folder-hdsentinel.tar.gz;echo $?;']);
            $result['install'] = $this->sendSshCmd(['ls /home/'.$user.'/hdsentinel/ressources/install_apt.sh | wc -l']);
            $result['publish'] = $this->sendSshCmd(['ls /home/'.$user.'/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh | wc -l']);
        }

        log::add(__CLASS__, 'info', __('Suppression des anciens log', __FILE__));
        $result['removeLog'] = $this->sendSshCmd([$cmd . 'rm /tmp/hdsentinel_*']);

        return $result;
    }

    public function getFiles($_local, $_target)
    {
        /**
         * Récupère un fichier à un emplacement donné
         *
         * @param			$_local        string        Emplacement distant
         * @param			$_target       string        Emplacement local
         * @return			               bool          Vrai
         */
        if (!$connection = ssh2_connect($this->getConfiguration('addressip'), $this->getConfiguration('portssh'))) {
            log::add(__CLASS__, 'error', __('Connexion SSH KO pour ', __FILE__) . $this->getName());
            return false;
        } else {
            if (!ssh2_auth_password($connection, $this->getConfiguration('user'), $this->getConfiguration('password'))) {
                log::add(__CLASS__, 'error', __('Authentification SSH KO pour ', __FILE__) . $this->getName());
                return false;
            } else {
                log::add(__CLASS__, 'info', __('Récupération de fichier depuis ', __FILE__) . $this->getConfiguration('addressip'));
                ssh2_scp_recv($connection, $_target, $_local);
                $execmd = $this->getSudoCmd() . 'exit';
                $stream = ssh2_exec($connection, $execmd);
                $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                stream_set_blocking($errorStream, true);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
                fclose($errorStream);
                if (trim($output) != '') {
                    log::add(__CLASS__, 'debug', $output);
                }
            }
        }
        return true;
    }

    public function sendSshFiles($_local, $_target)
    {
        /**
         * Envoie un fichier à un emplacement donné
         *
         * @param			$_local        string        Emplacement distant
         * @param			$_target       string        Emplacement local
         * @return			               bool          Vrai
         */
        if (!$connection = ssh2_connect($this->getConfiguration('addressip'), $this->getConfiguration('portssh'))) {
            log::add(__CLASS__, 'debug', __('Connexion SSH KO pour ', __FILE__) . $this->getName());
            return false;
        } else {
            if (!ssh2_auth_password($connection, $this->getConfiguration('user'), $this->getConfiguration('password'))) {
                log::add(__CLASS__, 'error', __('Authentification SSH KO pour ', __FILE__) . $this->getName());
                return false;
            } else {
                $result = ssh2_scp_send($connection, $_local, $_target, 0777);
                if (!$result) {
                    log::add(__CLASS__, 'error', __('Erreur d\'envoi du fichier sur ', __FILE__) . $this->getConfiguration('addressip'));
                    return false;
                } else {
                    log::add(__CLASS__, 'info', __('Fichier envoyé avec succès sur ', __FILE__) . $this->getConfiguration('addressip'));
                }
                $stream = ssh2_exec($connection, $this->getSudoCmd() . 'exit');
                $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                stream_set_blocking($errorStream, true);
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
                fclose($errorStream);
                if (trim($output) != '') {
                    log::add(__CLASS__, 'debug', $output);
                }
            }
        }
        return true;
    }

    public function sendSshCmd($_cmd)
    {
        /**
         * Envoie de commandes à l'appareil distant
         *
         * @param			$_cmd        array       Tableau des commandes à envoyer
         * @return			             string      Retour de la commande
         */
        $plugin = plugin::byId('hdsentinel');
        if (!$connection = ssh2_connect($this->getConfiguration('addressip'), $this->getConfiguration('portssh'))) {
            log::add(__CLASS__, 'debug', __('Connexion SSH KO pour ', __FILE__) . $this->getName());
            return false;
        } else {
            if (!ssh2_auth_password($connection, $this->getConfiguration('user'), $this->getConfiguration('password'))) {
                log::add(__CLASS__, 'error', __('Authentification SSH KO pour ', __FILE__) . $this->getName());
                return false;
            } else {
                foreach ($_cmd as $cmd) {
                    $cmdLog = str_replace($this->getConfiguration('password'),'PASSWORD',$cmd);
                    $cmdLog = str_replace(jeedom::getApiKey($plugin->getId()),'APIKEY',$cmdLog);
                    log::add(__CLASS__, 'info', __('Commande par SSH2 ', __FILE__) . $cmdLog .  __(' sur ', __FILE__) . $this->getConfiguration('addressip'));
                    $execmd = $cmd;
                    $stream = ssh2_exec($connection, $execmd);
                    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                    stream_set_blocking($errorStream, true);
                    stream_set_blocking($stream, true);
                    $output = stream_get_contents($stream);
					//$output = stream_get_contents($stream) . ' ' . stream_get_contents($errorStream);

                    fclose($stream);
                    fclose($errorStream);
                    log::add(__CLASS__, 'info', __('Sortie commande par SSH2 ', __FILE__) . $output .  __(' sur ', __FILE__) . $this->getConfiguration('addressip'));
                }
                $stream = ssh2_exec($connection, 'exit');
                $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                stream_set_blocking($errorStream, true);
                stream_set_blocking($stream, true);
                fclose($stream);
                fclose($errorStream);
                $output = trim($output);
                return $output;
            }
        }
        return false;
    }

    public function test()
    {
        /**
         * Teste un envoie du XML complet, du coup, permet de générer les commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Test de la commande', __FILE__));
        $plugin = plugin::byId('hdsentinel');
        $cmd = $this->getSudoCmd();
        $cmd .= 'bash /home/' . $this->getConfiguration('user') . '/hdsentinel/ressources/hdsentinel_to_jeedom_pub.sh';
        $cmd .= ' -a ' . jeedom::getApiKey($plugin->getId());
        $cmd .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
        $cmd .= ' -o xml';
        //$cmd .= ' >> /tmp/hdsentinel_log 2>&1 &"';
        $return = $this->sendSshCmd([$cmd]);
        log::add(__CLASS__, 'info', __('Test de la commande - résultat : ', __FILE__) . $return);
        return $return;
    }

    public function refresh()
    {
        /**
         * Teste un envoie du XML complet, du coup, permet de générer les commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Refresh commande', __FILE__));

        $cmd = "HDSENTINEL=$(which hdsentinel);
        if [ -f '/usr/local/bin/hdsentinel' ];
          then HDSENTINEL='/usr/local/bin/hdsentinel';
        elif [ -f '/usr/bin/hdsentinel' ]; then
          HDSENTINEL='/usr/bin/hdsentinel';
        elif [ -f '/bin/hdsentinel' ]; then
          HDSENTINEL='/bin/hdsentinel';
        elif [ -f '/sbin/hdsentinel' ]; then
          HDSENTINEL='/sbin/hdsentinel';
        fi; ";
        $cmd .= "DISK='';
        if [ -f '/dev/mmcblk0p1' ];
          then DISK='-dev /dev/mmcblk0p1';
        fi; ";
        $cmd .= $this->getSudoCmd();
        $cmd .= '$HDSENTINEL -dump -xml $DISK';
        $return = $this->sendSshCmd([$cmd]);

        try {
            $xml_action = new SimpleXMLElement($return);
            $result = json_decode(json_encode($xml_action), true);
            self::getApiXmlResult($result, $this->getConfiguration('addressip'));
        } catch (Exception $e) {
            log::add(__CLASS__, 'info', __('Erreur XML', __FILE__));
        }
        return $return;
    }

    public function getHtmlDisksFullResultManually()
    {
        $cmd = "HDSENTINEL=$(which hdsentinel);
        if [ -f '/usr/local/bin/hdsentinel' ];
          then HDSENTINEL='/usr/local/bin/hdsentinel';
        elif [ -f '/usr/bin/hdsentinel' ]; then
          HDSENTINEL='/usr/bin/hdsentinel';
        elif [ -f '/bin/hdsentinel' ]; then
          HDSENTINEL='/bin/hdsentinel';
        elif [ -f '/sbin/hdsentinel' ]; then
          HDSENTINEL='/sbin/hdsentinel';
        fi; ";
        $cmd .= $this->getSudoCmd();
        $cmd .= '$HDSENTINEL -html -r /tmp/hdsentinel.html >/dev/null 2>&1 && cat /tmp/hdsentinel.html';
        return $this->sendSshCmd([$cmd]);
    }
}

class hdsentinelCmd extends cmd
{
    public static $_widgetPossibility = array('custom' => true);

    public function execute($_options = null)
    {
        $eqLogic = $this->getEqLogic();
        if ($this->getLogicalId() == '') {
            $paramaction = $this->getId();
        } else {
            $paramaction = $this->getLogicalId();
        }

        switch ($this->getType()) {
            case 'info':
                log::add('hdsentinel', 'debug', __('TYPE info ', __FILE__));
                break;
            case 'action':
                log::add('hdsentinel', 'debug', __('TYPE action ', __FILE__). $paramaction . ' avec option : '. json_encode($_options));
                if ($this->getLogicalId() == 'refresh') {
                    $eqLogic->refresh();
                }
                break;
            default:
                log::add('hdsentinel', 'debug', __('TYPE autre : ', __FILE__));
                break;
        }
        return true;
    }
}
