<?php

class LGAPI
{

    private $access_token;
    private $refresh_token;
    private $session_id;

    private $GATEWAY_URL    = 'https://kic.lgthinq.com:46030/api/common/gatewayUriList';
    private $APP_KEY        = 'wideq';
    private $SECURITY_KEY   = 'nuts_securitykey';
    private $DATA_ROOT      = 'lgedmRoot';
    private $SVC_CODE       = 'SVC202';
    private $CLIENT_ID      = 'LGAO221A02';
    private $auth_base      = Null;
    private $api_root       = Null;
    private $oauth_root     = Null;
    private $country        = Null;
    private $language       = Null;
    private $email          = Null;
    private $password       = Null;
    private $devices        = array();
    private $workId         = array();

    function __construct($email, $password, $country, $language,$access_token=Null,$session_id=Null)
    {
        $this->country      = $country;
        $this->language     = $language;
        $this->email        = $email;
        $this->password     = $password;
        $this->access_token = $access_token;
        $this->session_id   = $session_id;
    }

    function lgedm_post($url = '', $data = array(), $add_headers = Null)
    {

        $success = false;
        $try = 1;
        $result = Null;

        $json_request = $this->generate_json_request($data);

        do {

            $headers = array(
                'x-thinq-application-key: ' . $this->APP_KEY,
                'x-thinq-security-key: ' . $this->SECURITY_KEY,
                'Accept: application/json',
                'Content-Type:application/json',
            );

            if (isset($add_headers)) {
                array_push($headers, $add_headers);
            }

            if (isset($this->access_token)) {
                #debmes($this->access_token, 'lgsmarthinq');
                array_push($headers, 'x-thinq-token: ' . $this->access_token);
            }

            if (isset($this->session_id)) {
                #debmes($this->session_id, 'lgsmarthinq');
                array_push($headers, 'x-thinq-jsessionId: ' . $this->session_id);
            }

            debmes($headers, 'lgsmarthinq');
            debmes($url, 'lgsmarthinq');
            debmes($json_request, 'lgsmarthinq');
            #echo "\n";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);
            #print_r($response);
            #echo "\n";
            $result = json_decode($response);
            debmes($result, 'lgsmarthinq');
            $data_root = $this->DATA_ROOT;
            $code = $result->$data_root->returnCd;
            if ($code == "0102" || $code == "9003") {
                if ($code == '9003') {
                    debmes("Session creation failure", 'lgsmarthinq');
                } else {
                    debmes($response, 'lgsmarthinq');
                    debmes($result->$data_root->returnMsg, 'lgsmarthinq');
                }
                #$this->update_access_token();
                $this->login();
                #return Null;
            } else if ($code == '0000') {
                $success = true;
            } else {
                #debmes("Error response: $response", 'lgsmarthinq');
                #debmes("Do request againg. Try: $try", 'lgsmarthinq');
                echo $response;
                echo "\n";
            }
            $try = $try + 1;
        } while (!$success && $try <= 10);
        return $result->$data_root;
    }

    function update_access_token()
    {
        $this->check_gateway();
        $url      = $this->oauth_url();
        #echo $url;
        $login    = $this->email;
        $password = $this->password;
        #debmes("Start login via selenium", 'lgsmarthinq');
        #debmes($url, 'lgsmarthinq');
        #debmes($login, 'lgsmarthinq');
        #debmes($password, 'lgsmarthinq');
        #ini_set('max_execution_time', '300');
        $command = "/usr/bin/python3 /var/www/html/modules/LGsmartthinq/login.py --url '$url' --login '$login' --password '$password'";
        debmes($command, 'lgsmarthinq');
        #echo $command;
        #$command = '';
        $result = exec ($command);
        #echo $result;
        debmes("result command: ".$result, 'lgsmarthinq');
        $json = json_decode($result);
        $this->set_access_token((string)$json->access_token);
        $this->set_refresh_token((string)$json->refresh_token);
        debmes($json, 'lgsmarthinq');
        return (string)$this->access_token;
    }

    function generate_json_request($data = Null)
    {
        $json = array(
            $this->DATA_ROOT => $data
        );
        return json_encode($json);
    }

    function gateway_info()
    {
        return $this->lgedm_post($this->GATEWAY_URL, array('countryCode' => $this->country, 'langCode' => $this->language));
    }

    function set_gateway()
    {
        $response = $this->gateway_info();
        $this->auth_base = $response->empUri;
        $this->api_root = $response->thinqUri;
        $this->oauth_root = $response->oauthUri;
        $this->auth_base = $response->empUri;
    }

    function check_gateway()
    {
        if (!isset($this->auth_base) || !isset($this->api_root) || !isset($this->oauth_root) || !isset($this->auth_base)) {
            #debmes("Set GateWays", 'lgsmarthinq');
            $this->set_gateway();
        }
    }

    function oauth_url()
    {
        $url = $this->auth_base . '/login/sign_in';
        $params = array(
            'country' => $this->country,
            'language' => $this->language,
            'svcCode' => $this->SVC_CODE,
            'authSvr' => 'oauth2',
            'client_id' => $this->CLIENT_ID,
            'division' => 'ha',
            'grant_type' => 'password',
        );
        return "$url?" . http_build_query($params);
    }

    function set_access_token($access_token = Null)
    {
        $this->access_token = $access_token;
        #debmes("new access_token: '$access_token'", 'lgsmarthinq');
    }

