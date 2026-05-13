<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once("TicketingSystemAPI.php");
$apiObj = new TicketingSystemAPI();
    $mobileNumber   = !empty($_REQUEST['CLI']) ? substr($_REQUEST['CLI'], -11) : "";
    $firstName      = !empty($_REQUEST['firstName']) ? $_REQUEST['firstName'] : "";
    $address        = !empty($_REQUEST['address']) ? $_REQUEST['address'] : "";
    $customerId     = !empty($_REQUEST['CID']) ? $_REQUEST['CID'] : "";
    $customerAcc    = !empty($_REQUEST['ACC']) ? $_REQUEST['ACC'] : "";
    $ticket_id      = !empty($_REQUEST['TICKET_ID']) ? $_REQUEST['TICKET_ID'] : "";
    $token          = !empty($_REQUEST['TOKEN']) ? $_REQUEST['TOKEN'] : "";
    $type           = !empty($_REQUEST['TYPE']) ? strtoupper($_REQUEST['TYPE']) : "";
  
    /* Get ticket list by mobile number */
    if($mobileNumber != '' && $type == "TICKET_LIST_BY_MOBILE"){
    
        echo $apiObj->getTicketListByMobile("crm/get-ticket-list", ['mobile' => $mobileNumber]);
    
    }
    
    if($ticket_id != '' && $type == "TICKET_REPLY"){
    
        echo $apiObj->getTicketReplyIframe($ticket_id);
    
    }
    
    if($type == "TICKET_CREATE_BUTTON"){
    
        echo json_encode([['create_ticket'=>'Create Ticket']]);
    
    }
    
    if($type == "GET_AGENT_INFO"){
        
        echo $apiObj->getAgentInfo();
    
    }
	
    if($type == "TICKET_CREATE"){
        
        if( !empty($mobileNumber) ){
            $apiObj->storeCustomerInfo($mobileNumber, $firstName, $address);
        }
    
        echo $apiObj->getTicketCreateIframe($mobileNumber);
    
    }
    
     if($type == "STORE_CUSTOMER"){
	if( !empty($mobileNumber) ){
            $res = $apiObj->storeCustomerInfo($mobileNumber, $firstName, $address);

            if( is_array($res) && array_key_exists('status', $res) && $res['status'] == 200 ){
                echo json_encode($res);
                return;
            }
        }
        echo json_encode($res);
        return;
    }

    if($type == "GET_CUSTOMER_BY_MOBILE"){
        $res = [];
        if( !empty($mobileNumber) ){
            $res = $apiObj->getCustomerInfo($mobileNumber);
             
        }
        echo json_encode($res);
        return;
    }

    // if($type == "GET_ACCOUNT_BY_ID"){
		
    //     if( !empty($customerId) ){
    //         $res = $apiObj->getAccountInfoByCid($customerId);
    //         if( is_array($res) && array_key_exists('status', $res) && $res['status'] == 200 ){
    //             echo json_encode($res);
    //             return;
    //         }
    //     }
    //     echo json_encode($res);
    //     return;
    // }


//    if($type == "GET_CARD_BY_ID"){

//         if( !empty($customerId) ){
//             $res = $apiObj->getCardInfoByCid($customerId);
//             if( is_array($res) && array_key_exists('status', $res) && $res['status'] == 200 ){
//                 echo json_encode($res);
//                 return;
//             }
//         }
//         echo json_encode($res);
//         return;
//     }