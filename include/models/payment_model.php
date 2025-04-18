<?php
class PaymentModel extends Model {
  private $FLURL = 'https://foreninglet.dk/main/apiredirectcreditcardwindow/';


  public function checkParticipant($post) {
    if (isset($post->id)) {
      $participant = $this->createEntity('Deltagere')->findById($post->id);

      // Test correct participant info
      if(empty($participant)) return null;
      if($participant->password !== $post->pass) return null;
    } else {
      $participant = $this->createEntity('Deltagere')->findByField('participant_hash', $post->hash);
      if(empty($participant)) return null;
    }

    return $participant;
  }

  public function getPaymentUser($uid) {
    $result = $this->db->query(
      "SELECT payment_user_id FROM payment_users WHERE participant_id = ?",
      $uid
    );

    if (empty($result)) return null;
    
    return $result[0][0];
  }

  public function createPaymentUser($user_id) {
    // Insert first to minimize chance of duplicates from multiple requests.
    $payment_uid = 0;
    $display_id = 0;
    $this->db->exec(
      "INSERT INTO payment_users VALUES(?,?,?)",
      [$user_id, $payment_uid, $display_id],
    );

    $first_name = "Fastaval Deltager ". $user_id;
    $result = $this->foreningLetAPI('member', [
      'member' => [
        'first_name' => $first_name,
        'activity_ids' => [
          57455, // Alea
          //160539, // Fastaval Test
          160538, //Fastaval 2025
        ]
      ]
    ]);

    $payment_uid = $result->member->id;
    $display_id = $result->member->display_id;
    $this->db->exec(
      "UPDATE payment_users SET payment_user_id = ?, payment_display_id = ? WHERE participant_id = ?",
      [$payment_uid, $display_id, $user_id],
    );
    return $payment_uid;
  }

  public function updatePayments($participant) {
    $balance = $participant->calcSignupTotal() - $participant->betalt_beloeb;
    if ($balance <= 0) return 'no payment needed';

    $payment_uid = $this->getPaymentUser($participant->id);

    if ($payment_uid == null) {
      $this->fileLog("updatePayments(): No payment user found for participant ID $participant->id\n");
      return 'no payment user';
    }
    
    $result = $this->db->query(
      "SELECT * FROM payment_registrations WHERE status = 'pending' AND payment_user_id = ?",
      [$payment_uid],
    );
    if (!empty($result)) {
      if ($result[0]['amount'] == $balance) {
        return ['pending', $this->FLURL.$result[0]['token']];
      }
      return 'unconfirmed payments';
    }

    $this->db->exec(
      "DELETE FROM payment_registrations WHERE status = 'created' AND payment_user_id = ?",
      [$payment_uid],
    );


    $this->db->exec(
      "INSERT INTO payment_registrations(payment_user_id, amount, created) VALUES(?,?,?)",
      [$payment_uid, $balance, time()],
    );

    return 'success';
  }

  public function createFLPaymentURL($payment_uid) {
    $db_result = $this->db->query(
      "SELECT * FROM payment_registrations WHERE status = 'created' AND payment_user_id = ?",
      [$payment_uid],
    );

    if (empty($db_result)) return ['error', 'no registered payments'];

    //$this->fileLog("Get payment registration Result:\n".print_r($db_result,true));

    $api_result = $this->foreningLetAPI(
      'invoice',
      [
        'invoice' => [
          "member_id" => $payment_uid,
          "description" => 'Fastaval '.$this->getConYear().' tilmelding',
          "amount" => $db_result[0]['amount'],
        ]
      ]
    );

    //$this->fileLog("Create Payment URL Result:\n".print_r($api_result,true));

    $url = $api_result->invoice->credit_card_payment_url;
    $token = substr($url, strlen($this->FLURL));
    $this->db->exec(
      "UPDATE payment_registrations SET token = ?, fl_id = ?, status = 'pending' WHERE id = ?",
      [$token, $api_result->invoice->id, $db_result[0]['id']],
    );

    return ['success', $url];
  }

