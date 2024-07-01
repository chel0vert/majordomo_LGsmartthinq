<?php

class LGAPI
{
	private $log = false;
    private $access_token;
    private $redirected_url;
    private $oauth2_backend_url;
    private $oauth_code;
    private $user_number;
    private $refresh_token;
    private $session_id;
    private $homeId;

    private $GATEWAY_URL = 'https://route.lgthinq.com:46030/v1/service/application/gateway-uri';
    private $API_KEY = 'VGhpblEyLjAgU0VSVklDRQ==';
    private $MESSAGE_ID = 'MJD';
    private $SECURITY_KEY = 'nuts_securitykey';
    private $DATA_ROOT = 'result';
    private $SVC_CODE = 'SVC202';
    private $SVC_PHASE = 'OP';
    private $CLIENT_ID = 'LGAO221A02';
    private $DATE_FORMAT = 'D, j M Y H:i:s +0000';
    private $APP_LEVEL = 'PRD';
    private $APP_OS = 'ANDROID';
    private $APP_TYPE = 'NUTS';
    private $APP_VER = '3.0.2100';
    private $auth_base = Null;
    private $api_root = Null;
    private $oauth_root = 'https://ru.lgeapi.com'; # теоретически может отличаться в зависимости от региона
    private $country = 'RU';
    private $language = 'ru-RU';
    private $devices = array();
    private $workId = array();
    private $error = Null;
    private $OAUTH_REDIRECT_URI  = 'https://kr.m.lgaccount.com/login/iabClose';

    function __construct($country, $language, $redirected_url=Null)
    {
        $this->country = $country;        //#[require]
        $this->language = $language;       //#[require]
        if ($redirected_url) {
            $this->redirected_url = $redirected_url; //#[require]
            $this->parse_redirected_url($redirected_url);
            $this->login();
        }
    }

    function parse_redirected_url($redirected_url){
        $query = parse_url($redirected_url, PHP_URL_QUERY);
        $key_value = explode ("&", $query);
        $result = array();
        foreach ($key_value as $item) {
            $array = explode("=", $item);
            $result[$array[0]] = isset($array[1]) ? $array[1] : '';
        }
        $this->set_api_property('user_number', isset($result['user_number']) ? $result['user_number'] : '');
        $this->set_api_property('oauth_code', isset($result['code']) ? $result['code'] : '');
        $this->set_api_property('oauth2_backend_url', urldecode(isset($result['oauth2_backend_url']) ? $result['code'] : ''));
        return $result;
    }


    function lgedm_post($url = '', $data = array(), $add_headers = Null, $data_root = Null)
    {

        $success = false;
        $try = 1;
        $result = Null;

        $json_request = $this->generate_json_request($data, $data_root);
        //$this->lglog($json_request);
        do {

            $headers = $this->headers($add_headers);

            $this->lglog($url);
            $this->lglog($headers);
            $this->lglog($json_request);
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
            #print_r($headers);
            #echo "\n";
            #print_r($url);
            #echo "\n";
            #print_r($response);
            #echo "\n";
            $result = json_decode($response);
            #$this->lglog($url);
            $this->lglog($result);
            if (!$data_root) {
                $data_root = $this->DATA_ROOT;
            }
			if (isset($result->resultCode)){
                $code = $result->$data_root->returnCd;
            }else $code = '';
            #echo $code;
            if ($code == "0102" || $code == "9003" ) {
                if ($code == '9003') {
                    $this->lglog("Session creation failure");
                } else {
                    $this->lglog($json_request);
                    $this->lglog($response);
                    $this->lglog($result->$data_root->returnMsg);
                }
                $this->update_access_token();
                #$this->login();
                $this->set_api_error($response);
            } else if ($code == '0000') {
                $success = true;
                $this->set_api_error(Null); # unset error
            } else if ($code == '0106') {
                $success = true;
                $this->lglog('Is not connected');
                $this->set_api_error(Null); # unset error
            } else {
                $this->set_api_error($response);
                $this->lglog("url: $url");
                $this->lglog("Error response: $response");
                $this->lglog("Do request againg. Try: $try");
                #echo $response;
                #echo "\n";
            }
            $try = $try + 1;
        } while (!$success && $try <= 10);
        return $result->$data_root;
    }

