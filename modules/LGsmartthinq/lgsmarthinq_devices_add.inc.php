<?php
/*
* @version 0.1 (wizard)
*/

include_once(DIR_MODULES . 'LGsmartthinq/LGsmartthinq.class.php');
include_once(DIR_MODULES . 'LGsmartthinq/LGAPI.php');
$LGsmartthinq_module = new LGsmartthinq();
$module_config = $LGsmartthinq_module->getConfig();
$redirected_url = $module_config['API_REDIRECTED_URL'];
$api = new LGAPI($module_config['API_COUNTRY'], $module_config['API_LANGUAGE']);
$api->set_api_property('access_token', $LGsmartthinq_module->config['API_ACCESS_TOKEN']);
$api->set_api_property('refresh_token', $LGsmartthinq_module->config['API_REFRESH_TOKEN']);
$api->set_api_property('user_number', $LGsmartthinq_module->config['API_USER_NUMBER']);
$api->set_api_property('redirected_url', $LGsmartthinq_module->config['API_REDIRECTED_URL']);

$result = $api->set_devices();
if (!$result) {
    return Null;
} else {
    $devices = $api->get_devices(); # получение устройств с api
    foreach ($devices as $device) {
        $device_id = $LGsmartthinq_module->getMJDDeviceId($device);
        if (!$device_id) {
            $device_id = $LGsmartthinq_module->addDevice2MJD($device);
            if ($device_id) {
                debmes("added device: '$device->deviceId' => '$device_id' id in DB ", 'lgsmarthinq');
            } else {
                debmes("Cannot add device. No deviceId from api: Device:", 'lgsmarthinq');
                debmes($device, 'lgsmarthinq');
            }
        } else {
            debmes("device: '$device->deviceId' => '$device_id' already exists in DB ", 'lgsmarthinq');
        }

        if ($device_id) {
            foreach ($device as $key => $value) {
                $LGsmartthinq_module->set_device_property($device_id, $key, $value);
            }
        }
    }
}