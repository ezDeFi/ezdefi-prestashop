<?php

class EzdefiConfig
{
	/**
	 * Get module config by key
	 *
	 * @param $key
	 *
	 * @return string
	 */
	public function getConfig($key)
	{
		return Configuration::get($key);
	}

	/**
	 * Update module config
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return bool
	 */
	public function updateConfig($key, $value)
	{
		$value = ($key === 'EZDEFI_CURRENCY') ? serialize($value) : $value;

		return Configuration::updateValue($key, $value);
	}

	/**
	 * Get API Url config
	 *
	 * @return string
	 */
	public function getApiUrl()
	{
		return $this->getConfig('EZDEFI_API_URL');
	}

	/**
	 * Get API Key config
	 *
	 * @return string
	 */
	public function getApiKey()
	{
		return $this->getConfig('EZDEFI_API_KEY');
	}

	/**
	 * Get accepted cryptocurrencies
	 *
	 * @return mixed
	 */
	public function getAcceptedCurrencies()
	{
		return unserialize($this->getConfig('EZDEFI_CURRENCY'));
	}

	/**
	 * Get currency config data
	 *
	 * @param $symbol
	 *
	 * @return mixed|null
	 */
	public function getCurrencyOptionData($symbol)
	{
		$currency_data = $this->getAcceptedCurrencies();

		$index = array_search($symbol, array_column($currency_data, 'symbol'));

		if($index === false) {
			return null;
		}

		return $currency_data[$index];
	}

	/**
	 * Get payment methods config
	 *
	 * @return array
	 */
	public function getPaymentMethods()
	{
		$methods = array();

		$amount_id = $this->getConfig('EZDEFI_AMOUNT_ID');

		if($amount_id == '1') {
			$methods[] = 'amount_id';
		}

		$ezdefi_wallet = $this->getConfig('EZDEFI_EZDEFI_WALLET');

		if($ezdefi_wallet == '1') {
			$methods[] = 'ezdefi_wallet';
		}

		return $methods;
	}
}