    function set_refresh_token($refresh_token = Null)
    {
        $this->refresh_token = $refresh_token;
        #debmes("new refresh_token: '$refresh_token'", 'lgsmarthinq');
    }

    function get_refresh_token()
    {
        return $this->refresh_token;
    }

    function get_session_id()
    {
        return $this->session_id;
    }

    function set_session_id($session_id=Null)
    {
        if ( isset($session_id) ) {
            $this->session_id = $session_id;
        }
        return $this->session_id;
    }

    function get_access_token()
    {
        return $this->access_token;
    }

    function login()
    {
        $this->check_gateway();

        $url = $this->api_root . "/member/login";

        $headers = array(
            'x-thinq-application-key: ' . $this->APP_KEY,
            'x-thinq-security-key: ' . $this->SECURITY_KEY,
            'Accept: application/json',
            'Content-Type:application/json',
        );

        $data = array(
            'countryCode' => $this->country,
            'langCode' => $this->language,
            'loginType' => 'EMP',
            'token' => $this->access_token,
        );

        $json_request = $this->generate_json_request($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data_root = $this->DATA_ROOT;
        $json = json_decode($response);
        $result = $json->$data_root;
        $this->set_session_id((string)$result->jsessionId);
        #debmes((string)$result->jsessionId, 'lgsmarthinq');
        $this->devices = $this->get_items($result);
        return $result;
    }

    function get_items($response)
    {
        $result = array();
        array_push($result, $response->item);
        return $result;
    }

    function get_devices()
    {
        return $this->devices;
    }

    function set_devices()
    {
        $this->check_gateway();
        $url = $this->api_root . "/device/deviceList";
        $data = array();
        $result = $this->lgedm_post($url, $data);
        $this->devices = $this->get_items($result);
        return $this->devices;
    }

    function set_api_property($property, $value)
    {
        $this->$property = $value;
        #debmes("SEt api property '$property' => ".$this->$property, 'lgsmarthinq');
    }

    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    function monitor_start($device_id){
        $this->check_gateway();
        $url = $this->api_root . "/rti/rtiMon";
        $data = array(
            'cmd'       => 'Mon',
            'cmdOpt'    => 'Start',
            'deviceId'  => $device_id,
            'workId'    => $this->gen_uuid(),
        );
        $result = $this->lgedm_post($url, $data);
        #debmes($result, 'lgsmarthinq');
        $this->workId[$device_id] = $result->workId;
        return $result->workId;
    }

    function get_device_work_id($device_id){
        return $this->workId[$device_id];
    }

    function monitor_result ($device_id) {
        $this->check_gateway();
        $url = $this->api_root . '/rti/rtiResult';
        $data = array(
            'workList' => array (
                array (
                    'deviceId' => $device_id,
                    'workId'   => $this->get_device_work_id($device_id),
                ),
            ),
        );
        $response = $this->lgedm_post($url, $data);
        $code = $response->returnCd;
        $result = Null;
        if ( $code == '0000' && $response->workList ) {
            $result = $response->workList;
        }
        return $result;
    }

    function monitor_stop($device_id){
        $this->check_gateway();
        $url = $this->api_root . "/rti/rtiMon";
        $data = array(
            'cmd'       => 'Mon',
            'cmdOpt'    => 'Stop',
            'deviceId'  => $device_id,
            'workId'    => $this->get_device_work_id($device_id),
        );
        $result = $this->lgedm_post($url, $data);
        return $result;
    }

    function send_command($device_id, $category, $command, $value){
        # params can be:
        # $category     $command        $value              $data
        # Config        Get             "<something>"       ''
        # Control       Operation       Start               'DAECAgEAAAAAAAAAAAA=' bit program
        # Control       Operation       Stop                ''
        # Control       Set
        $this->check_gateway();
        $url = $this->api_root . "/rti/rtiControl";
        $data = array(
            'cmd'       => $category,
            'cmdOpt'    => $command,
            'value'     => $value,
            'deviceId'  => $device_id,
            'workId'    => $this->gen_uuid(),
            'data'      => '',
            "format"    => "B64",
        );
        $response = $this->lgedm_post($url, $data);
        print_r($response);
        echo "\n";
        $code = $response->returnCd;
        $result = Null;
        if ( $code == '0000' ) {
            $result = $response;
            if ( $result->format == 'B64' ) {
                $result->decoded_data = $this->decode_data($result->returnData);
            }
        }
        return $result;
    }

    function decode_data($configuration, $data) {
        $data = base64_decode($data);
        $params = $configuration->Monitoring->protocol;
        $decoded = array();
        debmes($data);
        foreach ( $params as $param ) {
            $key = $param->value;
            $start_byte = $param->startByte;
            $len = $param->length;
            $value = ord(substr($data, $start_byte, $len));
            $decoded[$key] = (string)$value;
        }
        $result = array();
        foreach ( $decoded as $key=>$decoded_value ) {
            $value = 0 ;
            $item = $configuration->Value->$key;
            $type = $item->type;
            if ( $type == 'Enum') {
                $value = $item->option->$decoded_value;
            } else if ( $type == 'Range' ) {
                $value = $decoded_value;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    function get_device_configuration($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        #debmes($response, 'lgsmarthinq');
        $result = json_decode($response);
        return $result;
    }

}

?>