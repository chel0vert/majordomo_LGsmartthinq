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

require_once 'LGAPI.php';

class LGsmartthinq extends module {
/**
* LGsmartthinq
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="LGsmartthinq";
  $this->title="LG smartthinq";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->api = new LGAPI(Null, Null, Null, Null);
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
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
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
    $this->getConfig();
    $out['API_URL']=$this->config['API_URL'];
    if (!$out['API_URL']) {
        $out['API_URL']='http://';
    }
    $out['API_KEY']             = $this->config['API_KEY'];
    $out['API_USERNAME']        = $this->config['API_USERNAME'];
    $out['API_PASSWORD']        = $this->config['API_PASSWORD'];
    $out['API_COUNTRY']         = $this->config['API_COUNTRY'];
    $out['API_LANGUAGE']        = $this->config['API_LANGUAGE'];
    $out['API_REFRESH_PERIOD']  = $this->config['API_REFRESH_PERIOD'];
 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   global $api_country;
   $this->config['API_COUNTRY']=$api_country;
   global $api_language;
   $this->config['API_LANGUAGE']=$api_language;
   global $api_refresh_period;
   $this->config['API_REFRESH_PERIOD']=$api_refresh_period;
   $this->api->set_api_property("email",    $api_username);
   $this->api->set_api_property("password", $api_password);
   $this->api->set_api_property("country",  $api_country );
   $this->api->set_api_property("language", $api_language);
   $this->api->check_gateway();
   $this->saveConfig();
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='lgsmarthinq_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_lgsmarthinq_devices') {
   $this->search_lgsmarthinq_devices($out);
  }
  if ($this->view_mode=='edit_lgsmarthinq_devices') {
   $this->edit_lgsmarthinq_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_lgsmarthinq_devices') {
   $this->delete_lgsmarthinq_devices($this->id);
   $this->redirect("?data_source=lgsmarthinq_devices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='lgsmarthinq_values') {
  if ($this->view_mode=='' || $this->view_mode=='search_lgsmarthinq_values') {
   $this->search_lgsmarthinq_values($out);
  }
  if ($this->view_mode=='edit_lgsmarthinq_values') {
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
function usual(&$out) {
 $this->admin($out);
}
/**
* lgsmarthinq_devices search
*
* @access public
*/
 function search_lgsmarthinq_devices(&$out) {
  require(DIR_MODULES.$this->name.'/lgsmarthinq_devices_search.inc.php');
 }
/**
* lgsmarthinq_devices edit/add
*
* @access public
*/
 function edit_lgsmarthinq_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/lgsmarthinq_devices_edit.inc.php');
 }
/**
* lgsmarthinq_devices delete record
*
* @access public
*/
 function delete_lgsmarthinq_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM lgsmarthinq_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM lgsmarthinq_devices WHERE ID='".$rec['ID']."'");
 }
/**
* lgsmarthinq_values search
*
* @access public
*/
 function search_lgsmarthinq_values(&$out) {
  require(DIR_MODULES.$this->name.'/lgsmarthinq_values_search.inc.php');
 }