    function lgedm_get($url = '', $data = array(), $add_headers = Null)
    {

        $success = false;
        $try = 1;
        $result = Null;

        $json_request = $this->generate_json_request($data);

        do {

            $headers = $this->headers($add_headers);

            #$this->lglog($headers);
            #$this->lglog($url);
            #$this->lglog($json_request);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);
            #print_r($headers);
            #echo "\n";
            #print_r($url);
            #echo "\n";
            #print_r($response);
            #echo "\n";
            $result = json_decode($response);
            #$this->lglog($url);
            #$this->lglog($result);
            $data_root = $this->DATA_ROOT;
            $code = $result->resultCode;
            if ($code == "0102" || $code == "9003") {
                if ($code == '9003') {
                    $this->lglog("Session creation failure");
                } else {
                    $this->lglog($json_request);
                    $this->lglog($response);
                    $this->lglog($result->$data_root->returnMsg);
                }
                $this->update_access_token();
                $this->login();
                $this->set_api_error($response);
            } else if ($code == '0000') {
                $success = true;
                $this->set_api_error(Null); # unset error
            } else {
                $this->set_api_error($response);
                $this->lglog("url: $url");
                $this->lglog("Error response: $response");
                $this->lglog("Do request againg. Try: $try");
                #echo $response;
                #echo "\n";
            }
            $try = $try + 1;
        } while (!$success && $try <= 10);
        return $result->$data_root;
    }

    function headers($headers)
    {
        $result = array(
            'Accept:application/json',
            'Content-type: application/json;charset=UTF-8',
            'x-api-key:' . $this->API_KEY,
            'x-client-id:' . $this->CLIENT_ID,
            'x-country-code:' . $this->country,
            'x-language-code:' . $this->language,
            'x-message-id:' . $this->MESSAGE_ID,
            'x-service-code:' . $this->SVC_CODE,
            'x-service-phase:' . $this->SVC_PHASE,
            'x-thinq-app-level:' . $this->APP_LEVEL,
            'x-thinq-app-os:' . $this->APP_OS,
            'x-thinq-app-type:' . $this->APP_TYPE,
            'x-thinq-app-ver:' . $this->APP_VER,
            'x-thinq-security-key:' . $this->SECURITY_KEY,
        );
        if (isset($headers)) {
            array_push($result, $headers);
        }
        if (isset($this->access_token)) {
            #$this->lglog($this->access_token);
            array_push($result, 'x-emp-token: ' . $this->access_token);
        }

        if (isset($this->user_number)) {
            #$this->lglog($this->session_id);
            array_push($result, 'x-user-no: ' . $this->user_number);
        }
        return $result;
    }

    function update_access_token()
    {
        $this->check_gateway();
        $refresh_token = $this->get_refresh_token();
        if ($refresh_token) {
            $access_token = $this->get_new_access_token($refresh_token);
            $this->set_access_token($access_token);
        } else {
            $this->lglog('No refresh token');
            echo "No refresh token\n";
        }
        return $this->get_access_token();
    }

    function generate_json_request($data = Null, $data_root = Null)
    {
        if (!$data_root) {
            $data_root = $this->DATA_ROOT;
        }
        $json = array(
            $data_root => $data
        );
        return json_encode($json);
    }

    function gateway_info()
    {
        return $this->lgedm_get($this->GATEWAY_URL, array('x-country-code' => $this->country, 'x-language-code' => $this->language));
    }

    function set_gateway()
    {
        $response = $this->gateway_info();
        $this->auth_base = $response->empUri;
        $this->api_devices_root = $response->thinq1Uri;
        $this->api_root = $response->thinq2Uri;
        #$this->oauth_root = $response->empUri;
    }

    function check_gateway()
    {
        if (!isset($this->auth_base) || !isset($this->api_root) || !isset($this->oauth2_backend_url)) {
            #$this->lglog("Set GateWays"');
            $this->set_gateway();
        }
    }

    function oauth_url()
    {
        $url = $this->auth_base . '/spx/login/signIn';
        $params = array(
            'country' => $this->country,
            'language' => $this->language,
            'svc_list' => $this->SVC_CODE,
            'client_id' => $this->CLIENT_ID,
            'division' => 'ha',
            'state' => '56f9761ecb1943aa9217e455d1fb06a9',
            'show_thirdparty_login' => 'GGL,AMZ,FBK',
            'redirect_uri' => 'https://kr.m.lgaccount.com/login/iabClose'
        );
        return "$url?" . http_build_query($params);
    }

