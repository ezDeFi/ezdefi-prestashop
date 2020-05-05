<?php

class EzdefiCallbackModuleFrontController extends ModuleFrontController
{
	const EXPLORER_URL = 'https://explorer.nexty.io/tx/';

	protected $helper;

	protected $api;

	protected $db;

	public function __construct()
	{
		parent::__construct();

		$this->helper = new EzdefiHelper();

		$this->api = new EzdefiApi();

		$this->db = new EzdefiDb();
	}

	public function initContent()
	{
		parent::initContent();

		$result = null;

		if(Tools::getValue('uoid') && Tools::getValue('paymentid')) {
			$orderId = Tools::getValue('uoid');
			$paymentId = Tools::getValue('paymentid');

			$result = $this->processPaymentCallback($orderId, $paymentId);
		}

		if(
			Tools::getValue('value') && Tools::getValue('explorerUrl') &&
			Tools::getValue('currency') && Tools::getValue('id') &&
			Tools::getValue('decimal')
		) {
			$value = Tools::getValue('value');
			$decimal = Tools::getValue('decimal');
			$value = $value / pow(10, $decimal);
			$value = $this->sanitizeFloatValue($value);
			$explorerUrl = Tools::getValue('explorerUrl');
			$currency = Tools::getValue('currency');
			$id = Tools::getValue('id');

			$result = $this->processTransactionCallback($value, $explorerUrl, $currency, $id);
		}

		header_remove();

		header('Content-Type: application/json');
		header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");

		if($result) {
			http_response_code(200);
			header('Status: 200');
		} else {
			http_response_code(400);
			header('Status: 400');
		}

		die(json_encode($result));
	}

	/**
	 * Process transaction callback from gateway. Uses when gateway receive unknown transaction
	 *
	 * @param $value
	 * @param $explorerUrl
	 * @param $currency
	 * @param $id
	 *
	 * @return bool
	 */
	public function processTransactionCallback($value, $explorerUrl, $currency, $id)
	{
		$transaction = $this->api->getTransaction($id);

		if(!$transaction || empty($transaction)) {
			return false;
		}

		if($transaction['status'] != 'ACCEPTED') {
			return false;
		}

		$data = array(
			'amount_id' => str_replace(',', '', $value),
			'currency' => $currency,
			'explorer_url' => $explorerUrl,
		);

		$this->db->addException($data);

		return true;
	}

	/**
	 * Process payment callback from gateway.
	 *
	 * @param $order_id
	 * @param $paymentid
	 *
	 * @return bool
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
	public function processPaymentCallback($order_id, $paymentid)
	{
		$order_id = substr($order_id, 0, strpos( $order_id,'-'));

		if(is_null($order = $this->helper->getOrderById($order_id))) {
			return false;
		}

		$payment = $this->api->getPayment($paymentid);

		if(!$payment || empty($payment)) {
			return false;
		}

		$status = $payment['status'];
        $uoid = (int) $order_id;
        $payment_method = $this->isPayAnyWallet( $payment ) ? 'amount_id' : 'ezdefi_wallet';

        if($status === 'PENDING' || $status === 'EXPIRED') {
			return false;
		}

        if( $status === 'DONE' ) {
            $this->helper->setOrderDone($uoid);

            if( $payment_method === 'ezdefi_wallet' ) {
                $this->db->deleteExceptions( array(
                    'order_id' => $uoid
                ) );
                return true;
            }
        }

        $value = ( $payment_method === 'amount_id' ) ? $payment['originValue'] : ( $payment['value'] / pow( 10, $payment['decimal'] ) );

        $this->db->updateExceptions(
            array(
                'order_id' => $uoid,
                'payment_method' => $payment_method,
            ),
            array(
                'amount_id' => $this->sanitizeFloatValue( $value ),
                'currency' => $payment['token']['symbol'],
                'status' => strtolower( $status ),
                'explorer_url' => $payment['explorer']['tx'] . $payment['transactionHash']
            ),
            1
        );

        $this->db->deleteExceptions( array(
            'order_id' => $uoid,
            'explorer_url' => null,
        ) );

        return true;
	}

	/**
	 * Sanitize float value
	 *
	 * @param $value
	 *
	 * @return string|string[]
	 */
	protected function sanitizeFloatValue($value)
	{
		$notation = explode('E', strtoupper($value));

		if(count($notation) === 2){
			$exp = abs(end($notation)) + strlen($notation[0]);
			$decimal = number_format($value, $exp);
			$value = rtrim($decimal, '.0');
		}

		return str_replace( ',', '', $value);
	}

	protected function isPayAnyWallet($payment)
    {
        if( ! is_array( $payment ) ) {
            return false;
        }

        return ( isset( $payment['amountId'] ) && $payment['amountId'] = true );
    }
}