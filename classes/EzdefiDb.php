<?php

class EzdefiDb
{
    protected $config;

    public function __construct()
    {
        $this->config = new EzdefiConfig();
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
			explorer_url varchar(200) DEFAULT NULL,
			confirmed tinyint(1) DEFAULT 0 NOT NULL,
			is_show tinyint(1) DEFAULT 1 NOT NULL,
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
	 * Add schedule events to clear ezdefi_exceptions table
	 */
	public function addEvents()
	{
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
	public function updateExceptions($wheres = array(), $data = array(), $limit = null)
	{
		$exception_table = _DB_PREFIX_ . 'ezdefi_exceptions';

		if(empty( $data ) || empty($wheres)) {
			return;
		}

		$query = "UPDATE $exception_table SET";

		$comma = " ";

		foreach ($data as $column => $value) {
			if( is_null($value)) {
				$query .= $comma . $column . " IS NULL";
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
                        $conditions[] = " $column = '$value' ";
						break;
				}
			}
		}

		if(!empty( $conditions)) {
			$query .= ' WHERE ' . implode($conditions, 'AND');
		}

        if(is_numeric($limit)) {
            $query .= " ORDER BY id DESC LIMIT $limit";
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
			'status' => '',
            'confirmed' => '',
		);

		$params = array_merge( $default, $params );

		$query = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.email FROM $exception_table t1 LEFT JOIN ( SELECT orders.id_order as order_id, customers.email as email FROM $order_table orders INNER JOIN $customer_table customers ON orders.id_customer = customers.id_customer ) t2 ON t1.order_id = t2.order_id";

		$sql = array();

        foreach( $params as $column => $param ) {
            if( $column === 'type' ) {
                switch ( $params['type'] ) {
                    case 'pending' :
                        $sql[] = " t1.confirmed = 0 ";
                        $sql[] = " t1.explorer_url IS NOT NULL ";
                        break;
                    case 'confirmed' :
                        $sql[] = " t1.confirmed = 1 ";
                        break;
                    case 'archived' :
                        $sql[] = " t1.confirmed = 0 ";
                        $sql[] = " t1.explorer_url IS NULL ";
                        break;
                }
            }  elseif ( ! empty( $param ) && in_array( $column, array_keys( $default ) ) ) {
                switch ( $column ) {
                    case 'amount_id' :
                        $sql[] = " t1.amount_id RLIKE '^$param' ";
                        break;
                    case 'email' :
                        $sql[] = " t2.email = '$param' ";
                        break;
                    default :
                        $sql[] = " t1.$column = '$param' ";
                        break;
                }
            }
        }

		if(!empty($sql)) {
			$query .= ' WHERE ' . implode($sql, 'AND');
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
		$orderStateId = (int) $this->config->getConfig('EZDEFI_OS_WAITING');

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

	public function deleteExceptions($wheres = array())
    {
        $table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

        if( empty( $wheres ) ) {
            return;
        }

        $query = "DELETE FROM $table_name";

        $conditions = array();

        foreach( $wheres as $column => $value ) {
            $type = gettype( $value );
            switch ($type) {
                case 'integer' :
                    $conditions[] = " $column = $value ";
                    break;
                case 'NULL' :
                    $conditions[] = " $column IS NULL ";
                    break;
                default :
                    $conditions[] = " $column = '$value' ";
                    break;
            }
        }

        if( ! empty( $conditions ) ) {
            $query .= ' WHERE ' . implode( $conditions, 'AND' );
        }

        return DB::getInstance()->execute($query);
    }

    public function deleteException($exception_id)
    {
        $table_name = _DB_PREFIX_ . 'ezdefi_exceptions';

        $query = "DELETE FROM $table_name WHERE id = $exception_id";

        return DB::getInstance()->execute($query);
    }
}