/**
* lgsmarthinq_values edit/add
*
* @access public
*/
 function edit_lgsmarthinq_values(&$out, $id) {
  require(DIR_MODULES.$this->name.'/lgsmarthinq_values_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
   $table='lgsmarthinq_values';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
        $values        = $properties[$i];
        $linked_object = $values['LINKED_OBJECT'];
        $linked_method = $values['LINKED_METHOD'];
        if ( isset($linked_object) && isset($linked_method) ) {
            callMethodSafe("$linked_object.$linked_method");
        }
    }
   }
 }
 function processSubscription($event, $details='') {
 $this->getConfig();
  if ($event=='SAY') {
   $level=$details['level'];
   $message=$details['message'];
   //...
  }
 }
 function processCycle() {
     $this->getConfig();
     $this->api->set_api_property("email",    $this->config['API_USERNAME']);
     $this->api->set_api_property("password", $this->config['API_PASSWORD']);
     $this->api->set_api_property("country",  $this->config['API_COUNTRY'] );
     $this->api->set_api_property("language", $this->config['API_LANGUAGE']);
     $this->api->check_gateway();
     if ( $this->config['API_REFRESH_PERIOD'] <= 0 ) {
         return Null;
     }

     $this->api->set_devices();
     $devices = $this->api->get_devices(); # получение устройств с api
     #debmes($devices, 'lgsmarthinq');
     foreach ($devices as $device) {
         if (isset($device)) {
             #debmes($device, 'lgsmarthinq');
             $device_id = $this->getDeviceIdPerMacAddress($device);
             #debmes("Device ID: $device_id", 'lgsmarthinq');
             foreach ($device as $key => $value) {
                 $this->set_device_property($device_id, $key, $value);
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
 function install($data='') {
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
 function uninstall() {
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
 function dbInstall($data) {
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
 lgsmarthinq_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_devices: UPDATED datetime
 lgsmarthinq_values: ID int(10) unsigned NOT NULL auto_increment
 lgsmarthinq_values: TITLE varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: VALUE varchar(255) NOT NULL DEFAULT ''
 lgsmarthinq_values: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 lgsmarthinq_values: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 lgsmarthinq_values: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }

    function saveTokens($access_token, $refresh_token) {
        $this->set_api_property('ACCESS_TOKEN', $access_token);
        $this->set_api_property('REFRESH_TOKEN', $refresh_token);
    }

    function get_api_property($property){
        $values=SQLSelectOne("SELECT * FROM lgsmarthinq WHERE TITLE='$property'");
        $result = $values['VALUE'];
        return $result;
    }

    function set_api_property($property, $value){
        $values=SQLSelectOne("SELECT * FROM lgsmarthinq WHERE TITLE='$property'");
        if ( isset($values) && isset($values['ID']) ) {
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

    function getDeviceIdPerMacAddress($device){
        $values=SQLSelectOne("SELECT * FROM lgsmarthinq_devices WHERE MAC='".$device->macAddress."'");
        if ( !isset($values) ) {
            $values = array(
                'TITLE' => $device->alias,
                'MAC'   => $device->macAddress,
                'IMAGE' => $device->smallImageUrl,
            );
            SQLInsert('lgsmarthinq_devices', $values);
            $values = SQLSelectOne("SELECT * FROM lgsmarthinq_devices WHERE MAC='".$device->macAddress."'");
            $result = $values['ID'];
            debmes("insert device ".$device->macAddress." => id $result", 'lgsmarthinq');
        } else {
            $result = $values['ID'];
        }
        return $result;
    }

    function get_device_property($id, $property){
        $values=SQLSelectOne("SELECT * FROM lgsmarthinq_values WHERE DEVICE_ID='$id' and TITLE='$property'");
        $result = $values['VALUE'];
        return $result;
    }

    function set_device_property($id, $property, $value){
        if (!$id) {
            return Null;
        }
        $values=SQLSelectOne("SELECT * FROM lgsmarthinq_values WHERE DEVICE_ID='$id' and TITLE='$property'");
        if ( isset($values) && isset($values['ID']) ) {
            $values['VALUE'] = "$value";
            SQLUpdate('lgsmarthinq_values', $values);
            #debmes("update device id($id) property $property => $value ", 'lgsmarthinq');
        } else {
            $values = array(
                'TITLE' => $property,
                'DEVICE_ID' => $id,
                'VALUE' => "$value",
            );
            #debmes("insert device id($id) property $property => $value", 'lgsmarthinq');
            SQLInsert('lgsmarthinq_values', $values);
        }

        $linked_object = $values['LINKED_OBJECT'];
        $linked_method = $values['LINKED_METHOD'];
        if ( isset($linked_object) ) {
            sg("$linked_object.$property", $value);
            if ( isset($linked_method) ){
                callMethodSafe("$linked_object.$linked_method");
            }
        }

        return $values;
    }

    function getAccessToken() {
        $this->getConfig();
        $access_token = $this->api->get_access_token();
        $this->config['API_USERNAME']=$access_token;
        $refresh_token = $this->api->get_refresh_token();
        $this->config['API_PASSWORD']=$refresh_token;
        return $access_token;
    }
    function getRefreshToken() {
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
