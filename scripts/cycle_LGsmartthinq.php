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
$redirected_url = $module_config['API_REDIRECTED_URL'];
$LGsmartthinq_module->config['API_REFRESH_TOKEN'] = '';
$LGsmartthinq_module->config['API_ACCESS_TOKEN'] = '';
$api = new LGAPI($module_config['API_COUNTRY'], $module_config['API_LANGUAGE']);
$api->set_api_property('access_token', $LGsmartthinq_module->config['API_ACCESS_TOKEN']);
$api->set_api_property('refresh_token', $LGsmartthinq_module->config['API_REFRESH_TOKEN']);
$api->set_api_property('user_number', $LGsmartthinq_module->config['API_USER_NUMBER']);
$api->set_api_property('redirected_url', $LGsmartthinq_module->config['API_REDIRECTED_URL']);

$expire = $LGsmartthinq_module->config['API_ACCESS_TOKEN_EXPIRE'];
if (!$expire) {
    $expire = 3600;
    $LGsmartthinq_module->config['API_ACCESS_TOKEN_EXPIRE'] = $expire;
}

$access_token = $api->get_access_token();
$refresh_token = $api->get_refresh_token();

if (!$refresh_token) {
    echo("Getting new refresh token. url: $redirected_url\n");
    $api->parse_redirected_url($redirected_url);
    $api->login();
    $refresh_token = $api->get_refresh_token();
    if ($refresh_token) {
        logger("New refresh token was added to module params");
        $LGsmartthinq_module->config['API_REFRESH_TOKEN'] = $refresh_token;
    }
    $access_token = $api->get_access_token();
    if ($access_token) {
        logger("New access token was added to module params");
        $LGsmartthinq_module->config['API_ACCESS_TOKEN'] = $access_token;
    }
}

if ($refresh_token && !$access_token) {
    echo("Get new access token\n");
    $api->update_access_token();
}

$access_token = $api->get_access_token();
$refresh_token = $api->get_refresh_token();

if (!$access_token || !$refresh_token) {
    logger("ERROR: access token: $access_token ; refresh_token: $refresh_token. Exit");
    $error = $api->get_api_error();
    logger($error);
    exit;
} else {
    $LGsmartthinq_module->config['API_ACCESS_TOKEN'] = $access_token;
    $LGsmartthinq_module->config['API_REFRESH_TOKEN'] = $refresh_token;
    logger("access token and refresh token are ok. continue the cycle");
}

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;
# save all settings to mysql
logger($LGsmartthinq_module->config);
$LGsmartthinq_module->saveConfig();

while (1) {

    $module_config = $LGsmartthinq_module->getConfig();
    $checkEvery = $module_config['API_REFRESH_PERIOD'];

    if ($checkEvery <= 0) {
        $checkEvery = 60;
    }

    setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    if ((time() - $latest_check) > $checkEvery) {
        $latest_check = time();
        echo date('Y-m-d H:i:s') . ' Polling devices...' . "\n";
        $last_access_token_update = $LGsmartthinq_module->config['API_ACCESS_TOKEN_TIMESTAMP'];
        if (($latest_check - $last_access_token_update) > $expire || !$api->get_access_token() || $api->get_api_error()) {
            $api->update_access_token();
            $refresh_token = $api->get_refresh_token();
            $access_token = $api->get_access_token();
            if (!$access_token) {
                logger("Cannot update access_token. See log");
                logger($api->get_api_error);
            }
            if (!$refresh_token){
                logger("Cannot update refresh_token. See log");
                logger($api->get_api_error);
            }
            if ($access_token && $refresh_token) {
                $LGsmartthinq_module->config['API_ACCESS_TOKEN'] = $access_token;
                $LGsmartthinq_module->config['API_ACCESS_TOKEN_TIMESTAMP'] = $latest_check;
                $LGsmartthinq_module->config['API_REFRESH_TOKEN'] = $refresh_token;
                $LGsmartthinq_module->config['API_SESSION_ID'] = $api->get_session_id();
                $LGsmartthinq_module->saveConfig();
            }
        }
        $LGsmartthinq_module->processCycle();
    }

    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        $db->Disconnect();
        exit;
    }
    sleep($checkEvery);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));

function logger($message) {
    var_dump($message);
    debmes($message, 'lgsmarthinq');
}
