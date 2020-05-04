<?php

class AdminEzdefiExceptionController extends ModuleAdminController
{
    public $bootstrap = true;

    public function initContent()
    {
        $css = array(
            $this->module->getLocalPath() . 'views/css/select2.min.css',
            $this->module->getLocalPath() . 'views/css/logs.css'
        );

        $this->addCSS($css);

        $js = array(
            $this->module->getLocalPath() . 'views/js/select2.min.js',
            $this->module->getLocalPath() . 'views/js/logs.js'
        );

        $this->addJS($js);

        Media::addJsDef(array(
            'ezdefiAdminUrl' => $this->context->link->getAdminLink('AdminAjaxEzdefi')
        ));

        $this->context->smarty->assign(array(
            'url' => $_SERVER['REQUEST_URI'],
            'current_type' => Tools::getValue('type'),
        ));

        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/logs.tpl');

        parent::initContent();
    }
}