<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'LGsmartthinq/LGsmartthinq.class.php');
include_once(DIR_MODULES . 'LGsmartthinq/LGAPI.php');
$LGsmartthinq_module = new LGsmartthinq();
$module_config = $LGsmartthinq_module->getConfig();
$api = new LGAPI($module_config['API_COUNTRY'], $module_config['API_LANGUAGE'], $module_config['API_ACCESS_TOKEN'], $module_config['API_REFRESH_TOKEN'], $module_config['API_USER_NUMBER']);
$refresh_token = $LGsmartthinq_module->config['API_REFRESH_TOKEN'];
if ($refresh_token) {
    $api->set_refresh_token($refresh_token);
}
$api->update_access_token();
$api->login();
$access_token = $api->get_access_token();
if (!$access_token) {
    echo "No access token\n";
    debmes("No access token for LG smartthinq", 'lgsmarthinq');
} else {
    $LGsmartthinq_module->config['API_ACCESS_TOKEN'] = $api->get_access_token();
    $LGsmartthinq_module->config['API_ACCESS_TOKEN_EXPIRE'] = 3600;
    $LGsmartthinq_module->config['API_ACCESS_TOKEN_TIMESTAMP'] = time();
    $LGsmartthinq_module->config['API_REFRESH_TOKEN'] = $api->get_refresh_token();
    $LGsmartthinq_module->config['API_SESSION_ID'] = $api->get_session_id();
    $LGsmartthinq_module->saveConfig();
}
#$tmp = SQLSelectOne("SELECT ID FROM lgsmarthinq_devices LIMIT 1");
#if (!$tmp['ID'])
#   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;

while (1) {

    $module_config = $LGsmartthinq_module->getConfig();
    $checkEvery = $module_config['API_REFRESH_PERIOD']; // poll every 5 seconds

    if ($checkEvery <= 0) {
        $checkEvery = 60;
    }

    setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    if ((time() - $latest_check) > $checkEvery) {
        $latest_check = time();
        echo date('Y-m-d H:i:s') . ' Polling devices...' . "\n";
        $expire = $LGsmartthinq_module->config['API_ACCESS_TOKEN_EXPIRE'];
        $last_access_token_check = $LGsmartthinq_module->config['API_ACCESS_TOKEN_TIMESTAMP'];
        $current_timestamp = time();
        if (($current_timestamp - $last_access_token_check) > $expire || !$api->get_access_token() || $api->get_api_error()) {
            $api->set_api_property('country', $LGsmartthinq_module->config['API_COUNTRY']);
            $api->set_api_property('language', $LGsmartthinq_module->config['API_LANGUAGE']);
            $api->update_access_token();
            $api->login();
            $LGsmartthinq_module->config['API_ACCESS_TOKEN'] = $api->get_access_token();
            $LGsmartthinq_module->config['API_ACCESS_TOKEN_EXPIRE'] = 3600;
            $LGsmartthinq_module->config['API_ACCESS_TOKEN_TIMESTAMP'] = $current_timestamp;
            $LGsmartthinq_module->config['API_REFRESH_TOKEN'] = $api->get_refresh_token();
            $LGsmartthinq_module->config['API_SESSION_ID'] = $api->get_session_id();
            $LGsmartthinq_module->saveConfig();
        }
        $LGsmartthinq_module->processCycle();
    }

    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        $db->Disconnect();
        exit;
    }
    sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