    function user_name_url()
    {
        $url = $this->auth_base . '/spx/login/signIn';
        $params = array(
            'country' => $this->country,
            'language' => $this->language,
            'svc_list' => $this->SVC_CODE,
            'client_id' => $this->CLIENT_ID,
            'division' => 'ha',
            'state' => '56f9761ecb1943aa9217e455d1fb06a9',
            'show_thirdparty_login' => 'GGL,AMZ,FBK',
            'redirect_uri' => 'https://kr.m.lgaccount.com/login/iabClose'
        );
        return "$url?" . http_build_query($params);
    }

    function set_access_token($access_token = Null)
    {
        $this->access_token = $access_token;
    }

    function set_refresh_token($refresh_token = Null)
    {
        $this->refresh_token = $refresh_token;
    }

    function get_refresh_token()
    {
        return $this->refresh_token;
    }

    function get_session_id()
    {
        return $this->session_id;
    }

    function set_session_id($session_id = Null)
    {
        if (isset($session_id)) {
            $this->session_id = $session_id;
        }
        return $this->session_id;
    }

    function get_access_token()
    {
        return $this->access_token;
    }

    function update_session_id($data_root=Null)
    {
        if (!$data_root) {
            $data_root = $this->DATA_ROOT;
        }

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

        return $result;
    }

    function login()
    {
        $this->check_gateway();

        if (!$this->oauth2_backend_url) {
            $values = $this->parse_redirected_url($this->redirected_url);
            $this->oauth2_backend_url = urldecode(isset($values['oauth2_backend_url']) ? $values['oauth2_backend_url'] : '');
        }

        $url = $this->oauth2_backend_url . "oauth/1.0/oauth2/token";

        $headers = array(
            'x-lge-appkey: '. $this->CLIENT_ID,
            'x-lge-oauth-signature: ',
            'x-lge-oauth-date: ' . $this->oauth2_datetime(),
            'Accept: application/json',
        );

        $data = array(
            'code' => $this->oauth_code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->OAUTH_REDIRECT_URI,
        );
        $json_request = $this->generate_json_request($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);
        if (!isset($json->access_token) || !isset($json->refresh_token)) {
            $message = "Can not get new access token. Please add new redirected url\n$response";
            $this->lglog($message);
            echo "$message\n";
        } else {
            $this->set_api_property('access_token', $json->access_token);
            $this->set_api_property('refresh_token', $json->refresh_token);
        }
        return $json;
    }

    function get_new_access_token($refresh_token = Null)
    {
        if (!$refresh_token) {
            $this->lglog('Can not get access token: No refresh token');
            echo "Can not get access token: No refresh token\n";
            return Null;
        }
        $result = Null;

        if (!$this->oauth2_backend_url) {
            $values = $this->parse_redirected_url($this->redirected_url);
            $this->oauth2_backend_url = urldecode($values['oauth2_backend_url']);
        }

        $url = $this->oauth2_backend_url . "/oauth/1.0/oauth2/token";
        $this->lglog($url);

        $headers = array(
            'Accept: application/json',
            'x-lge-oauth-date: ' . $this->oauth2_datetime(),
            "x-lge-appkey: LGAO221A02",
            'x-lge-oauth-signature: ',
            'X-Requested-With: com.lgeha.nuts',
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        );
        #print_r($url);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=refresh_token&refresh_token=$refresh_token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        #print_r($response);
        #$this->lglog($response);
        curl_close($ch);
        $json = json_decode($response);
        $this->lglog($json);
        if (isset($json->error)) {
            $this->lglog("Can not get access token: \n" .
                "headers:\n" . print_r($headers, 1) .
                "\nParams: \"grant_type=refresh_token&refresh_token=$refresh_token\"\n" .
                "\nresponse:\n" . $response);
        }
        $result = $json->access_token;
        if ( $result ) {
            $this->set_api_property('access_token', $result);
        }
        $this->lglog($result);
        return $result;
    }

    function oauth2_datetime()
    {
        $result = date($this->DATE_FORMAT, time() - date("Z"));
        #$this->lglog("Date:".$result);
        return $result;
    }

