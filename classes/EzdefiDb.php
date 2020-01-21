<?php

class EzdefiDb
{
	/**
	 * Create ezdefi_amount_ids table
	 *
	 * @return bool
	 */
	public function createAmountIdsTable()
	{
		$table_name = _DB_PREFIX_ . 'ezdefi_amount_ids';

		$query = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
			amount_key int(11) NOT NULL,
			price decimal(20,12) NOT NULL,
			amount_id decimal(20,12) NOT NULL,
			currency varchar(10) NOT NULL,
			expired_time timestamp default current_timestamp,
			PRIMARY KEY (id),
			UNIQUE (amount_id, currency)
		);";

		return DB::getInstance()->execute($query);
	}

	/**
	 * Drop ezdefi_amount_ids table
	 *
	 * @return bool
	 */
	public function dropAmountIdsTable()
	{
		$amount_ids_table_name = _DB_PREFIX_ . 'ezdefi_amount_ids';

		return DB::getInstance()->execute("DROP TABLE $amount_ids_table_name");
	}

	/**
	 * Create ezdefi_exceptions table
	 *
	 * @return bool
	 */
	public function createExceptionsTable()
	{
		$table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

		$query = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			amount_id decimal(20,12) NOT NULL,
			currency varchar(10) NOT NULL,
			order_id int(11),
			status varchar(20),
			payment_method varchar(100),
			explorer_url varchar(200),
			PRIMARY KEY (id)
		);";

		return DB::getInstance()->execute($query);
	}

	/**
	 * Drop ezdefi_exceptions table
	 *
	 * @return bool
	 */
	public function dropExceptionsTable()
	{
		$exception_table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

		return DB::getInstance()->execute("DROP TABLE $exception_table_name");
	}

	/**
	 * Add procedure to generate unique amount id number
	 */
	public function addProcedure()
	{
		$table_name = _DB_PREFIX_ . 'ezdefi_amount_ids';

		DB::getInstance()->execute("DROP PROCEDURE IF EXISTS `ps_ezdefi_generate_amount_id`" );

		DB::getInstance()->execute("
	        CREATE PROCEDURE `ps_ezdefi_generate_amount_id`(
	            IN value DECIMAl(20,12),
			    IN token VARCHAR(10),
			    IN decimal_number INT(2),
                IN life_time INT(11),
			    OUT amount_id DECIMAL(20,12)
			)
			BEGIN
			    DECLARE unique_id INT(11) DEFAULT 0;
			    IF EXISTS (SELECT 1 FROM $table_name WHERE `currency` = token AND `price` = value) THEN
			        IF EXISTS (SELECT 1 FROM $table_name WHERE `currency` = token AND `price` = value AND `amount_key` = 0 AND `expired_time` > NOW()) THEN
				        SELECT MIN(t1.amount_key+1) INTO unique_id FROM $table_name t1 LEFT JOIN $table_name t2 ON t1.amount_key + 1 = t2.amount_key AND t2.price = value AND t2.currency = token AND t2.expired_time > NOW() WHERE t2.amount_key IS NULL;
				        IF((unique_id % 2) = 0) THEN
				            SET amount_id = value + ((unique_id / 2) / POW(10, decimal_number));
				        ELSE
				            SET amount_id = value - ((unique_id - (unique_id DIV 2)) / POW(10, decimal_number));
				        END IF;
			        ELSE
			            SET amount_id = value;
			        END IF;
			    ELSE
			        SET amount_id = value;
			    END IF;
			    INSERT INTO $table_name (amount_key, price, amount_id, currency, expired_time)
                    VALUES (unique_id, value, amount_id, token, NOW() + INTERVAL life_time SECOND + INTERVAL 10 SECOND)
                    ON DUPLICATE KEY UPDATE `expired_time` = NOW() + INTERVAL life_time SECOND + INTERVAL 10 SECOND;
			END
		" );
	}

	/**
	 * Add schedule events to clear ezdefi_amount_ids table and ezdefi_exceptions table
	 */
	public function addEvents()
	{
		$amount_ids_table = _DB_PREFIX_ . 'ezdefi_amount_ids';

		// Add schedule event to clear amount id table
		DB::getInstance()->execute("
			CREATE EVENT IF NOT EXISTS `ps_ezdefi_clear_amount_ids_table`
			ON SCHEDULE EVERY 3 DAY
			DO
				DELETE FROM $amount_ids_table;
		");

		$exceptions_table = _DB_PREFIX_ . 'ezdefi_exceptions';

		// Add schedule event to clear exception table
		DB::getInstance()->execute("
			CREATE EVENT IF NOT EXISTS `ps_ezdefi_clear_exceptions_table`
			ON SCHEDULE EVERY 7 DAY
			DO
				DELETE FROM $exceptions_table;
		");
	}

	/**
	 * Generate unique amount id
	 *
	 * @param $price
	 * @param $currencyData
	 *
	 * @return float|null
	 */
	public function generateUniqueAmountId($price, $currencyData, $acceptable_variation)
	{
		$decimal = $currencyData['decimal'];
		$symbol = $currencyData['symbol'];
		$life_time = (int) $currencyData['lifetime'] * 60;

		$price = round($price, $decimal);

		$db = DB::getInstance();

		$query = "CALL ps_ezdefi_generate_amount_id('$price', '$symbol', $decimal, $life_time, @amount_id)";

		try {
			$db->execute($query);
			$result = $db->getValue("SELECT @amount_id");
		} catch (Exception $e) {
			return null;
		}

		$amount_id = floatval($result);

		$variation_percent = $acceptable_variation / 100;

		$min = floatval($price - ($price * $variation_percent));
		$max = floatval($price + ($price * $variation_percent));

		if(($amount_id < $min) || ($amount_id > $max)) {
			return null;
		}

		return $amount_id;
	}

	/**
	 * Add exception
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function addException($data)
	{
		$keys = array();
		$values = array();

		foreach ( $data as $key => $value ) {
			$keys[] = "$key";
			$values[] = "'$value'";
		}

		$exception_table = _DB_PREFIX_ . 'ezdefi_exceptions';

		$query = "INSERT INTO $exception_table (" . implode( ',', $keys ) . ") VALUES (" . implode( ',', $values ) . ")";

		return DB::getInstance()->execute($query);
	}

	/**
	 * Update exception
	 *
	 * @param array $wheres
	 * @param array $data
	 *
	 * @return bool|void
	 */
	public function updateException($wheres = array(), $data = array())
	{
		$exception_table = _DB_PREFIX_ . 'ezdefi_exceptions';

		if(empty( $data ) || empty($wheres)) {
			return;
		}

		$query = "UPDATE $exception_table SET";

		$comma = " ";

		foreach ($data as $column => $value) {
			if( is_null($value)) {
				$query .= $comma . $column . " = NULL";
			} else {
				$query .= $comma . $column . " = '" . $value . "'";
			}
			$comma = ", ";
		}

		$conditions = array();

		foreach($wheres as $column => $value) {
			if(!empty($value)) {
				$type = gettype($value);
				switch ($type) {
					case 'integer' :
						$conditions[] = " $column = $value ";
						break;
					case 'NULL' :
						$conditions[] = " $column IS NULL ";
						break;
					default :
						$conditions[] = " $column LIKE '$value%' ";
						break;
				}
			}
		}

		if(!empty( $conditions)) {
			$query .= ' WHERE ' . implode($conditions, 'AND');
		}

		return DB::getInstance()->execute($query);
	}

	/**
	 * Get exceptions
	 *
	 * @param array $params
	 * @param int $offset
	 * @param int $per_page
	 *
	 * @return array
	 * @throws PrestaShopDatabaseException
	 */
	public function getExceptions( $params = array(), $offset = 0, $per_page = 15 )
	{
		$exception_table = _DB_PREFIX_ . 'ezdefi_exceptions';

		$order_table = _DB_PREFIX_ . 'orders';

		$customer_table = _DB_PREFIX_ . 'customer';

		$default = array(
			'amount_id' => '',
			'currency' => '',
			'order_id' => '',
			'email' => '',
			'payment_method' => '',
			'status' => ''
		);

		$params = array_merge( $default, $params );

		$query = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.email FROM $exception_table t1 LEFT JOIN ( SELECT orders.id_order as order_id, customers.email as email FROM $order_table orders INNER JOIN $customer_table customers ON orders.id_customer = customers.id_customer ) t2 ON t1.order_id = t2.order_id";

		$sql = array();

		foreach($params as $column => $param) {
			if(!empty($param) && in_array($column, array_keys($default)) && $column != 'amount_id') {
				$sql[] = ($column === 'email') ? " t2.email = '$param' " : " t1.$column = '$param' ";
			}
		}

		if(!empty($sql)) {
			$query .= ' WHERE ' . implode($sql, 'AND');
		}

		if(!empty($params['amount_id'])) {
			$amount_id = $params['amount_id'];
			if(!empty( $sql)) {
				$query .= " AND";
			} else {
				$query .= " WHERE";
			}
			$query .= " amount_id RLIKE '^$amount_id'";
		}

		$query .= " ORDER BY id DESC LIMIT $offset, $per_page";

		$data = DB::getInstance()->executeS($query);

		$total = DB::getInstance()->getValue("SELECT FOUND_ROWS() as total;");

		return array(
			'data' => $data,
			'total' => $total
		);
	}

	/**
	 * Get Order
	 *
	 * @param string $keyword
	 *
	 * @return array|false|mysqli_result|PDOStatement|resource|null
	 * @throws PrestaShopDatabaseException
	 */
	public function getOrders($keyword = '')
	{
		$orderStateId = (int) $this->getConfig('EZDEFI_OS_WAITING');

		$sql = 'SELECT o.id_order as id, o.current_state as current_state, o.total_paid_tax_incl as total, o.date_add as date, customer.email as email, currency.iso_code as currency
	                FROM ' . _DB_PREFIX_ . 'orders o
	                INNER JOIN ' . _DB_PREFIX_ . 'customer customer ON o.id_customer = customer.id_customer
	                INNER JOIN ' . _DB_PREFIX_ . 'currency currency ON o.id_currency = currency.id_currency
	                ' . Shop::addSqlRestriction(false, 'o') . '
	                WHERE `current_state` = ' . (int) $orderStateId;

		if(!empty($keyword)) {
			$sql .= ' AND o.id_order = ' . (int) $keyword;
		} else {
			$sql .= ' ORDER BY date ASC';
		}

		$orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

		return $orders;
	}

	/**
	 * Delete exception
	 *
	 * @param $amount_id
	 * @param $currency
	 * @param $order_id
	 *
	 * @return bool
	 */
	public function deleteAmountIdException($amount_id, $currency, $order_id)
	{
		$table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

		if(is_null($order_id) ) {
			return DB::getInstance()->execute("DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id IS NULL LIMIT 1");
		}

		return DB::getInstance()->execute("DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id = $order_id LIMIT 1");
	}

	/**
	 * Delete exception by order id
	 *
	 * @param $order_id
	 *
	 * @return bool
	 */
	public function deleteExceptionByOrderId($order_id)
	{
		$table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

		$query = "DELETE FROM $table_name WHERE order_id = $order_id";

		return DB::getInstance()->execute($query);
	}
}