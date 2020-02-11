<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (defined('_PS_MODULE_DIR_')) {
	require_once _PS_MODULE_DIR_ . 'ezdefi/classes/EzdefiHelper.php';
	require_once _PS_MODULE_DIR_ . 'ezdefi/classes/EzdefiApi.php';
	require_once _PS_MODULE_DIR_ . 'ezdefi/classes/EzdefiDb.php';
	require_once _PS_MODULE_DIR_ . 'ezdefi/classes/EzdefiConfig.php';
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Ezdefi extends PaymentModule
{
	protected $configFields = array(
		'EZDEFI_API_URL',
		'EZDEFI_API_KEY',
		'EZDEFI_AMOUNT_ID',
		'EZDEFI_EZDEFI_WALLET',
		'EZDEFI_ACCEPTABLE_VARIATION',
		'EZDEFI_CURRENCY'
	);

    protected $api;

    protected $config;

    protected $db;

    protected $helper;

    protected $html;

    protected $postErrors = array();

    public function __construct()
    {
        $this->name = 'ezdefi';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'ezDeFi';
	    $this->bootstrap = true;

        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );

        $this->controllers = array(
        	'adminAjax' => 'AdminAjaxEzdefi'
        );

        parent::__construct();

        $this->displayName = $this->l('Pay with Cryptocurrencies');
        $this->description = $this->l('Accept Bitcoin, Ethereum and Cryptocurrencies on your Prestashop store with ezDeFi');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        if (!Configuration::get('ezdefi')) {
            $this->warning = $this->l('No name provided');
        }

	    $this->api = new EzdefiApi();

        $this->config = new EzdefiConfig();

        $this->db = new EzdefiDb();

        $this->helper = new EzdefiHelper();
    }

    public function install()
    {
		if(parent::install() && $this->installDb() && $this->installTab() && $this->installOrderState() && $this->registerHook('paymentOptions')) {
			$this->active = true;
			return true;
		}

		return false;
    }

    public function uninstall()
    {
        if(parent::uninstall() && $this->uninstallDb() && $this->uninstallTab()) {
        	return true;
        }

        return false;
    }

    public function installDb()
    {
	    $this->db->createAmountIdsTable();

	    $this->db->createExceptionsTable();

	    $this->db->addProcedure();

	    $this->db->addEvents();

		return true;
    }

    public function installTab()
    {
	    $tab = new Tab();
	    $tab->active = 1;
	    $tab->class_name = 'AdminAjaxEzdefi';
	    $tab->name = array();
	    $tab->id_parent = -1;
	    $tab->module = $this->name;

	    foreach (Language::getLanguages(true) as $lang) {
		    $tab->name[$lang['id_lang']] = $this->name;
	    }

	    return $tab->add();
    }

    public function installOrderState()
    {
	    if (Configuration::get('EZDEFI_OS_WAITING') > 0) {
		    return true;
	    }

	    $orderState = new OrderState();
	    $orderState->name = array_fill(0, 10, 'Awaiting for cryptocurrencies payment');
	    $orderState->send_email = false;
	    $orderState->color = '#4169E1';
	    $orderState->hidden = false;
	    $orderState->delivery = false;
	    $orderState->logable = false;
	    $orderState->invoice = false;
	    $orderState->module_name = $this->name;

	    if(!$orderState->add()) {
	    	return false;
	    }

	    if (Shop::isFeatureActive()) {
		    $shops = Shop::getShops();
		    foreach ($shops as $shop) {
			    Configuration::updateValue('EZDEFI_OS_WAITING', (int) $orderState->id, false, null, (int)$shop['id_shop']);
		    }
	    } else {
		    Configuration::updateValue('EZDEFI_OS_WAITING', (int) $orderState->id);
	    }

	    return true;
    }

    public function uninstallDb()
    {
	    $this->db->dropAmountIdsTable();

	    $this->db->dropExceptionsTable();

	    return true;
    }

    public function uninstallTab()
    {
	    $id_tab = (int) Tab::getIdFromClassName('AdminAjaxEzdefi');

	    if ($id_tab) {
		    $tab = new Tab($id_tab);
		    if (Validate::isLoadedObject($tab)) {
			    return ($tab->delete());
		    } else {
			    $return = false;
		    }
	    } else {
		    $return = true;
	    }

	    return $return;
    }

    public function hookPaymentOptions($params)
    {
    	$cart = $this->context->cart;

    	$total = $cart->getOrderTotal(true, Cart::BOTH);

    	$from = $this->helper->getCurrencyIsoCode((int) $cart->id_currency);

	    $acceptedCurrencies = $this->config->getAcceptedCurrencies();

	    $exchanges = $this->helper->getExchanges($total, $from);

    	$this->smarty->assign([
    		'exchanges' => $exchanges,
		    'acceptedCurrencies' => $acceptedCurrencies,
		    'modulePath' => $this->_path
	    ]);

        $option = new PaymentOption();
        $option->setModuleName($this->name)
               ->setInputs([['type' => 'hidden', 'name' => 'ezdefi_currency', 'value' => '']])
               ->setCallToActionText('Pay with cryptocurrency')
               ->setAction($this->context->link->getModuleLink($this->name, 'process', array(), true))
               ->setAdditionalInformation($this->fetch('module:ezdefi/views/templates/front/ezdefi_checkout.tpl'));

        $payment_options = array(
            $option
        );

        return $payment_options;
    }

    protected function postValidation()
    {
        if(!$this->isSubmit()) {
            return;
        }

        if(!$this->getValue('EZDEFI_API_URL')) {
            $this->setPostError(
                $this->l('Gateway API Url is required')
            );
        }

        if(!$this->getValue('EZDEFI_API_KEY')) {
            $this->setPostError(
                $this->l('Gateway API Key is required')
            );
        }

        if(!$this->isAmountIdEnabled() && !$this->isEzdefiWalletEnabled()) {
            $this->setPostError(
                $this->l('You must enabled at least one payment methods')
            );
        }

        if($this->isAmountIdEnabled() && !$this->getValue('EZDEFI_ACCEPTABLE_VARIATION')) {
            $this->setPostError(
                $this->l('Acceptable price variation is required')
            );
        }
    }

    protected function postProcess()
    {
        if(!$this->isSubmit()) {
            return;
        }

	    foreach($this->configFields as $configField) {
		    $this->config->updateConfig(
			    $configField,
			    $this->getValue($configField)
		    );
	    }
    }

    public function getContent()
    {
	    if($this->isSubmit()) {
		    $this->postValidation();
	    }

	    $html = '';

	    if(!empty($this->getPostErrors())) {
		    foreach($this->getPostErrors() as $error) {
			    $html .= $this->displayError($error);
		    }
	    }

	    $this->postProcess();

    	$tabModule = Tools::getValue('tab_module');
    	$activeTab = '';

    	if(!$tabModule || empty($tabModule)) {
    		$activeTab = 'ezdefi-settings';
	    } else if(($tabModule === 'ezdefi-settings') || ($tabModule === 'ezdefi-logs')) {
    		$activeTab = $tabModule;
	    }

    	$this->loadAssets();

    	$this->context->smarty->assign(array(
    		'activeTab' => $activeTab,
		    'settings_output' => $this->renderForm(),
		    'logs_output' => '',
		    'ezdefiAdminUrl' => $this->context->link->getAdminLink('AdminAjaxEzdefi')
	    ));

    	$html .= $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/admin.tpl');

    	return $html;
    }

    public function loadAssets()
    {
	    $css = array(
		    $this->_path . 'views/css/select2.min.css',
		    $this->_path . 'views/css/settings.css',
		    $this->_path . 'views/css/logs.css'
	    );

	    $this->context->controller->addCSS($css);

	    $js = array(
		    $this->_path . 'views/js/jquery.validate.min.js',
		    $this->_path . 'views/js/select2.min.js',
		    $this->_path . 'views/js/admin.js',
		    $this->_path . 'views/js/logs.js'
	    );

	    $this->context->controller->addJqueryUI('ui.sortable');
	    $this->context->controller->addJS($js);
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General Settings'),
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Gateway API Url'),
                        'name' => 'EZDEFI_API_URL',
                        'required' => true,
                        'placeholder' => 'https://merchant-api.ezdefi.com/api'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Gateway API Key'),
                        'name' => 'EZDEFI_API_KEY',
                        'required' => true,
                        'desc' => '<a target="_blank" href="https://merchant.ezdefi.com/register?utm_source=prestashop-download">' . $this->l('Register to get API Key') . '</a>'
                    ),
                    array(
                        'type' => 'ezdefi_method_checkbox',
                        'label' => $this->l('Payment methods'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Acceptable price variation'),
                        'name' => 'EZDEFI_ACCEPTABLE_VARIATION'
                    ),
                )
            )
        );

        $fields_form_currency = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Accepted Cryptocurrency')
                ),
                'input' => array(
                    array(
                        'type' => 'currency_table',
                        'label' => $this->l('asfasf'),
                        'name' => 'EZDEFI_CURRENCY',
                        'required' => true
                    )
                )
            )
        );

        $form_submit_button = array(
            'form_submit_button' => array(
                'text' => 'Save'
            )
        );

        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                          '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues()
        );

        return $helper->generateForm(array($fields_form, $fields_form_currency, $form_submit_button));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'EZDEFI_API_URL' => $this->getValue('EZDEFI_API_URL', $this->config->getConfig('EZDEFI_API_URL')),
            'EZDEFI_API_KEY' => $this->getValue('EZDEFI_API_KEY', $this->config->getConfig('EZDEFI_API_KEY')),
            'EZDEFI_AMOUNT_ID' => $this->getValue('EZDEFI_AMOUNT_ID', $this->config->getConfig('EZDEFI_AMOUNT_ID')),
            'EZDEFI_ACCEPTABLE_VARIATION' => $this->getValue('EZDEFI_ACCEPTABLE_VARIATION', $this->config->getConfig('EZDEFI_ACCEPTABLE_VARIATION')),
            'EZDEFI_EZDEFI_WALLET' => $this->getValue('EZDEFI_EZDEFI_WALLET', $this->config->getConfig('EZDEFI_EZDEFI_WALLET')),
            'EZDEFI_CURRENCY' => $this->getCurrencyConfig(),
        );
    }

    protected function isSubmit()
    {
        return Tools::isSubmit('submit' . $this->name);
    }

    protected function getValue($key, $default = null)
    {
        if(is_null($default)) {
            return Tools::getValue($key);
        }

        return Tools::getValue($key, $default);
    }

    protected function isAmountIdEnabled()
    {
        return ($this->getValue('EZDEFI_AMOUNT_ID') == 1);
    }

    protected function isEzdefiWalletEnabled()
    {
        return ($this->getValue('EZDEFI_EZDEFI_WALLET') == 1);
    }

    protected function getCurrencyConfig()
    {
    	$config = $this->config->getAcceptedCurrencies();

    	if(!empty($config)) {
    		return $config;
	    }

		return $this->getDefaultCurrencies();
    }

    protected function getDefaultCurrencies()
    {
    	return [
		    [
			    'logo' => $this->_path . 'views/images/newsd-icon.png',
				'symbol' => 'newsd',
				'name' => 'NewSD',
				'desc' => 'NewSD',
				'decimal' => 4,
			    'discount' => 0,
			    'lifetime' => 15,
			    'block_confirm' => 1,
			    'wallet_address' => '',
			    'decimal_max' => 6
		    ],
		    [
			    'logo' => $this->_path . 'views/images/bitcoin-icon.png',
				'symbol' => 'btc',
				'name' => 'Bitcoin',
				'desc' => 'Bitcoin',
				'decimal' => 8,
			    'discount' => 0,
			    'lifetime' => 15,
			    'block_confirm' => 1,
			    'wallet_address' => '',
			    'decimal_max' => 8
		    ],
		    [
		        'logo' => $this->_path . 'views/images/ethereum-icon.png',
				'symbol' => 'eth',
				'name' => 'Ethereum',
				'desc' => 'Ethereum',
				'decimal' => 8,
			    'discount' => 0,
			    'lifetime' => 15,
			    'block_confirm' => 1,
			    'wallet_address' => '',
			    'decimal_max' => 18
		    ]
	    ];
    }

    public function setPostError($error)
    {
        $this->postErrors[] = $error;
    }

    public function getPostErrors()
    {
        return $this->postErrors;
    }
}