    function get_items($response)
    {
        return $response->devices;
    }

    function get_devices()
    {
        return $this->devices;
    }
/*
    function set_devices()
    {
        $this->check_gateway();
        $url = $this->api_root . "/service/application/dashboard";
        $data = array();

        $result = $this->lgedm_get($url, $data);
        if ($result && count($result->item) > 0) {
            $this->devices = $this->get_items($result);
        } else {
            $this->devices = Null;
        }
        return $this->devices;
    }
*/

    function set_devices()
    {
        $this->check_gateway();
        $homes = $this->get_homes();
        if (is_array($homes)) {
            $devices = array();
            foreach($homes as $home) {
                $homeId = $home->homeId;
                $url = $this->api_root . "/service/homes/" . $homeId;
                $data = array();
                $result = $this->lgedm_get($url, $data);
				//print_r( $result);
                if ($result && count($result->devices) > 0) {
                    $home_devices = $this->get_items($result);
                    $devices = array_merge($devices, $home_devices);
                }
            }
            if (count($devices)) {
                $this->devices = $devices;
            } else {
                $this->devices = Null;
            }
        }
        return $this->devices;
    }

    function get_homes() {
        $this->check_gateway();
        $url = $this->api_root . "/service/homes";
        $data = array();
        $result = $this->lgedm_get($url, $data);
		$homes = isset($result->item) ? $result->item : '';
        if (!is_array($homes)) {
            $homes = Null;
            $this->lglog("No homes found");
        }
        return $homes;
    }

    function get_user_number(){
        return $this->user_number;
    }
/*
    function get_user_number(){
        $url = $this->oauth2_backend_url . "/users/profile";
        $this->lglog($url);

        $access_token = $this->get_access_token();
        $headers = array(
            #'Authorization: Bearer '.$access_token,
            'Authorization: Bearer a5ba1fb9530b897c86d4b77704622ba0a6f724c0fedab798ce85c51a715818516293aaf9eb677bd631eed4b877aa388b',
            'Accept: application/json',
            #'x-lge-oauth-date: ' . $this->oauth2_datetime(),
            'x-lge-oauth-signature: ',
            'x-lge-oauth-date: ',
            'X-Requested-With: com.lgeha.nuts',
            "x-lge-appkey: LGAO221A02",
            'X-Device-Type: M01',
            'X-Device-Platform:	ADR',
            'X-Application-Key: '.$this->OAUTH_CLIENT_KEY,
            'lgemp-x-app-key: '.$this->OAUTH_CLIENT_KEY,
            'X-Lge-Svccode: '. $this->SVC_CODE,
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
        );
        #$date = 'Tue, 28 Apr 2020 08:35:47 +0000';#$this->oauth2_datetime();
        $date = $this->oauth2_datetime();
        $signature = $this->signature($url, $date);
        $headers = array(
            #'x-lge-oauth-signature: +gxRvBEtIGaABnrZ32xkE11saWk=',
            #'x-lge-oauth-date: Tue, 28 Apr 2020 08:35:47 +0000',
            'x-lge-oauth-signature: '.$signature,
            'x-lge-oauth-date: '. $date,
            'Authorization: Bearer a5ba1fb9530b897c86d4b77704622ba0a6f724c0fedab798ce85c51a715818516293aaf9eb677bd631eed4b877aa388b',
            'X-Device-Type: M01',
            'X-Device-Platform: ADR',
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'X-Lge-Svccode: SVC202',
            'X-Application-Key: LGAO221A02',
            'lgemp-x-app-key: LGAO221A02',
            'User-Agent: Dalvik/2.1.0 (Linux; U; Android 5.1; M3s Build/LMY47I)',
            'Host: ru.lgeapi.com',
            'Connection: Keep-Alive',
            'Accept-Encoding: gzip',
            'Pragma: no-cache',
            'Cache-Control: no-cache',
        );
        $this->lglog($headers);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        #curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        #curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        #$this->lglog($response);
        curl_close($ch);
        $json = json_decode($response);
        $this->lglog($json);
        if ($json->error) {
            $this->lglog("Can not get access token: \n" .
                "headers:\n" . print_r($headers, 1) .
                "\nParams: \"grant_type=refresh_token&refresh_token=$refresh_token\"\n" .
                "\nresponse:\n" . $response);
        }
        $result = $json->access_token;
        $this->lglog($result);
        $this->lglog($result);
    }
*/
    function signature($url, $date){
        $message = $url."\n".$date;
        $secret = $this->OAUTH_SECRET_KEY;
        $secret_bytes = utf8_encode($secret);
        $message_bytes = utf8_encode($message);
        $result = hash_hmac('sha1', $message_bytes, $secret_bytes, true);
        $result = base64_encode($result);
        $this->lglog($result);
        return $result;
    }


