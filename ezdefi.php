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
        'EZDEFI_PUBLIC_KEY'
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
        $this->version = '2.0.0';
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
		if(parent::install() && $this->installDb() && $this->installTab() && $this->addMenuLink() && $this->installOrderState() && $this->registerHook('paymentOptions')) {
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
	    $this->db->createExceptionsTable();

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

    public function addMenuLink()
    {
        $tab             = new \Tab();
        $tab->id_parent  = 42;
        $tab->name = array_fill_keys(array_values(\Language::getIDs(true)), 'Ezdefi Exceptions');
        $tab->class_name = 'AdminEzdefiException';
        $tab->module     = $this->name;
        $tab->active = 1;

        return $tab->save();
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

        $coins = $this->api->getWebsiteCoins();

        $to = implode(',', array_map( function ( $coin ) {
            return $coin['token']['symbol'];
        }, $coins ) );

	    $exchanges = $this->api->getTokenExchanges($total, $from, $to);

    	$this->smarty->assign([
    		'exchanges' => $exchanges,
		    'coins' => $coins,
		    'modulePath' => $this->_path
	    ]);

        $option = new PaymentOption();
        $option->setModuleName($this->name)
               ->setInputs([['type' => 'hidden', 'name' => 'ezdefi_coin', 'value' => '']])
               ->setCallToActionText('Pay with Cryptocurrencies')
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

        if(!$this->getValue('EZDEFI_PUBLIC_KEY')) {
            $this->setPostError(
                $this->l('Website ID is required')
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

	    $callbackUrl = $this->context->link->getModuleLink($this->name, 'callback', array(), true);

	    $this->api->updateCallbackUrl($callbackUrl);
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

    	$this->loadAssets();

    	$this->context->smarty->assign(array(
		    'settings_output' => $this->renderForm(),
		    'ezdefiAdminUrl' => $this->context->link->getAdminLink('AdminAjaxEzdefi')
	    ));

    	$html .= $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/admin.tpl');

    	return $html;
    }

    public function loadAssets()
    {
	    $css = array(
		    $this->_path . 'views/css/settings.css',
	    );

	    $this->context->controller->addCSS($css);

	    $js = array(
		    $this->_path . 'views/js/jquery.validate.min.js',
		    $this->_path . 'views/js/admin.js',
	    );

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
                        'type' => 'text',
                        'label' => $this->l('Website ID'),
                        'name' => 'EZDEFI_PUBLIC_KEY',
                        'required' => true,
                    ),
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

        return $helper->generateForm(array($fields_form, $form_submit_button));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'EZDEFI_API_URL' => $this->getValue('EZDEFI_API_URL', $this->config->getConfig('EZDEFI_API_URL')),
            'EZDEFI_API_KEY' => $this->getValue('EZDEFI_API_KEY', $this->config->getConfig('EZDEFI_API_KEY')),
            'EZDEFI_PUBLIC_KEY' => $this->getValue('EZDEFI_PUBLIC_KEY', $this->config->getConfig('EZDEFI_PUBLIC_KEY')),
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

    public function setPostError($error)
    {
        $this->postErrors[] = $error;
    }

    public function getPostErrors()
    {
        return $this->postErrors;
    }
}

