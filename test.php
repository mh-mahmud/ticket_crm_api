<?php

class TicketingSystemAPI{

    private const BASE_URL_BACKEND  = 'http://192.168.21.231/ticketApi/api/';
    private const BASE_URL_FRONTEND = 'http://192.168.21.231/ticket/';
    private const CUSTOMER_DETAILS_BY_CID = 'http://192.168.21.231/ccmw/account/common-api-function?sercret=PVFzWnlWQmJsdkNxQUszcWJrbFlUNjJVREpVMXR6R09kTHN5QXNHYSt1ZWM=&rm=T&callid=61657894123478512478&connname=MWCSINCI&cli=<CLI>&cid=<TICKET_CID>';
    private const CARD_DETAILS_BY_CID = 'http://192.168.21.231/ccmwdev/card/card-info-by-customer-id?sercret=PVFzWnlWQmJsdkNxQUszcWJrbFlUNjJVREpVMXR6R09kTHN5QXNHYSt1ZWM=&rm=T&callid=61657894123478512478&connname=MWCRDINF&cli=<CLI>&csid=<TICKET_CID>&terid=1001';
    private const USERNAME = 'crm_api_user';
    private const PASSWORD = '@A_bhk87##';
    private const CUSTOMER_DETAILS_MOBILE_EMAIL = 'http://192.168.21.231/ccpro/urmi_crm_api.php?type=GetCustomerInfo&CLI=<TICKET_MOBLIE>';

    public function getTicketListByMobile($requestUrl, Array $data){
            
        $headers = array(
            "Content-Type: application/json",
            "Connection: keep-alive",
            "username: " . self::USERNAME,
            "password: " . self::PASSWORD
        );

        $data['requestTimestamp'] = time();
        $url 	= self::BASE_URL_BACKEND . $requestUrl . '?' . http_build_query($data);
        $result = $this->curlRequest($headers, $url, "GET");
        return json_encode($result['data']);

    }

    public function getTicketReplyIframe($ticket_id){

        $token = $this->getAuthToken();
        if($token){
            $iframe['iframe'] = '<iframe width="100%" height="800" style="border:1px solid black;" src="' . self::BASE_URL_FRONTEND . 'crm/crm-ticket-reply/' . $ticket_id . '/' . $token . '" title="Ticket Details"></iframe>';
        }else{
            $iframe['iframe'] = "Invalid Request !";
        }
        return json_encode([$iframe]);

    }

    public function getAgentInfo(){

		session_start();
		include_once('../ccpro/conf.php');
		return 'AGENT_ID:' . (isset($_SESSION[$db->db_suffix.'sesGCCUser']) ? $_SESSION[$db->db_suffix.'sesGCCUser'] : "_") . ':' . (isset($_SESSION[$db->db_suffix.'sesGCCUserFullName']) ? $_SESSION[$db->db_suffix.'sesGCCUserFullName'] : "_");
        
    }
	
    public function getTicketCreateIframe($cli=''){

        $token = $this->getAuthToken();
        if($token){
            $iframe['iframe'] = '<iframe width="100%" height="800" style="border:1px solid black;" src="' . self::BASE_URL_FRONTEND . 'crm/crm-create-ticket/' . $token . '/' . $cli . '" title="Create Ticket"></iframe>';
        }else{
            $iframe['iframe'] = "Invalid Request !";
        }
        return json_encode([$iframe]);

    }

    public function getAuthToken(){
        $url 	= self::BASE_URL_BACKEND . 'crm/get-auth-token';
        $headers = array(
            "Content-Type: application/json",
            "Connection: keep-alive",
            "username: " . self::USERNAME,
            "password: " . self::PASSWORD
        );
        $authData = $this->curlRequest($headers, $url, "GET");
        return $authData ?: null;
    }