    function set_api_property($property, $value)
    {
        $this->$property = $value;
        #$this->lglog("SEt api property '$property' => ".$this->$property);
    }

    function gen_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    function monitor_start($device_id)
    {
        $this->check_gateway();
        $url = $this->api_devices_root . "/rti/rtiMon";
        $data = array(
            'cmd' => 'Mon',
            'cmdOpt' => 'Start',
            'deviceId' => $device_id,
            'workId' => $this->gen_uuid(),
        );
        $result = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        $this->lglog($result);
        $this->workId[$device_id] = isset($result->workId) ? $result->workId : '';
        return $this->workId[$device_id];
    }

    function get_device_work_id($device_id)
    {
        $result = $this->workId[$device_id];
        if (!$result) {
            $result = $this->gen_uuid();
        }
        return $result;
    }

    function monitor_result($device_id)
    {
        $this->check_gateway();
        $url = $this->api_devices_root . '/rti/rtiResult';
        $data = array(
            'workList' => array(
                array(
                    'deviceId' => $device_id,
                    'workId' => $this->get_device_work_id($device_id),
                ),
            ),
        );
        $response = $this->lgedm_post($url, $data,Null, 'lgedmRoot');
        $code = $response->returnCd;
        $result = Null;
		$workList = isset($response->workList) ? $response->workList : '';
        if ($code == '0000' && $workList != '') {
            $result = $response->workList;
        }
        return $result;
    }

    function monitor_stop($device_id)
    {
        $this->check_gateway();
        $url = $this->api_devices_root . "/rti/rtiMon";
        $data = array(
            'cmd' => 'Mon',
            'cmdOpt' => 'Stop',
            'deviceId' => $device_id,
            'workId' => $this->get_device_work_id($device_id),
        );
        $result = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        return $result;
    }

    function get_device_config($device, $category = Null, $command='Get', $value='')
    {
        # params can be:
        # $category     $command        $value              $data
        # Config        Get             "<something>"       ''
        # Control       Operation       Start               'DAECAgEAAAAAAAAAAAA=' bit program
        # Control       Operation       Stop                ''
        # Control       Set
        $this->check_gateway();
        $url = $this->api_devices_root . "/rti/rtiControl";

        $data = array(
            'cmd' => $category,
            'cmdOpt' => $command,
            'value' => $value,
            'deviceId' => $device->deviceId,
            'workId' => $this->gen_uuid(),
            'data' => '',
        );

        $response = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        //print_r($response);
        //echo "\n";
        $code = $response->returnCd;
        $result = Null;
        if ($code == '0000') {
            $result = $response;
        }
        return $result;
    }

    function send_command($device, $category = Null, $command='Get', $value, $params)
    {
        # params can be:
        # $category     $command        $value              $data
        # Config        Get             "<something>"       ''
        # Control       Operation       Start               'DAECAgEAAAAAAAAAAAA=' bit program
        # Control       Operation       Stop                ''
        # Control       Set
        $this->check_gateway();
        $url = $this->api_devices_root . "/rti/rtiControl";

        $data = array(
            'cmd' => $category,
            'cmdOpt' => $command,
            'value' => $value,
            'deviceId' => $device->deviceId,
            'workId' => $this->gen_uuid(),
            'data' => '',
            "format" => "B64",
        );

        if ($device->Programm) {
            $send_data = $this->make_start_programm($device, $device->Programm, $params);
            #$this->lglog("Data: ".$send_data);
            $data['data'] = $send_data;
        }

        $response = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        #print_r($response);
        #echo "\n";
        $code = $response->returnCd;
        $result = Null;
        if ($code == '0000') {
            $result = $response;
            if ($result->format == 'B64') {
                $result->decoded_data = $this->decode_data($device, $result->returnData);
            }
        }
        return $result;
    }

