<?php

class EzdefiHelper
{
	protected $config;

	protected $db;

	protected $api;

	public function __construct()
	{
		$this->config = new EzdefiConfig();

		$this->db = new EzdefiDb();

		$this->api = new EzdefiApi();
	}

	/**
	 * Get currency Iso Code
	 *
	 * @param $id_currency
	 *
	 * @return string
	 */
	public function getCurrencyIsoCode($id_currency)
	{
		$currency = new Currency($id_currency);

		return $currency->iso_code;
	}

	/**
	 * Get Order by order id
	 *
	 * @param $id
	 *
	 * @return Order|null
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
	public function getOrderById($id)
	{
		$order = new Order((int) $id);

		if($order) {
			return $order;
		}

		return null;
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public function getOrderTotal($order)
	{
		return $order->total_paid_tax_incl;
	}

	/**
	 * Get token exchanges
	 *
	 * @param $total
	 * @param $from
	 *
	 * @return array|bool
	 */
	public function getExchanges($total, $from)
	{
		$acceptedCurrencies = $this->config->getAcceptedCurrencies();

		$to = implode(',', array_map(function ($currency) {
			return $currency['symbol'];
		}, $acceptedCurrencies));

		return $this->api->getTokenExchanges($total, $from, $to);
	}

	/**
	 * Set order awaiting
	 *
	 * @param $order_id
	 *
	 * @return bool
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
	public function setOrderAwaiting($order_id)
	{
		$order = new Order($order_id);

		return $order->setCurrentState($this->config->getConfig('EZDEFI_OS_WAITING'));
	}

	/**
	 * Set order done
	 * @param $order_id
	 *
	 * @return bool
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
	public function setOrderDone($order_id)
	{
		$order = new Order($order_id);

		return $order->setCurrentState($this->config->getConfig('PS_OS_PAYMENT'));
	}

	/**
	 * Generate unique amount id
	 *
	 * @param $fiat
	 * @param $value
	 * @param $currencyData
	 *
	 * @return float|int|null
	 */
	public function generateUniqueAmountId($fiat, $value, $currencyData)
	{
		$token = $currencyData['symbol'];
		$rate = $this->api->getTokenExchange($fiat, $token);

		if(!$rate) {
			return null;
		}

		$value = $value * $rate;

		$acceptable_variation = $this->config->getConfig('EZDEFI_ACCEPTABLE_VARIATION');

		$value = $this->db->generateUniqueAmountId($value, $currencyData, $acceptable_variation);

		return $value;
	}
}