<?php
class ControllerExtensionPaymentKingspays extends Controller {
	private $error = array();
    private $currencies = array('GBP', 'NGN', 'USD', 'CHF','SGD', 'SEK', 'DKK', 'NOK','ZAR', 'JPY', 'CAD', 'AUD', 'EUR', 'NZD', 'KRW', 'THB');

	public function index() {
		$this->load->language('extension/payment/kingspays');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_kingspays', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['error_service_key'])) {
			$data['error_service_key'] = $this->error['error_service_key'];
		} else {
			$data['error_service_key'] = '';
		}

		if (isset($this->error['error_client_key'])) {
			$data['error_client_key'] = $this->error['error_client_key'];
		} else {
			$data['error_client_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/payment/kingspays', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/kingspays', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_kingspays_production_secret_key'])) {
			$data['payment_kingspays_production_secret_key'] = $this->request->post['payment_kingspays_production_secret_key'];
		} else {
			$data['payment_kingspays_production_secret_key'] = $this->config->get('payment_kingspays_production_secret_key');
		}

		if (isset($this->request->post['payment_kingspays_test_secret_key'])) {
			$data['payment_kingspays_test_secret_key'] = $this->request->post['payment_kingspays_test_secret_key'];
		} else {
			$data['payment_kingspays_test_secret_key'] = $this->config->get('payment_kingspays_test_secret_key');
		}

		if (isset($this->request->post['payment_kingspays_test_payout'])) {
			$data['payment_kingspays_test_payout'] = $this->request->post['payment_kingspays_test_payout'];
		} else {
			$data['payment_kingspays_test_payout'] = $this->config->get('payment_kingspays_test_payout');
		}

    if (isset($this->request->post['payment_kingspays_total'])) {
        $data['payment_kingspays_total'] = $this->request->post['payment_kingspays_total'];
    } else {
        $data['payment_kingspays_total'] = $this->config->get('payment_kingspays_total');
    }


        if (isset($this->request->post['payment_kingspays_currency'])) {
            $data['payment_kingspays_currency'] = $this->request->post['payment_kingspays_currency'];
        } else {
            $data['payment_kingspays_currency'] = $this->config->get('payment_kingspays_currency');
        }

        $this->load->model('localisation/currency');

        $currencies = $this->model_localisation_currency->getCurrencies();
        $data['currencies'] = array();
        foreach ($currencies as $currency) {
            if (in_array($currency['code'], $this->currencies)) {
                $data['currencies'][] = array(
                    'code'   => $currency['code'],
                    'title'  => $currency['title']
                );
            }
        }

		if (isset($this->request->post['payment_kingspays_order_status_id'])) {
			$data['payment_kingspays_order_status_id'] = $this->request->post['payment_kingspays_order_status_id'];
		} else {
			$data['payment_kingspays_order_status_id'] = $this->config->get('payment_kingspays_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_kingspays_geo_zone_id'])) {
			$data['payment_kingspays_geo_zone_id'] = $this->request->post['payment_kingspays_geo_zone_id'];
		} else {
			$data['payment_kingspays_geo_zone_id'] = $this->config->get('payment_kingspays_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_kingspays_status'])) {
			$data['payment_kingspays_status'] = $this->request->post['payment_kingspays_status'];
		} else {
			$data['payment_kingspays_status'] = $this->config->get('payment_kingspays_status');
		}

		if (isset($this->request->post['payment_kingspays_sort_order'])) {
			$data['payment_kingspays_sort_order'] = $this->request->post['payment_kingspays_sort_order'];
		} else {
			$data['payment_kingspays_sort_order'] = $this->config->get('payment_kingspays_sort_order');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_kingspays_entry_success_status_id'])) {
			$data['payment_kingspays_entry_success_status_id'] = $this->request->post['payment_kingspays_entry_success_status_id'];
		} else {
			$data['payment_kingspays_entry_success_status_id'] = $this->config->get('payment_kingspays_entry_success_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_failed_status_id'])) {
			$data['payment_kingspays_entry_failed_status_id'] = $this->request->post['payment_kingspays_entry_failed_status_id'];
		} else {
			$data['payment_kingspays_entry_failed_status_id'] = $this->config->get('payment_kingspays_entry_failed_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_settled_status_id'])) {
			$data['payment_kingspays_entry_settled_status_id'] = $this->request->post['payment_kingspays_entry_settled_status_id'];
		} else {
			$data['payment_kingspays_entry_settled_status_id'] = $this->config->get('payment_kingspays_entry_settled_status_id');
		}

		if (isset($this->request->post['payment_kingspays_refunded_status_id'])) {
			$data['payment_kingspays_refunded_status_id'] = $this->request->post['payment_kingspays_refunded_status_id'];
		} else {
			$data['payment_kingspays_refunded_status_id'] = $this->config->get('payment_kingspays_refunded_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_partially_refunded_status_id'])) {
			$data['payment_kingspays_entry_partially_refunded_status_id'] = $this->request->post['payment_kingspays_entry_partially_refunded_status_id'];
		} else {
			$data['payment_kingspays_entry_partially_refunded_status_id'] = $this->config->get('payment_kingspays_entry_partially_refunded_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_charged_back_status_id'])) {
			$data['payment_kingspays_entry_charged_back_status_id'] = $this->request->post['payment_kingspays_entry_charged_back_status_id'];
		} else {
			$data['payment_kingspays_entry_charged_back_status_id'] = $this->config->get('payment_kingspays_entry_charged_back_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_information_requested_status_id'])) {
			$data['payment_kingspays_entry_information_requested_status_id'] = $this->request->post['payment_kingspays_entry_information_requested_status_id'];
		} else {
			$data['payment_kingspays_entry_information_requested_status_id'] = $this->config->get('payment_kingspays_entry_information_requested_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_information_supplied_status_id'])) {
			$data['payment_kingspays_entry_information_supplied_status_id'] = $this->request->post['payment_kingspays_entry_information_supplied_status_id'];
		} else {
			$data['payment_kingspays_entry_information_supplied_status_id'] = $this->config->get('payment_kingspays_entry_information_supplied_status_id');
		}

		if (isset($this->request->post['payment_kingspays_entry_chargeback_reversed_status_id'])) {
			$data['payment_kingspays_entry_chargeback_reversed_status_id'] = $this->request->post['payment_kingspays_entry_chargeback_reversed_status_id'];
		} else {
			$data['payment_kingspays_entry_chargeback_reversed_status_id'] = $this->config->get('payment_kingspays_entry_chargeback_reversed_status_id');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/kingspays', $data));
	}

	public function install() {
		$this->load->model('extension/payment/kingspays');

		$this->model_extension_payment_kingspays->install();
	}

	public function uninstall() {
		$this->load->model('extension/payment/kingspays');
		$this->model_extension_payment_kingspays->uninstall();
	}

	public function order() {

		if ($this->config->get('payment_kingspays_status')) {

			$this->load->model('extension/payment/kingspays');

			$kingspay_order = $this->model_extension_payment_kingspays->getOrder($this->request->get['order_id']);

			if (!empty($kingspay_order)) {
				$this->load->language('extension/payment/kingspays');

				$kingspay_order['total_released'] = $this->model_extension_payment_kingspays->getTotalReleased($kingspay_order['kingspay_order_id']);

				$kingspay_order['total_formatted'] = $this->currency->format($kingspay_order['total'], $kingspay_order['currency_code'], false);
				$kingspay_order['total_released_formatted'] = $this->currency->format($kingspay_order['total_released'], $kingspay_order['currency_code'], false);

				$data['kingspay_order'] = $kingspay_order;

				$data['order_id'] = $this->request->get['order_id'];

				$data['user_token'] = $this->request->get['user_token'];

				return $this->load->view('extension/payment/kingspays_order', $data);
			}
		}
	}

	public function refund() {
		$this->load->language('extension/payment/kingspays');
		$json = array();

		if (isset($this->request->post['order_id']) && !empty($this->request->post['order_id'])) {
			$this->load->model('extension/payment/kingspays');

			$kingspay_order = $this->model_extension_payment_kingspays->getOrder($this->request->post['order_id']);

			$refund_response = $this->model_extension_payment_kingspays->refund($this->request->post['order_id'], $this->request->post['amount']);

			$this->model_extension_payment_kingspays->logger('Refund result: ' . print_r($refund_response, 1));

			if ($refund_response['status'] == 'success') {
				$this->model_extension_payment_kingspays->addTransaction($kingspay_order['kingspay_order_id'], 'refund', $this->request->post['amount'] * -1);

				$total_refunded = $this->model_extension_payment_kingspays->getTotalRefunded($kingspay_order['kingspay_order_id']);
				$total_released = $this->model_extension_payment_kingspays->getTotalReleased($kingspay_order['kingspay_order_id']);

				$this->model_extension_payment_kingspays->updateRefundStatus($kingspay_order['kingspay_order_id'], 1);

				$json['msg'] = $this->language->get('text_refund_ok_order');
				$json['data'] = array();
				$json['data']['created'] = date("Y-m-d H:i:s");
				$json['data']['amount'] = $this->currency->format(($this->request->post['amount'] * -1), $kingspay_order['currency_code'], false);
				$json['data']['total_released'] = $this->currency->format($total_released, $kingspay_order['currency_code'], false);
				$json['data']['total_refund'] = $this->currency->format($total_refunded, $kingspay_order['currency_code'], false);
				$json['data']['refund_status'] = 1;
				$json['error'] = false;
			} else {
				$json['error'] = true;
				$json['msg'] = isset($refund_response['message']) && !empty($refund_response['message']) ? (string)$refund_response['message'] : 'Unable to refund';
			}
		} else {
			$json['error'] = true;
			$json['msg'] = 'Missing data';
		}

		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/kingspays')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_kingspays_production_secret_key']) {
			$this->error['error_service_key'] = $this->language->get('error_service_key');
		}

		if (!$this->request->post['payment_kingspays_test_secret_key']) {
			$this->error['error_client_key'] = $this->language->get('error_client_key');
		}

		return !$this->error;
	}
}