    function start_command($device, $category, $command, $value, $params)
    {
        $workId = $this->monitor_start($device->deviceId);
        if ($workId) {
            $this->monitor_result($device->deviceId);
            $result = $this->send_command($device, $category, $command, $value, $params);
            $this->monitor_stop($device->deviceId);
        } else {
            $this->lglog('Can not start monitor');
        }
        return $result;
    }

    function delete_permission_command($device)
    {
        $this->check_gateway();
        $url = $this->api_devices_root . "/rti/delControlPermission";

        $data = array(
            'deviceId' => $device->deviceId,
        );

        $response = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        $code = $response->returnCd;
        $result = Null;
        if ($code == '0000') {
            $result = $response;
        }
        #$this->lglog($response);
        return $result;
    }

    function update_course_command($device, $params = array())
    {
        /*Values can get from json config
                $params = array(
                    'Course'            => 0,
                    'Wash'              => 0,
                    'SpinSpeed'         => 0,
                    'WaterTemp'         => 0,
                    'RinseOption'       => 0,
                    'Reserve_Time_H'    => 0,
                    'Reserve_Time_M'    => 0,
                    'LoadItem'          => 0,
                    'Option1'           => 0,
                    'Option2'           => 0,
                    'SmartCourse'       => 0,
                );
        */
        $this->check_gateway();
        $url = $this->api_devices_root . "/washer/courseUpdate";

        $data = array(
            'deviceId' => $device->deviceId,
            'courseData' => $this->gen_custom_course($device, $params),
            'selectedCd' => $device->Course,
        );

        #$this->lglog($data);
        $response = $this->lgedm_post($url, $data, Null, 'lgedmRoot');
        $code = $response->returnCd;
        $result = Null;
        if ($code == '0000') {
            $result = $response;
        }
        #$this->lglog($response);
        return $data;
    }

    function gen_custom_course($device, $params)
    { # course = 3 is 'My programm'
        $data = $this->pack_course($device, $params);
        if ($data) {
            $xml = new SimpleXMLElement('<COURSE/>');
            $xml->addChild('DATA', $this->pack_course($device, $params));
            $xml->addChild('ID', $device->Course);
            $NAME = $xml->addChild('NAME', '');
            $NAME->addChild('EN', 'My programm');
            $DESCRIPTION = $xml->addChild('DESCRIPTION', '');
            $DESCRIPTION->addChild('EN', 'Custom programm');
            $result = $xml->asXML();
            #$this->lglog($result);
        } else {
            $this->lglog('can not create custom programm');
        }
        return base64_encode($result);
    }

    function decode_data($device, $data)
    {
        #$this->lglog($data);
        $configuration = $this->get_device_configuration($device);
        #$localization  = $this->get_device_localization($device);
        #$this->lglog('local');
        #$this->lglog($localization);
        #$locale = $this->country;
        $data = base64_decode($data);
        $params = $configuration->Monitoring->protocol;
        $decoded = array();
        foreach ($params as $param) {
            $key = $param->value;
            $start_byte = $param->startByte;
            $len = $param->length;
            $value = ord(substr($data, $start_byte, $len));
            $decoded[$key] = (string)$value;
        }
        $result = array();
        foreach ($decoded as $key => $decoded_value) {
            $value = 0;
            $item = $configuration->Value->$key;
            $type = $item->type;
            $id = $decoded_value;
            if ($type == 'Enum') {
                $value = $item->option->$decoded_value;
            } else if ($type == 'Range') {
                $value = $decoded_value;
            } else if ($type == 'Reference') {
                $new_key = $item->option[0];
                if ($new_key) {
                    $new_item = $configuration->$new_key->$decoded_value;
                    if ($new_item && $new_item->name) {
                        $value = $new_item->name;
                    } else if ($new_item && $new_item->label) {
                        $value = $new_item->label;
                    }
                }
            } else if ($type == 'Bit') {
                $bits = array();
                for ($i = 0; $i < 8; $i++) {
                    $bits[$i] = (ord($decoded_value) & (1 << $i)) >> $i;
                }
                foreach ($item->option as $option) {
                    $bit_key = $option->value;
                    $id = $bits[(string)$option->startbit];
                    $new_item = $configuration->Value->$bit_key;
                    $value = $new_item->option->$id;
                    if (!$value) {
                        $value = $option->default;
                    }
                    if (!$value) {
                        $value = $id;
                    }
                    $result[$bit_key] = $value;
                    #$result[$bit_key."_ID"]    = $id;
                }
            }
            if ($key) {
                $result[$key] = $value;
            }
            if (isset($id)) {
                #$result[$key."_ID"] = $id;
            }
        }
        #$this->lglog($result);
        return $result;
    }