  private function foreningLetAPI($resource, $fields = null, $method = null, $base_url = '') {
    $credentials = $this->config->get('payment.api_credentials');

    $ch = curl_init();
    $header = array(
      'Accept: application/json',
      'Content-Type: application/json',
    );
      
    $url = (empty($base_url) ? 'https://foreninglet.dk/api/' : $base_url) . $resource . '?version=1';
    if ($method == null) {
      if ($fields) {
        $method = 'POST';
      } else {
        $method = 'GET';
      }
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_USERPWD, $credentials);
    if ($fields) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data);
  }


  /**
   * Create a payment url and save details about the payment
   */
  public function createPaymentURL($post) {
    $participant = $this->createEntity('Deltagere')->findById($post->id);

    // Test correct participant info
    if(empty($participant)) return ['error', "No participant with ID:$post->id"];
    if($participant->password !== $post->pass) return ['error', "Wrong password"];

    // Check amount
    $amount = $participant->calcSignupTotal() - $participant->betalt_beloeb;
    if ($amount <= 0) return ['error', "No payment needed"];

    // Check for exiting payments for participant and set order ID accordingly
    $payments = $this->db->query("SELECT * FROM payment_log WHERE participant_id = ?", $participant->id);
    $order_id = "Deltager $participant->id" . (count($payments) > 0 ? " #". count($payments): "");

    // Create a unique token for the payment    
    do {
      $token = makeRandomString(40);
    } while(count($this->db->query("SELECT * FROM payment_log WHERE token = ?", $token)) > 0);
    
    // Set values for the payment
    $return_url = $this->config->get('payment.return_page');
    $base_url = $this->config->get('app.public_uri');
    $testmode = $this->config->get('payment.testing');

    $fields = [
      'Amount'			      => $amount * 100, //amount in minor units e.g. 1DKK = 100
      'Currency'          => 'DKK',
      'OrderNumber'		    => "".$order_id,
      'CustomerAcceptUrl'	=> "$return_url?status=accept&token=$token",
      'CustomerCancelUrl' => "$return_url?status=cancel&token=$token",
      'ServerCallbackUrl'	=> $base_url."payment/callback",
      'BillingAddress'  	=> [
        'AddressLine1'		=> $participant->adresse1,
        'AddressLine2'		=> $participant->adresse2,
        'City'				    => $participant->by,
        'PostCode'			  => $participant->postnummer,
        'Country'			    => "".$participant->getContryISO(),
      ],
      'Options'           => [
        'TestMode'        => !!$testmode,
      ]
    ];

    // Get payment url from freepay
    $json = $this->doCurl('payment', json_encode($fields), 'POST', 'https://gw.freepay.dk/api/');
    $vars = json_decode($json);

    // Save payment details
    $query = "INSERT INTO payment_log (participant_id, amount, payment_id, created, token) VALUES (?,?,?,?,?)";
    $values = [
      $participant->id,
      $amount,
      $vars->paymentIdentifier,
      time(),
      $token,
    ];
    $this->db->exec($query, $values);

    return ['success', $vars->paymentWindowLink];
  }

  /**
   * Cancel a payment created with createPaymentURL()
   */
  public function cancelPayment($token) {
    $query = "UPDATE payment_log SET completed = ?, status = 'cancelled', token = NULL WHERE token = ?";
    $this->db->exec($query, [time(), $token]);
  }

  /**
   * Get the status of a payment
   */
  public function getPaymentStatus($token) {
    $query = "SELECT * FROM payment_log WHERE token = ?";
    $result = $this->db->query($query, $token);

    if (count($result) == 0) {
      return ['error', 'invalid token'];
    }
    return [$result[0]['status'], ''];
  }

  public function completePayment($vars) {
    $auth_id = $vars->authorizationIdentifier;
    $payment_id = $vars->paymentIdentifier;

    // Save auth ID
    $query = "UPDATE payment_log SET auth_id = ? WHERE payment_id = ?";
    $this->db->exec($query, [$auth_id, $payment_id]);

    // Get info about the payment authorization
    $json = $this->doCurl('authorization/' . $auth_id, null, 'GET');
    $info = json_decode($json);
    //$this->fileLog("Payment Info:\n".print_r($info,true));

    // Capture the full ammount
    $json = $this->doCurl('authorization/' . $auth_id . '/capture', '{}', 'POST');
    $capture_result = json_decode($json);
    //$this->fileLog("Payment Capture:\n".print_r($capture_result,true));

    // Save payment status
    $status = $capture_result->IsSuccess ? 'confirmed' : 'failed';
    $amount = $info->CaptureAmount;
    $query = "UPDATE payment_log SET status = ?, amount = ?, completed = ? WHERE payment_id = ?";
    $this->db->exec($query, [$status, $amount, time(), $payment_id]);

    // Get participant from payment
    $query = "SELECT * FROM payment_log WHERE payment_id = ?";
    $result = $this->db->query($query, [$payment_id]);
    $participant = $this->createEntity('Deltagere')->findById($result[0]['participant_id']);

    if ($status == 'failed') {
      // Maybe add a note to the participant
      // The participant will be notified about the error on the payment page when checking status
      $this->log("FAILED on-line payment for participant $participant->id of $amount DKK ", 'Payment', null);
      exit;
    }

    $amount = $result[0]['amount'] / 100;
    $participant->betalt_beloeb += $amount;

    $date = date('Y-m-d H:i:s', time());
    $note = 
      "Betalt med online betaling via Freepay\n".
      "Beløb: $amount, Dato: $date\n".
      "Betalings ID: ".$result[0]['payment_id'];
    
    if (!empty($participant->paid_note)) {
      $participant->paid_note .= "\n$note";
    } else {
      $participant->paid_note = $note;
    }
    $participant->update();

    $this->log("Registered on-line payment for participant $participant->id of $amount DKK", 'Payment', null);

    return [$participant, $amount];
  }

  public function checkTotal($post) {
    // Find by ID
    $participant = $this->createEntity('Deltagere')->findById($post->id);
    // Find by email instead
    if(!$participant) {
      $select = $this->createEntity('Deltagere')->getSelect();
      $select->setWhere('email', '=', $post->id);
      $select->setWhere('password', '=', $post->pass);

      $participant = $this->createEntity('Deltagere')->findBySelect($select);
    }
    if (!$participant || $participant->password != $post->pass) {
      return ['error', "wrong credentials"];
    }

    // Check amount
    $totals = [
      'signup' => $participant->calcSignupTotal(),
      'paid' => $participant->betalt_beloeb,
    ];

    return ['success', $totals];
  }

  //--------------------------------------------------------------------------------------------------------------------
  // Utility functions
  //--------------------------------------------------------------------------------------------------------------------
  
  /**
   * CURL function to communicate with Freepay API
   */
  private function doCurl($resource, $fields = null, $method = null, $base_url = '') {
    $apiKey = $this->config->get('payment.freepay_api'); //your API key that you get from Freepay dashboard

    $ch = curl_init();
    $header = array(
      'Authorization: ' . $apiKey,
      'Accept: application/json',
      'Content-Type: application/json',
    );
      
    $url = (empty($base_url) ? 'https://mw.freepay.dk/api/v2/' : $base_url) . $resource;
    if ($method == null) {
      if ($fields) {
        $method = 'POST';
      } else {
        $method = 'GET';
      }
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($fields) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
}