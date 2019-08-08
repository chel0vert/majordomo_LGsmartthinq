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

    function __construct($email, $password, $country, $language)
    {
        $this->country = $country;
        $this->language = $language;
        $this->email = $email;
        $this->password = $password;
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

            debmes("Url: $url", 'lgsmarthinq');
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

            $result = json_decode($response);
            $data_root = $this->DATA_ROOT;
            $code = $result->$data_root->returnCd;
            if ($code == "0102" || $code == "9003") {
                if ($code == '9003') {
                    debmes("Session creation failure", 'lgsmarthinq');
                } else {
                    debmes($result->$data_root->returnMsg, 'lgsmarthinq');
                }
                $this->update_access_token();
                $this->login();
            } else if ($code == '0000') {
                $success = true;
            } else {
                debmes("Error response: $response", 'lgsmarthinq');
                debmes("Do request againg. Try: $try", 'lgsmarthinq');
            }
            $try = $try + 1;
        } while (!$success && $try <= 10);
        return $result->$data_root;
    }

    function update_access_token()
    {
        $url = $this->oauth_url();
        $login = $this->email;
        $password = $this->password;
        debmes("Start login via selenium", 'lgsmarthinq');
        $result = exec("python3 " . DIR_MODULES . "/LGsmartthinq/login.py --url '$url' --login '$login' --password '$password'");
        $json = json_decode($result);
        $this->set_access_token((string)$json->access_token);
        $this->set_refresh_token((string)$json->refresh_token);
        #debmes($json, 'lgsmarthinq');
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
            debmes("Set GateWays", 'lgsmarthinq');
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
        $this->session_id = (string)$result->jsessionId;
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

}

?>