    public function storeCustomerInfo($mobile, $firstName, $address){
	$customer_info = $this->curlRequest([], str_replace('<TICKET_MOBLIE>', $mobile, SELF::CUSTOMER_DETAILS_MOBILE_EMAIL), "GET");
	$data = [];

        if (!empty($customer_info) && array_key_exists("CustomerId", $customer_info[0]) && !empty($customer_info[0]["CustomerId"])) {
            $data['cif_id']	        = $customer_info[0]['CustomerId'];
            $data['first_name']	        = $customer_info[0]['CustomerName'];
            $data['address']	        = $customer_info[0]['Address'];
            $data['mobile']	        = $mobile;
            $data['customer_type']	    = 'R';
            $data['requestTimestamp']   = time();
            $headers = array(
                // "Content-Type: application/json",
                "Connection: keep-alive",
                "username: " . self::USERNAME,
                "password: " . self::PASSWORD
            );
            $result       = $customer_info[0];
            $custmrIdKeys = array_flip(preg_grep("/CustomerID[0-9]*/", array_keys($result)));
            $final        = [];
            array_walk($custmrIdKeys, function($item, $key)use($result, &$final, $custmrIdKeys){
                $final[]  = $result[$key];
            });
            /* If user not exist then create otherwise return user info */
            return ["user_info"=>$this->curlRequest($headers, self::BASE_URL_BACKEND . 'crm/createUser', "POST", $data), "customer_ids"=>$final];
        }else{

            // Non registered customer
            $data['first_name']	    = $firstName;
            $data['mobile']             = $mobile;
            $data['address']	    = $address;
            $data['customer_type']	= 'N';

            $data['requestTimestamp'] = time();
            $headers = array(
                // "Content-Type: application/json",
                "Connection: keep-alive",
                "username: " . self::USERNAME,
                "password: " . self::PASSWORD
            );
            return ["user_info"=>$this->curlRequest($headers, self::BASE_URL_BACKEND . 'crm/createUser', "POST", $data), "customer_ids"=>[]];
        }
        
    }

    public function getCustomerInfo($mobile){
        $customer_info = $this->curlRequest([], str_replace('<TICKET_MOBLIE>', $mobile, SELF::CUSTOMER_DETAILS_MOBILE_EMAIL), "GET");
        if (!empty($customer_info) && array_key_exists("CustomerId", $customer_info[0]) && !empty($customer_info[0]["CustomerId"])) {
            $data['first_name']	        = $customer_info[0]['CustomerName'];
            $data['address']	        = $customer_info[0]['Address'];
            return $data;
        }

    }
	
   public function getCardInfoByCid($customerId){
        $card_info = $this->curlRequest([], str_replace('<TICKET_CID>', $customerId, SELF::CARD_DETAILS_BY_CID), "GET");
        
        if(
            is_array($card_info) && 
            array_key_exists('gcode', $card_info[0])
        ){

            if( $card_info[0]['gcode'] == 200 && array_key_exists('responseData', $card_info[0]) ){
                return array_column( $card_info[0]['responseData'], 'value' );
            }
            return [];

        }
    }

    private function curlRequest($headers, $url, $method, Array $data=null) {

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if($data){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($curl);

        if($data = $this->xmlToArray($response)){
            $result = $data;
        }elseif($data = $this->jsonToArray($response)){
            $result = $data;
        }else{
            $result = $response;
        }

        if(curl_errno($curl)){
            // throw the an Exception.
            // throw new Exception(curl_error($curl));
			$errorLog['url'] = $url;
			$errorLog['method'] = $method;
			$this->saveCurlErrorLog(json_encode($errorLog));
			$result['error'] =  curl_error($curl);
        }
        curl_close($curl);
        return $result;

    }

    public function jsonToArray($string) {
        $r = json_decode($string, 1);
        if(json_last_error() === JSON_ERROR_NONE){// No error
        return $r;
        }
        return false;
    }

    public function xmlToArray($xml){
        
        $xml = @simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        if($array = $this->jsonToArray( json_encode($xml), TRUE )){
            return $array;
        }
        return false;
        
    }
	
    public function saveCurlErrorLog($errData){
		$log_file = "/var/log/dblog/".date("y-m-d H:i:s") ."_curl_error.log";
		if (!file_exists($log_file)){
			touch($log_file);
		}
		file_put_contents($log_file, date("y-m-d H:i:s") . " " . $msg . "\n", FILE_APPEND | LOCK_EX);
		file_put_contents($log_file, "============================================================\n\n", FILE_APPEND | LOCK_EX);
        return null;
    }

}