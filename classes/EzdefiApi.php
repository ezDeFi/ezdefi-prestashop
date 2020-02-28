<?php

class EzdefiApi
{
    protected $apiUrl;

    protected $apiKey;

    protected $publicKey;

    protected $config;

    public function __construct()
    {
    	$this->config = new EzdefiConfig();
    }

	public function getApiUrl()
	{
		if(!empty($this->apiUrl)) {
			return $this->apiUrl;
		}

		return $this->apiUrl = $this->config->getApiUrl();
	}

	public function setApiUrl($apiUrl)
	{
		return $this->apiUrl = $apiUrl;
	}

    public function getApiKey()
    {
    	if(!empty($this->apiKey)) {
    		return $this->apiKey;
	    }

    	return $this->apiKey = $this->config->getApiKey();
    }

    public function setApiKey($apiKey)
    {
    	return $this->apiKey = $apiKey;
    }

    public function getPublicKey()
    {
        if(!empty($this->publicKey)) {
            return $this->publicKey;
        }

        return $this->publicKey = $this->config->getPublicKey();
    }

    public function setPublicKey($publicKey)
    {
        return $this->publicKey = $publicKey;
    }

	/**
	 * Call API
	 *
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 *
	 * @return array|bool
	 */
    public function call($path = '', $method = 'get', $data = [])
    {
        $url = $this->buildPath($path);

        $httpHeaders = $this->getHttpHeaders();

        $ch = curl_init();

        $method = strtolower($method);

        switch ($method) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'get':
            	if(!empty($data)) {
		            $query = http_build_query( $data, '', '&' );
		            $url   = $url . '?' . $query;
	            }
                break;
        }

	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

	    curl_setopt($ch, CURLOPT_VERBOSE, true);
	    curl_setopt($ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));

	    $response = curl_exec($ch);

	    $formattedResponse = $this->formatResponse($response);

        curl_close($ch);

        return $formattedResponse;
    }

	/**
	 * Get exchange for single cryptocurry
	 *
	 * @param $fiat
	 * @param $token
	 *
	 * @return array|bool
	 */
    public function getTokenExchange($fiat, $token)
    {
    	$url = "token/exchange/$fiat:$token";

    	return $this->call($url, 'get');
    }

	/**
	 * Get exchanges for multiple cryptocurries
	 *
	 * @param $value
	 * @param $from
	 * @param $to
	 *
	 * @return array|bool
	 */
	public function getTokenExchanges($value,$from,$to)
	{
		$url = "token/exchanges?amount=$value&from=$from&to=$to";

		return $this->call($url, 'get');
	}

	/**
	 * Create payment
	 *
	 * @param $data
	 *
	 * @return array|bool
	 */
	public function createPayment($data)
	{
		return $this->call('payment/create', 'post', $data);
	}

	/**
	 * Get payment by id
	 *
	 * @param $paymentId
	 *
	 * @return array|bool
	 */
	public function getPayment($paymentId)
	{
		return $this->call('payment/get', 'get', array(
			'paymentid' => $paymentId
		));
	}

	/**
	 * Check API Key
	 *
	 * @return array|bool
	 */
	public function checkApiKey()
	{
		return $this->call('user/show', 'get');
	}

    public function getWebsiteConfig()
    {
        $public_key = $this->getPublicKey();

        return $this->call( "website/$public_key" );
    }

    public function getWebsiteCoins()
    {
        $website_config = $this->getWebsiteConfig();

        if( is_null( $website_config ) ) {
            return null;
        }

        return $website_config['coins'];
    }

	/**
	 * Get transaction detail
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getTransaction($id)
	{
		return $this->call('transaction/get', 'get', array(
			'id' => $id
		));
	}

	/**
	 * Build full API path
	 * @param string $path
	 *
	 * @return string
	 */
    protected function buildPath($path = '')
    {
        return rtrim($this->getApiUrl(), '/' ) . '/' . $path;
    }

	/**
	 * Prepare HTTP Headers
	 *
	 * @return array
	 */
    protected function getHttpHeaders()
    {
        return array(
	        'api-key: ' . $this->getApiKey(),
        );
    }

	/**
	 * Format response from API
	 *
	 * @param $response
	 *
	 * @return bool|array
	 */
    protected function formatResponse($response)
    {
        $response = json_decode($response, true);

        if($response['code'] < 0) {
            return null;
        }

        return $response['data'];
    }
}
