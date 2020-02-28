<?php

class EzdefiProcessModuleFrontController extends ModuleFrontController
{
	protected $helper;

	protected $db;

	protected $config;

	protected $api;

	public function __construct()
	{
		parent::__construct();

		$this->helper = new EzdefiHelper();

		$this->db = new EzdefiDb();

		$this->config = new EzdefiConfig();

		$this->api = new EzdefiApi();
	}

	/**
	 * Handle POST request from checkout form. Redirect back to checkout page if error.
	 * Redirect to process page if no error.
	 */
	public function postProcess()
	{
		if(!$this->isModuleActive() && !$this->checkCartInformation()) {
			return $this->redirectToFirstStep();
		}

		if (!$this->isPaymentOptionValid()) {
			die($this->trans('This payment method is not available.', array(), 'Modules.Ezdefi.Shop'));
		}

		if(!$this->checkCustomerObject()) {
			return $this->redirectToFirstStep();
		}

        $coin_id = Tools::getValue('ezdefi_coin');

		$website_config = $this->api->getWebsiteConfig();

        $coins = $website_config['coins'];

        $coin_data = null;

        foreach ( $coins as $key => $coin ) {
            if ( $coin['_id'] == $coin_id ) {
                $coin_data = $coins[$key];
            }
        }

        if(empty($coin_data)) {
            array_push($this->errors, $this->module->l('Please select cryptocurrency'), false);
            return $this->redirectWithNotifications(
                $this->context->link->getPageLink('order', null, null, array('step' => '3'))
            );
        }

		if(!$this->validateOrder()) {
			return $this->redirectToFirstStep();
		}

		$order = Order::getByCartId($this->getCart()->id);

		$total = $order->total_paid_tax_incl;
        $to = implode(',', array_map( function ( $coin ) {
            return $coin['token']['symbol'];
        }, $coins ) );
		$from = $from = $this->helper->getCurrencyIsoCode((int) $order->id_currency);
		$exchanges = $this->api->getTokenExchanges($total, $from, $to);

		$paymentMethods = $this->config->getPaymentMethods();

		$ajaxUrl = $this->context->link->getModuleLink('ezdefi', 'ajax', array());

		$params = array(
			'id_cart' => $this->getCart()->id,
			'id_module' => $this->module->id,
			'id_order' => $this->module->currentOrder,
			'key' => $this->getCustomer()->secure_key
		);

		$orderConfirmUrl = $this->context->link->getPageLink(
			'order-confirmation',
			true,
			null,
			$params
		);

		$processData = array(
			'uoid' => $order->id,
			'ajaxUrl' => $ajaxUrl,
			'orderConfirmUrl' => $orderConfirmUrl,
		);

		$modulePath = _MODULE_DIR_ . 'ezdefi';

        foreach ($coins as $key => $c) {
            $coins[$key]['json_data'] = array(
                '_id' => $c['_id'],
                'discount' => $c['discount'],
                'wallet_address' => $c['walletAddress'],
                'symbol' => $c['token']['symbol'],
                'decimal' => $c['decimal']
            );
        }

		$this->context->smarty->assign(array(
		    'website_config' => $website_config,
			'coins' => $coins,
			'exchanges' => $exchanges,
			'paymentMethods' => $paymentMethods,
			'processData' => $processData,
			'modulePath' => $modulePath,
			'selectedCurrency' => $coin_data,
		));

		$this->setTemplate('module:ezdefi/views/templates/front/process.tpl');
	}

	/**
	 * Add CSS and JS files
	 *
	 * @return bool|void
	 */
	public function setMedia()
	{
		parent::setMedia();

		$this->registerStylesheet(
			'ezdefi-currency-select-css',
			'modules/ezdefi/views/css/currency-select.css'
		);

		$this->registerStylesheet(
			'ezdefi-process-css',
			'modules/ezdefi/views/css/process.css'
		);

		$this->registerJavascript(
			'ezdefi-clipboard-js',
			'modules/ezdefi/views/js/clipboard.js'
		);

		$this->registerJavascript(
			'ezdefi-process-js',
			'modules/ezdefi/views/js/process.js'
		);
	}

	/**
	 * Check if module is active or not
	 *
	 * @return bool
	 */
	protected function isModuleActive()
	{
		return $this->module->active == 1;
	}

	/**
	 * Check if collects enough information from customer or not
	 *
	 * @return bool
	 */
	protected function checkCartInformation()
	{
		$cart = $this->getCart();

		return (
			$cart->id_customer != 0 &&
			$cart->id_address_delivery != 0 &&
			$cart->id_address_invoice != 0
		);
	}

	/**
	 * Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
	 *
	 * @return bool
	 */
	protected function isPaymentOptionValid()
	{
		$authorized = false;

		foreach (Module::getPaymentModules() as $module) {
			if ($module['name'] == 'ezdefi') {
				$authorized = true;
				break;
			}
		}

		return $authorized;
	}

	/**
	 * Check Customer object is valid or not
	 *
	 * @return bool
	 */
	protected function checkCustomerObject()
	{
		return Validate::isLoadedObject($this->getCustomer());
	}

	/**
	 * Get customer object
	 *
	 * @return Customer
	 */
	protected function getCustomer()
	{
		return new Customer($this->getCustomerId());
	}

	/**
	 * Get customer id
	 *
	 * @return int
	 */
	protected function getCustomerId()
	{
		return $this->getCart()->id_customer;
	}

	/**
	 * Validate order before process
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function validateOrder()
	{
		$total = $this->getTotal();

		return $this->module->validateOrder(
			$this->getCart()->id,
			$this->config->getConfig('EZDEFI_OS_WAITING'),
			$total,
			$this->module->displayName,
			NULL,
			array(),
			(int) $this->context->currency->id,
			false,
			$this->getCustomer()->secure_key
		);
	}

	/**
	 * Return customer back to first step
	 */
	protected function redirectToFirstStep()
	{
		return Tools::redirect('index.php?controller=order&step=1');
	}

	/**
	 * Redirect customer to Order Confirmation page
	 */
	protected function redirectToOrderConfirmPage()
	{
		$data = array(
			'controller' => 'order-confirmation',
			'id_cart' => $this->getCart()->id,
			'id_module' => $this->module->id,
			'id_order' => $this->module->currentOrder,
			'key' => $this->getCustomer()->secure_key
		);

		$ezdefiCurrency = Tools::getValue('ezdefi_currency');

		if(isset($ezdefiCurrency) && !empty($ezdefiCurrency)) {
			$data['ezdefi_currency'] = $ezdefiCurrency;
		}

		$query_string = http_build_query($data, '', '&');

		return Tools::redirect("index.php?$query_string");
	}

	/**
	 * Get Cart object
	 *
	 * @return Cart
	 */
	protected function getCart()
	{
		return $this->context->cart;
	}

	/**
	 * Get cart total
	 *
	 * @return float
	 * @throws Exception
	 */
	protected function getTotal()
	{
		return (float) $this->getCart()->getOrderTotal(true, Cart::BOTH);
	}
}
