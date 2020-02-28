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
}