<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * -----------------------------------------------------------------
 * This Server Manager has created by @vanixweb https://github.com/vanixweb/FOSSBilling-Froxlor 
 */







 
 class FroxlorAPI
 {
     private ?string $url;
     private ?string $key;
     private ?string $secret;
     private ?array $lastError = null;
     private ?string $lastStatusCode = null;
 
     public function __construct($url, $key, $secret)
     {
         $this->url = $url;
         $this->key = $key;
         $this->secret = $secret;
     }
 
     public function request($command, array $data = [])
     {
         $payload = [
             'command' => $command,
             'params' => $data
         ];
 
         $ch = curl_init($this->url);
         curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
         curl_setopt($ch, CURLOPT_HEADER, 0);
         curl_setopt($ch, CURLOPT_USERPWD, $this->key . ":" . $this->secret);
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $result = curl_exec($ch);
 
         $this->lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
         return json_decode($result ?? curl_error($ch), true);
     }
 
     public function getLastStatusCode(): ?string
     {
         return $this->lastStatusCode;
     }
 }


class Server_Manager_Froxlor extends Server_Manager
{
    /**
     * Method is called just after obejct contruct is complete.
     * Add required parameters checks here.
     */
    public function init()
    {
    }

    /**
     * Return server manager parameters.
     */
    public static function getForm(): array
    {
        return [
            'label' => 'Froxlor',
            'form' => [
                'credentials' => [
                    'fields' => [
                        [
                            'name' => 'username',
                            'type' => 'text',
                            'label' => 'API Key',
                            'placeholder' => 'API Key you\'ve generated in Froxlor.',
                            'required' => true,
                        ],
                        [
                            'name' => 'password',
                            'type' => 'text',
                            'label' => 'Secret key',
                            'placeholder' => 'Secret key for the access key you\'ve generated in Froxlor',
                            'required' => true,
                        ],
                    ],
                ],
            ]
        ];
    }


    /**
     * Returns link to account management page.
     *
     * @return string
     */
    public function getLoginUrl(?Server_Account $account = null)
    {
        return 'https://' . $this->_config['host'] . '/';
    }

    /**
     * Returns link to reseller account management.
     *
     * @return string
     */
    
    public function getResellerLoginUrl(?Server_Account $account = null)
    {
        return $this->getLoginUrl();
    }


    private function _getPackageName(Server_Package $package)
    {
        $name = $package->getName();

        return $name;
    }

    /**
     * This method is called to check if configuration is correct
     * and class can connect to server.
     *
     * @return bool
     */
    public function testConnection()
    {


        $host = 'https://' . $this->_config['host'] . '/api.php';

        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
       
       // Perform a test API request
        $response = $fapi->request('Froxlor.listFunctions');

        // Check for errors
        if ($fapi->getLastStatusCode() != 200) {

         // Output error response as JSON
            throw new Server_Exception('Failed to connect to the :type: server. Please verify your credentials and configuration', [':type:' => 'Froxlor']);

            exit();
        }
           
        return true;

        
    
        
        

}

// view response data

    

    /**
     * Methods retrieves information from server, assign's new values to
     * cloned Server_Account object and returns it.
     *
     * @return Server_Account
     */
    public function synchronizeAccount(Server_Account $a)
    {
        $this->getLog()->info('Synchronizing account with server ' . $a->getUsername());
        $new = clone $a;
        //@example - retrieve username from server and set it to cloned object
        //$new->setUsername('newusername');
        return $new;
    }


    

    /**
     * Create new account on server.
     *
     * @param Server_Account $a
     */

    public function createAccount(Server_Account $a)
    {



		$package = $a->getPackage()->getName();
        //throw new Server_Exception(" $client->getEmail()");


        // Hosting plan name you want to find
        $hostingPlanName = "$package";

        $host = 'https://' . $this->_config['host'] . '/api.php';
        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);

        // Get hosting plan ID by name
        $params = [
            "planname" => $hostingPlanName
        ];

        $response = $fapi->request('HostingPlans.get', $params);

        // Check for errors
        if ($fapi->getLastStatusCode() != 200) {
            $errorResponse = [
                'error' => true,
                'message' => $response['message'] ?? 'Unknown error occurred'
            ];

            // Output error response as JSON
            echo json_encode($errorResponse);
            exit();
        }

        // Extract hosting plan ID from the response
        $hostingPlanId = $response['data']['id'];

        // Check if hosting plan ID was found
        if (!$hostingPlanId) {
            

            //throw new Server_Exception("($package), Hosting plan not found");
            exit();
        }




