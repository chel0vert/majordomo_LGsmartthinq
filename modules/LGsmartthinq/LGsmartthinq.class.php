<?php
/**
 * LG smartthinq
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 20:08:26 [Aug 06, 2019])
 */
//API_USERNAME
//

require DIR_MODULES . '/LGsmartthinq/LGAPI.php';

class LGsmartthinq extends module
{
    /**
     * LGsmartthinq
     *
     * Module class constructor
     *
     * @access private
     */

    function __construct()
    {
        $this->name = "LGsmartthinq";
        $this->title = "LG smartthinq";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->api = new LGAPI(Null, Null, Null, Null, Null);
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->data_source)) {
            $p["data_source"] = $this->data_source;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $data_source;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($data_source)) {
            $this->data_source = $data_source;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['DATA_SOURCE'] = $this->data_source;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        global $api_url;
        global $api_redirected_url;
        $this->getConfig();
        $out['API_COUNTRY'] = $this->config['API_COUNTRY'];
        $out['API_LANGUAGE'] = $this->config['API_LANGUAGE'];
        $out['API_REFRESH_PERIOD'] = $this->config['API_REFRESH_PERIOD'];
        $out['API_REDIRECTED_URL'] = $this->config['API_REDIRECTED_URL'];
        global $api_country;
        global $api_language;
        $this->api->set_api_property("country", $this->config['API_COUNTRY']);
        $this->api->set_api_property("language", $this->config['API_LANGUAGE']);
        $this->api->check_gateway();
        $out['OAUTH_URL'] = $this->api->oauth_url();
        if ($this->view_mode == 'update_settings') {
            global $api_key;
            global $api_access_token;
            global $api_refresh_token;
            $this->config['API_COUNTRY'] = $api_country;
            $this->config['API_LANGUAGE'] = $api_language;
            global $api_refresh_period;
            $this->config['API_REFRESH_PERIOD'] = $api_refresh_period;
            $this->api->set_api_property("country", $api_country);
            $this->api->set_api_property("language", $api_language);
            $this->api->check_gateway();
            $api_url = $this->api->oauth_url();
            $this->config['API_URL'] = $api_url;
            $this->config['API_REDIRECTED_URL'] = $api_redirected_url;
            if ($api_redirected_url) {
                $this->api->parse_redirected_url($api_redirected_url);
                $this->api->login();
                $this->config['API_ACCESS_TOKEN'] = $this->api->get_access_token();
                $this->config['API_REFRESH_TOKEN'] = $this->api->get_refresh_token();
                $this->config['API_USER_NUMBER'] = $this->api->get_user_number();
            }
            $this->saveConfig();
            $this->redirect("?");
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'lgsmarthinq_devices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_lgsmarthinq_devices') {
                $this->search_lgsmarthinq_devices($out);
            }
            if ($this->view_mode == 'edit_lgsmarthinq_devices') {
                $this->edit_lgsmarthinq_devices($out, $this->id);
            }
            if ($this->view_mode == 'delete_lgsmarthinq_devices') {
                $this->delete_lgsmarthinq_devices($this->id);
                $this->redirect("?data_source=lgsmarthinq_devices");
            }
        }
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'lgsmarthinq_values') {
            if ($this->view_mode == '' || $this->view_mode == 'search_lgsmarthinq_values') {
                $this->search_lgsmarthinq_values($out);
            }
            if ($this->view_mode == 'edit_lgsmarthinq_values') {
                $this->edit_lgsmarthinq_values($out, $this->id);
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * lgsmarthinq_devices search
     *
     * @access public
     */
    function search_lgsmarthinq_devices(&$out)
    {
        require(DIR_MODULES . $this->name . '/lgsmarthinq_devices_search.inc.php');
    }

    /**
     * lgsmarthinq_devices edit/add
     *
     * @access public
     */
    function edit_lgsmarthinq_devices(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/lgsmarthinq_devices_edit.inc.php');
    }

    /**
     * lgsmarthinq_devices delete record
     *
     * @access public
     */
    function delete_lgsmarthinq_devices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM lgsmarthinq_devices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM lgsmarthinq_devices WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * lgsmarthinq_values search
     *
     * @access public
     */
    function search_lgsmarthinq_values(&$out)
    {
        require(DIR_MODULES . $this->name . '/lgsmarthinq_values_search.inc.php');
    }

    /**
     * lgsmarthinq_values edit/add
     *
     * @access public
     */
    function edit_lgsmarthinq_values(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/lgsmarthinq_values_edit.inc.php');
    }

    function propertySetHandle($object, $property, $value)
    {
        $this->getConfig();
        $this->set_tokens_to_api();
        $table = 'lgsmarthinq_values';
        $properties = SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                $values = $properties[$i];
                $linked_object = $values['LINKED_OBJECT'];
                $linked_method = $values['LINKED_METHOD'];
                $Programm = gg("$linked_object.Programm");
                $device = $this->getDeviceByID($values['DEVICE_ID']);
                $device->Programm = $Programm; #FIXME надо придумать как передавать программу стирки
                #debmes($device, 'lgsmarthinq');
                #debmes("property: $property value: $value", 'lgsmarthinq');
                if ($property == 'command') {
                    if ($value == 'Start' && $Programm >= 0) {
                        $device->Course = $Programm;
                        $this->api->start_command($device, 'Control', 'Operation', 'Start');
                    } else if ($value == 'Stop') {
                        $this->api->start_command($device, 'Control', 'Operation', 'Stop');
                    } else if ($value == 'WakeUp') {
                        $this->api->start_command($device, 'Control', 'Operation', 'WakeUp');
                    } else if ($value == 'Off') {
                        $this->api->start_command($device, 'Control', 'Power', 'Off');
                    } else if ($value == 'SetCustomProgramm' || $value == 'StartCustomProgramm') {
                        #debmes($device, 'lgsmarthinq');
                        $device->Course = $Programm; # значение=3 у стиральной машинке F2J7HSR2S = Моя программа
                        $params = array(
                            'Course' => $Programm,
                            'Wash' => gg("$linked_object.SetWash"),#0,
                            'SpinSpeed' => gg("$linked_object.SetSpinSpeed"),#1,
                            'WaterTemp' => gg("$linked_object.SetWaterTemp"),#1,
                            'RinseOption' => gg("$linked_object.SetRinseOption"),#1,
                            'Reserve_Time_H' => gg("$linked_object.SetReserve_Time_H"),#0,
                            'Reserve_Time_M' => gg("$linked_object.SetReserve_Time_M"),#0,
                            'LoadItem' => gg("$linked_object.SetLoadItem"),#0,
                            'Option1' => gg("$linked_object.SetOption1"),#3,
                            'Option2' => gg("$linked_object.SetOption2"),#0,
                            'SmartCourse' => gg("$linked_object.SetSmartCourse"),#0,
                        );
                        #debmes($params, 'lgsmarthinq');
                        $data = $this->api->update_course_command($device, $params);
                        if ($value == 'StartCustomProgramm') {
                            $device->Programm = Null;
                            $this->api->start_command($device, 'Control', 'Operation', 'Start', $params);
                        }
                    }
                } else if ($property == 'status') {
                    if ($value == 1 && $Programm >= 0) {
                        $device->Course = $Programm;
                        $this->api->start_command($device, 'Control', 'Operation', 'Start');
                    } else if ($value == 0) {
                        $this->api->start_command($device, 'Control', 'Operation', 'Stop');
                    }
                }

                if (isset($linked_object) && isset($linked_method)) {
                    callMethodSafe("$linked_object.$linked_method");
                }
            }
        }
    }

    function getDeviceByID($device_id)
    {
        $fields = array(
            deviceId => Null,
            modelJsonUrl => Null,
            deviceType => Null,
            langPackModelUri => Null,
            langPackProductTypeUri => Null,
            Course => Null,
        );
        $device = new stdClass;
        foreach ($fields as $property => $value) {
            $values = SQLSelectOne("SELECT * FROM lgsmarthinq_values WHERE DEVICE_ID = $device_id AND TITLE = '$property'");
            $device->$property = $values['VALUE'];
        }
        return $device;
    }

    function set_tokens_to_api()
    {
        $this->getConfig();
        $access_token = $this->config['API_ACCESS_TOKEN'];
        $refresh_token = $this->config['API_REFRESH_TOKEN'];
        $user_number = $this->config['API_USER_NUMBER'];
        if (isset($access_token)) {
            $this->api->set_access_token($access_token);
        }
        if (isset($user_number)) {
            $this->api->set_api_property('user_number', $user_number);
        }
        if ($refresh_token) {
            $this->api->set_api_property('refresh_token', $refresh_token);
        }
        $country = $this->config['API_COUNTRY'];
        $language = $this->config['API_LANGUAGE'];
        if (isset($country) && isset($language)) {
            $this->api->set_api_property("country", $country);
            $this->api->set_api_property("language", $language);
        }
    }

    function processSubscription($event, $details = '')
    {
        $this->getConfig();
        if ($event == 'SAY') {
            $level = $details['level'];
            $message = $details['message'];
            //...
        }
    }

    function processCycle()
    {
        $this->getConfig();

        if ($this->config['API_REFRESH_PERIOD'] <= 0) {
            return Null;
        }
        $this->set_tokens_to_api();
        $result = $this->api->set_devices();
        if (!$result) {
            return Null;
        } else {
            $devices = $this->api->get_devices(); # получение устройств с api
            foreach ($devices as $device) {
				//debmes($device, 'lgsmarthinq');
                $device_id = $this->getMJDDeviceId($device);
                if (!$device_id) {
                    $device_id = $this->addDevice2MJD($device);
                    if ($device_id) {
                        debmes("added device: '$device->deviceId' => '$device_id' id in DB ", 'lgsmarthinq');
                    } else {
                        debmes("Cannot add device. No deviceId from api: Device:", 'lgsmarthinq');
                        debmes($device, 'lgsmarthinq');
                    }
                }
                if ($device_id) {
					$values = SQLSelect("SELECT * FROM lgsmarthinq_values WHERE DEVICE_ID='$device_id'");
					//debmes($values, 'lgsmarthinq');
                    foreach($values as $val){
                        $prop[$val['TITLE']] = $val;
                    }
                    $snapshot = $device->snapshot;
                    $prop_to_update = array();
                    foreach ($device as $key => $value) {
                        $prop_to_update[$key] = $value;
                    }
                    if (isset($snapshot)) {
                        $washer_dryer_snapshot = $snapshot->washerDryer;
                        if(isset($washer_dryer_snapshot)){
                            foreach ($washer_dryer_snapshot as $key => $value){
                                $prop_to_update[$key] = $value;
                            }
                        }
                    }
                    if (isset($device) && $device->deviceState != 'D') { # $device->deviceState == 'E' значит включена
                        $workId = $this->api->monitor_start($device->deviceId);
                        $try = 0;
                        $exit_flag = False;
                        $decoded_properties = array();
                        do {
                            $data = $this->api->monitor_result($device->deviceId);
                            $encoded_properties = isset($data->returnData) ? $data->returnData : '';
                            if ($encoded_properties != '') {
                                $decoded_properties = $this->api->decode_data($device, $encoded_properties);
                                break;
                            }
                            $try = $try + 1;
                            sleep(1);
                        } while ($try < 5);
                        if ($decoded_properties) {
                            foreach ($decoded_properties as $key => $value) {
                                //print_r($key);
                                //print_r($value);
                                //echo $key . " => " . $value . "\n";
                                //debmes($key . " => " . $value, 'lgsmarthinq');
                                $prop_to_update[$key] = $value;
                            }
                        }
                        $this->api->monitor_stop($device->deviceId);
                    }
                    foreach ($prop_to_update as $key => $value) {
                        //debmes($key.' => '.$value, 'lgsmarthinq');
                        $this->set_device_property($device_id, $key, $value, $prop);
                    }

                }
            }
        }
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        subscribeToEvent($this->name, 'SAY');
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        unsubscribeFromEvent('SAY');
        SQLExec('DROP TABLE IF EXISTS lgsmarthinq');
        SQLExec('DROP TABLE IF EXISTS lgsmarthinq_devices');
        SQLExec('DROP TABLE IF EXISTS lgsmarthinq_values');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        lgsmarthinq -
        lgsmarthinq_devices -
        lgsmarthinq_values -
        */
        $data = <<<EOD
 lgsmarthinq: ID int(10) unsigned NOT NULL auto_increment
 lgsmarthinq: TITLE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq: VALUE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq: UPDATED datetime
 lgsmarthinq_devices: ID int(10) unsigned NOT NULL auto_increment
 lgsmarthinq_devices: DEVICE_ID text NOT NULL DEFAULT ''
 lgsmarthinq_devices: MAC text NOT NULL DEFAULT '' 
 lgsmarthinq_devices: IMAGE text NOT NULL DEFAULT ''
 lgsmarthinq_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: UPDATED datetime
 lgsmarthinq_values: ID int(10) unsigned NOT NULL auto_increment
 lgsmarthinq_values: TITLE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: VALUE varchar(255) DEFAULT ''
 lgsmarthinq_values: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 lgsmarthinq_values: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: UPDATED datetime
EOD;
        parent::dbInstall($data);
    }

    function saveTokens($access_token, $refresh_token)
    {
        $this->set_api_property('ACCESS_TOKEN', $access_token);
        $this->set_api_property('REFRESH_TOKEN', $refresh_token);
    }

    function get_api_property($property)
    {
        $values = SQLSelectOne("SELECT * FROM lgsmarthinq WHERE TITLE='$property'");
        $result = $values['VALUE'];
        return $result;
    }

    function set_api_property($property, $value)
    {
        $values = SQLSelectOne("SELECT * FROM lgsmarthinq WHERE TITLE='$property'");
        if (isset($values) && isset($values['ID'])) {
            $values['VALUE'] = "$value";
            SQLUpdate('lgsmarthinq', $values);
            #debmes("update property $property => $value ", 'lgsmarthinq');
        } else {
            $values = array(
                'TITLE' => $property,
                'VALUE' => "$value",
            );
            #debmes("insert property $property => $value", 'lgsmarthinq');
            SQLInsert('lgsmarthinq', $values);
        }
        return $values;
    }

    function getMJDDeviceId($device)
    {
        $device_id = $this->getMJDDeviceIdByAPIDeviceId($device);
        if (!$device_id) {
            $device_id = $this->getMJDDeviceIdByMacAddress($device);
        }
        return $device_id;
    }

    function getMJDDeviceIdByAPIDeviceId($device)
    {
        $result = $this->getDeviceIdByField('DEVICE_ID', $device->deviceId, $device);
        return $result;
    }

    function getMJDDeviceIdByMacAddress($device)
    {
        $result = $this->getDeviceIdByField('MAC', $device->deviceId, $device);
        return $result;
    }

    function getDeviceIdByField($field, $value, $device)
    {
        $result = Null;
        if (!$value) {
            debmes("Can not get device id by value '$value' and field '$field'", 'lgsmarthinq');
            return Null;
        }
        $select = "SELECT * FROM lgsmarthinq_devices WHERE $field='$value'";
        try {
            $values = SQLSelectOne($select);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            debmes($e->getMessage(), 'lgsmarthinq');
            $values = Null;
        }
        if (isset($values)) {
            $values['DEVICE_ID'] = $device->deviceId;
            $values['UPDATED'] = date('Y-m-d H:i:s');
            SQLUpdate('lgsmarthinq_devices', $values);
            $result = $values['ID'];
        }
        return $result;
    }

    function addDevice2MJD($device)
    {
        $id = $this->getMJDDeviceId($device);
        if (!$id && $device->deviceId) {
            $values = array(
                'DEVICE_ID' => $device->deviceId,
                'TITLE' => $device->alias,
                'IMAGE' => $device->smallImageUrl,
                'UPDATED' => date('Y-m-d H:i:s'),
            );
            SQLInsert('lgsmarthinq_devices', $values);
            $id = $this->getMJDDeviceId($device);
        }
        return $id;
    }

    function get_device_property($id, $property)
    {
        $values = SQLSelectOne("SELECT * FROM lgsmarthinq_values WHERE DEVICE_ID='$id' and TITLE='$property'");
        $result = $values['VALUE'];
        return $result;
    }

    function set_device_property($id, $property, $value, $prop="")
    {

        if (!$id || !$property || is_object($value) || !is_string($property)) {
            return Null;
        } else if (is_array($value)) {

			$value = json_encode($value);
        }
            
        if (isset($prop[$property]) && isset($prop[$property]['ID'])) {
			$values = $prop[$property];
			//if (!$values['LINKED_PROPERTY']) {
			//	$values['LINKED_PROPERTY'] = $property;
			//}
			//if (!$values['LINKED_OBJECT']) {
			//	$values['LINKED_OBJECT'] = $device_linked_object;
			//}
			if($values['VALUE']!= $value){
				$linked_object = $values['LINKED_OBJECT'];
				//if (!$linked_object) {
				//	$linked_object = $device_linked_object;
				//}
				if (isset($linked_object)) {
					sg("$linked_object.$property", $value);
					$linked_method = $values['LINKED_METHOD'];
					if (isset($linked_method)) {
						$params['VALUE'] = $value;
						$params['OLD_VALUE'] = $values['VALUE'];
						callMethodSafe("$linked_object.$linked_method", $params);
					}
				}
				$values['VALUE'] = isset($values['VALUE'])? $value:0;
				$values['UPDATED'] = date('Y-m-d H:i:s');
				SQLUpdate('lgsmarthinq_values', $values);
			}
        } else {
			$device_values = SQLSelectOne("SELECT * FROM lgsmarthinq_devices WHERE ID='$id'");
			$device_linked_object = $device_values['LINKED_OBJECT'];
            $values = array(
                'TITLE' => $property,
                'DEVICE_ID' => $id,
                'VALUE' => $value,
                'LINKED_PROPERTY' => $property,
                'LINKED_OBJECT' => isset($device_values['LINKED_OBJECT']) ? $device_values['LINKED_OBJECT'] : 0,
				'UPDATED' => date('Y-m-d H:i:s'),
            );
            #debmes("insert device id($id) property $property => $value", 'lgsmarthinq');
            SQLInsert('lgsmarthinq_values', $values);
        }
		
        return $values;
    }

    function getAccessToken()
    {
        $this->getConfig();
        $access_token = $this->api->get_access_token();
        $this->config['API_USERNAME'] = $access_token;
        $refresh_token = $this->api->get_refresh_token();
        $this->config['API_PASSWORD'] = $refresh_token;
        return $access_token;
    }

    function getRefreshToken()
    {
        $this->getConfig();
        return $this->config['API_PASSWORD'];
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXVnIDA2LCAyMDE5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
