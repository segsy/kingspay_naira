<?php
class ControllerExtensionPaymentKingspays extends Controller {
    public function index() {

      $data['button_confirm'] = $this->language->get('button_confirm');

  		$this->load->model('checkout/order');

  		if(!isset($this->session->data['order_id'])) {
  			return false;
  		}

  		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

      $payment = ($order_info['currency_code'] == 'NGN') ? 'nigerian' : 'international';

      $kingspays_key = ($this->config->get('payment_kingspays_test_payout') == 1) ? $this->config->get('payment_kingspays_test_secret_key') : $this->config->get('payment_kingspays_production_secret_key');

      $config = array (
  			'kingspays_secret_key' => $kingspays_key,
  			'merchant_callback_url'  => $this->url->link('extension/payment/kingspays/callback'),
        'amount'                 => $order_info['total'],
        'currency'               => $order_info['currency_code'],
        'description'            => "Test payment",
  			'payment_type'           => $payment,
        'product_id'             => $order_info['order_id'],
        'payment_type'           => 'kingspays',
  		);

  		$this->load->model('extension/payment/kingspays');
  		$data['form_params'] = $config;

  		return $this->load->view('extension/payment/kingspays', $data);
  	}

    public function send()
    {
      $this->load->model('checkout/order');
  		if(!isset($this->session->data['order_id'])) {
  			return false;
  		}
  		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

      $payment = ($order_info['currency_code'] == 'NGN') ? 'nigerian' : 'international';

      $kingspays_key = ($this->config->get('payment_kingspays_test_payout') == 1) ? $this->config->get('payment_kingspays_test_secret_key') : $this->config->get('payment_kingspays_production_secret_key');

      $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

      $url = "https://api.kingspay-gs.com/api/payment/initialize";
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $headers = array(
         "authorization: Bearer $kingspays_key",
         "cache-control: no-cache",
         "content-type: application/json",
      );
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      $data = array
      (
          "amount" => $amount * 100,
          "currency" => $order_info['currency_code'],
          "description" => "Test Payment",
          "merchant_callback_url" => $this->url->link('extension/payment/kingspays/callback'),
          "metadata" => array("product_id"=>$order_info['order_id']),
          "payment_type" => $payment,
      );
      $data = json_encode($data);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $resp = curl_exec($curl);
      curl_close($curl);
      $r = $resp;
      $r = json_decode($r);
      $this->session->data['payment_id'] =  $r->payment_id;
      print_r($resp);
      return $resp;
    }

    public function callback()
    {
      $this->response->setOutput($this->load->view('extension/payment/kingspays_js'));
    }

    public function get_data()
    {
      $pay_id = $this->session->data['payment_id'];
      $curl = curl_init('https://api.kingspay-gs.com/api/payment/'.$pay_id);

      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
      curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
      curl_setopt($curl, CURLOPT_POST, 1);

      $response = curl_exec($curl);
      curl_close($curl);

      $custPayResponse = array(
          'data'       => json_decode($response)
      );

      $success = array(
          'redirect'   => $this->url->link('checkout/success', '', true)
      );

      $failed = array(
          'redirect'   => $this->url->link('checkout/failure', '', true)
      );
      if ($custPayResponse['data']->status == "SUCCESS")
      {
        $this->load->model('extension/payment/kingspays');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
  			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);
        $this->model_extension_payment_kingspays->addTransaction($this->session->data['order_id'],1,$order_info);
        $GLOBALS['params'] = array_merge($custPayResponse, $success);
      }
      else
      {
        $GLOBALS['params'] = array_merge($custPayResponse, $failed);
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($GLOBALS['params']));
    }
}
?>