        // customer data
        $customerData = [
            'new_loginname' => $a->getUsername(),
            'email' => $client = $a->getClient() ->getEmail(),
            'firstname' => $a->getUsername(),
            'company' =>  $a->getUsername(),
            'new_customer_password' => base64_encode($a->getPassword()),
            'hosting_plan_id' =>  1,
            'api_allowed' => false,];
            $host = 'https://' . $this->_config['host'] . '/api.php';
            $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
    
    
        $response = $fapi->request('Customers.add', $customerData);

        // check for error
        if ($fapi->getLastStatusCode() != 200) {
            throw new Server_Exception("{$response['message']}");
        }
        // create a dwomain
        //? Froxlor is not creating a domain once the account created so we can do that manually

        $customerId = $response['data']['customerid'];
        $customerDomainData = [
            'domain' => $a->getDomain(),
            'customerid' => $customerId,
            'subcanemaildomain' => 2,
            'selectserveralias' => 1,
            'caneditdomain' => true,
            'isbinddomain' => true,
            'ssl_redirect' => true
        ];
        $domainRes = $fapi->request('Domains.add', $customerDomainData);

        if ($fapi->getLastStatusCode() != 200) {
            throw new Server_Exception("{$domainRes['message']}");
        }

        return true;
    }

    /**
     * Suspend account on server.
     */
    public function suspendAccount(Server_Account $a)
    {
        $host = 'https://' . $this->_config['host'] . '/api.php';
        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
        $SuspCustomerData = [
    
            'loginname' => $a->getUsername(),
            'deactivated' => true
            
         ];

        // Send the update request to the API
        $SuspCustomerRes = $fapi->request('Customers.update', $SuspCustomerData);

        // Check for errors while updating loginname
        if ($fapi->getLastStatusCode() != 200) {

            throw new Server_Exception("HTTP-STATUS {$fapi->getLastStatusCode()} {$SuspCustomerRes['message']}");

        exit();
        }         
       
        return true;
    }

    /**
     * Unsuspend account on server.
     */
    public function unsuspendAccount(Server_Account $a)
    {
        $host = 'https://' . $this->_config['host'] . '/api.php';
        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
        $unSuspCustomerData = [
    
            'loginname' => $a->getUsername(),
            'deactivated' => false
            
         ];

        // Send the update request to the API
        $unSuspCustomerRes = $fapi->request('Customers.update', $unSuspCustomerData);

        // Check for errors while updating loginname
        if ($fapi->getLastStatusCode() != 200) {
            throw new Server_Exception("HTTP-STATUS {$fapi->getLastStatusCode()} {$unSuspCustomerRes['message']}");
        exit();
        }         
       
        return true;
    }

    /**
     * Cancel account on server.
     */
    public function cancelAccount(Server_Account $a)
    {
        $host = 'https://' . $this->_config['host'] . '/api.php';
        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
        $cancelAccountCustomerData = [
    
            'loginname' => $a->getUsername(),
            'delete_userfiles' => true
            
         ];

        // Send the update request to the API
        $cancelAccountCustomerRes = $fapi->request('Customers.delete', $cancelAccountCustomerData);

        // Check for errors while updating loginname
        if ($fapi->getLastStatusCode() != 200) {
            throw new Server_Exception("HTTP-STATUS {$fapi->getLastStatusCode()} {$cancelAccountCustomerRes['message']}");
        exit();
        }         
       

        return true;
    }

    /**
     * Change account package on server.
     */
    public function changeAccountPackage(Server_Account $a, Server_Package $p)
    {
        throw new Server_Exception('Changing account package feature will be available soon');

    }

    /**
     * Change account username on server.
     */
    public function changeAccountUsername(Server_Account $a, $new)
    {
        throw new Server_Exception('Froxlor does not support changing username');

    }
    /**
     * Change account domain on server.
     */
    public function changeAccountDomain(Server_Account $a, $new)
    {
        throw new Server_Exception('Froxlor does not support changing domain');

    }

    /**
     * Change account password on server.
     */
    public function changeAccountPassword(Server_Account $a, $new)
    {
        
        $host = 'https://' . $this->_config['host'] . '/api.php';
        $fapi = new FroxlorAPI($host, $this->_config['username'], $this->_config['password']);
        $changeAccountPasswordData = [
    
            'loginname' => $a->getUsername(),
            'new_customer_password' => $new
            
         ];

        // Send the update request to the API
        $changeAccountPasswordRes = $fapi->request('Customers.update', $changeAccountPasswordData);

        // Check for errors while updating loginname
        if ($fapi->getLastStatusCode() != 200) {
            throw new Server_Exception("HTTP-STATUS {$fapi->getLastStatusCode()} {$changeAccountPasswordRes['message']}");
        exit();
        }         
        return true;
    }

    /**
     * Change account IP on server.
     */
    public function changeAccountIp(Server_Account $a, $new)
    {
        throw new Server_Exception(':type: does not support :action:', [':type:' => 'Froxlor', ':action:' => __trans('changing the account IP')]);
    }
}
