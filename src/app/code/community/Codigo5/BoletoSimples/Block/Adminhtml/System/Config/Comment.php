<?php

class Codigo5_BoletoSimples_Block_Adminhtml_System_Config_Comment extends Mage_Core_Block_Template
{
    public function getVersion()
    {
        return $this->_getHelper()->getVersion();
    }

    public function getLogoUrl()
    {
        return $this->_getHelper()->getLogoUrl();
    }

    public function getRegisterUrl()
    {
        return $this->_getHelper()->getRegisterUrl();
    }

    public function getWebhooksUrl()
    {
        return $this->_getHelper()->getWebhooksUrl();
    }

    public function getChangeAddressLinesUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/system_config/edit', array('section' => 'customer'));
    }

    public function getAuthorName()
    {
        return $this->_getHelper()->getAuthorName();
    }

    public function getAuthorUrl()
    {
        return $this->_getHelper()->getAuthorUrl();
    }

    public function getAuthorLogoUrl()
    {
        return $this->_getHelper()->getAuthorLogoUrl();
    }

    protected function _getHelper()
    {
        return Mage::helper('codigo5_boletosimples/info');
    }
}
