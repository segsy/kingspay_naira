<?php

class ModelExtensionPaymentKingspays extends Model {

	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kingspay_order` (
			  `kingspay_order_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `order_code` VARCHAR(50),
			  `date_added` DATETIME NOT NULL,
			  `date_modified` DATETIME NOT NULL,
			  `refund_status` INT(1) DEFAULT NULL,
			  `currency_code` CHAR(3) NOT NULL,
			  `total` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`kingspay_order_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kingspay_order_transaction` (
			  `kingspay_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `kingspay_order_id` INT(11) NOT NULL,
			  `date_added` DATETIME NOT NULL,
			  `type` ENUM('payment', 'refund') DEFAULT NULL,
			  `amount` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`kingspay_order_transaction_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kingspay_order_recurring` (
			  `kingspay_order_recurring_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `order_recurring_id` INT(11) NOT NULL,
			  `order_code` VARCHAR(50),
			  `token` VARCHAR(50),
			  `date_added` DATETIME NOT NULL,
			  `date_modified` DATETIME NOT NULL,
			  `next_payment` DATETIME NOT NULL,
			  `trial_end` datetime DEFAULT NULL,
			  `subscription_end` datetime DEFAULT NULL,
			  `currency_code` CHAR(3) NOT NULL,
			  `total` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`kingspay_order_recurring_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kingspay_card` (
			  `card_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `customer_id` INT(11) NOT NULL,
			  `order_id` INT(11) NOT NULL,
			  `token` VARCHAR(50) NOT NULL,
			  `digits` VARCHAR(22) NOT NULL,
			  `expiry` VARCHAR(5) NOT NULL,
			  `type` VARCHAR(50) NOT NULL,
			  PRIMARY KEY (`card_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kingspay_order`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kingspay_order_transaction`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kingspay_order_recurring`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kingspay_card`;");
	}

	public function refund($order_id, $amount) {
		$kingspay_order = $this->getOrder($order_id);

		if (!empty($kingspay_order) && $kingspay_order['refund_status'] != 1) {
			$order['refundAmount'] = (int)($amount * 100);

			$url = $kingspay_order['order_code'] . '/refund';

			$response_data = $this->sendCurl($url, $order);

			return $response_data;
		} else {
			return false;
		}
	}

	public function updateRefundStatus($kingspay_order_id, $status) {
		$this->db->query("UPDATE `" . DB_PREFIX . "kingspay_order` SET `refund_status` = '" . (int)$status . "' WHERE `kingspay_order_id` = '" . (int)$kingspay_order_id . "'");
	}

	public function getOrder($order_id) {

		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kingspay_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($qry->num_rows) {
			$order = $qry->row;
			$order['transactions'] = $this->getTransactions($order['kingspay_order_id'], $qry->row['currency_code']);

			return $order;
		} else {
			return false;
		}
	}

	private function getTransactions($kingspay_order_id, $currency_code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kingspay_order_transaction` WHERE `kingspay_order_id` = '" . (int)$kingspay_order_id . "'");

		$transactions = array();
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$row['amount'] = $this->currency->format($row['amount'], $currency_code, false);
				$transactions[] = $row;
			}
			return $transactions;
		} else {
			return false;
		}
	}

	public function addTransaction($kingspay_order_id, $type, $total) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "kingspay_order_transaction` SET `kingspay_order_id` = '" . (int)$kingspay_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (double)$total . "'");
	}

	public function getTotalReleased($kingspay_order_id) {
		$query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "kingspay_order_transaction` WHERE `kingspay_order_id` = '" . (int)$kingspay_order_id . "' AND (`type` = 'payment' OR `type` = 'refund')");

		return (double)$query->row['total'];
	}

	public function getTotalRefunded($kingspay_order_id) {
		$query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "kingspay_order_transaction` WHERE `kingspay_order_id` = '" . (int)$kingspay_order_id . "' AND 'refund'");

		return (double)$query->row['total'];
	}
	public function getCurrencies() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.kingspay-gs.com/api/payment/initialize');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}

	public function sendCurl($url, $order,$amount,$currency_code,$payment) {

		$json = json_encode($order);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'https://api.kingspay-gs.com/api/payment/initialize' . $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS,"{\n\t\"amount\": $amount,\n\t\"currency\": \"$currency_code\",\n\t\"description\": \n\t\"merchant_callback_url\": \"$url\",\n\t\"metadata\": { \"product_id\": ,\n\t\"payment_type\": \"$payment\"\n}\n");
		curl_setopt(
				$curl, CURLOPT_HTTPHEADER, array(
						"Authorization: " . $this->config->get('payment_production_secret_key'),
						"Content-Type: application/json",
						"Content-Length: " . strlen($json)
				)
		);

		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		$response = array();

		if (isset($result)) {
			$response['status'] = $result->httpStatusCode;
			$response['message'] = $result->message;
			$response['full_details'] = $result;
		} else {
			$response['status'] = 'success';
		}

		return $response;
	}

	public function logger($message) {
		if ($this->config->get('kingspay_debug') == 1) {
			$log = new Log('kingspay.log');
			$log->write($message);
		}
	}

}