    function get_device_configuration($device)
    {
        $url = $device->modelJsonUrl;
        $type = $device->deviceType;
        $filename = __DIR__ . "/LGAPI_configuration_$type.json";
        if ($url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response) {
                $response = preg_replace('/^\x{feff}/u', '', $response);
                file_put_contents($filename, $response);
                #$this->lglog($response);
                $result = json_decode($response);
            }
        } else {
            if (file_exists($filename)) {
                $content = file_get_contents($filename);
                if ($content) {
                    $result = json_decode($content);
                }
            }
        }

        return $result;
    }

    function get_device_localization($device)
    {

        $type = $device->deviceType;
        $filename = __DIR__ . "/LGAPI_localization_$type.json";
        if (file_exists($filename) && (time() - filemtime($filename)) <= (24 * 60 * 60)) {
            $content = file_get_contents($filename);
            if ($content) {
                $result = json_decode($content);
            }
        }

        $url = $device->langPackProductTypeUri;
        #$this->lglog("lang url $url");
        if (!$result && $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response) {
                $response = preg_replace('/^\x{feff}/u', '', $response);
                $result = json_decode($response);
                $url = $device->langPackModelUri;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $response = curl_exec($ch);
                curl_close($ch);
                $response = preg_replace('/^\x{feff}/u', '', $response);
                $langPackModelUri = json_decode($response);
                foreach ($langPackModelUri->pack as $key => $value) {
                    $result->pack->$key = $value;
                }
                $content = json_encode($result);
                file_put_contents($filename, $content);
                #$this->lglog($response);

            }
        }
        #$this->lglog($result->pack->"@WM_TITAN2_OPTION_ECO_HYBRID_W");
        return $result;
    }

    function get_api_error()
    {
        return $this->error;
    }

    function set_api_error($error)
    {
        return $this->error;
    }

    function make_start_programm($device, $course, $params)
    { #course=program
        $config = $this->get_device_configuration($device);
        $template = $config->ControlWifi->action->OperationStart->data;
        #$this->lglog("make_programm: template = $template");
        #$this->lglog("make_programm: config");
        #$this->lglog($config);
        $course_config = $config->Course->$course;
        if (!$course_config) {
            $course_config = $config->SmartCourse->$course;
        }

        if ($course_config) {
            if (isset($params)) {
                $result = $this->pack_course($device, $params);
            } else {
                $course_name = $course_config->name;
                #$this->lglog("make_programm: Course name = $course_name");
                $items = $course_config->function;
                $params = array();
                foreach ($items as $item) {
                    $name = $item->value;
                    $value = $item->default;
                    $params[$name] = $value;
                }
                $params['Course'] = $course;
                $params['Option2'] = 3;
                $result = $this->pack_course($device, $params);
                #$this->lglog($result);
            }
        } else {
            $this->lglog("make_programm: No course = $course");
        }
        return $result;
    }

    function pack_course($device, $course_params = array())
    {
        $config = $this->get_device_configuration($device);
        $template = $config->ControlWifi->action->OperationStart->data;
        $result = Null;
        foreach ($course_params as $key => $value) {
            if (!$value) {
                $value = 0;
            }
            $template = preg_replace("/\{\{$key\}\}/", "$value", $template);
        }
        $template = preg_replace("/\{\{\w+\}\}/", 0, $template);
        $template = preg_replace("/^\[|\]$/", '', $template);
        #$this->lglog($template);
        $array = preg_split("/,/", $template);
        foreach ($array as $byte_number => $byte) {
            $result = $result . pack("C*", $byte);
        }

        if ($result) {
            $result = base64_encode($result);
        }
        return $result;
    }

    function check_device_online_status($device)
    {
        $type = $device->deviceType;
        if ($type == 201) { # washer

        }
    }
	
	function lglog($message){
		if($this->log){
			debmes($message, 'lgsmarthinq');
		}
	}

}
?>