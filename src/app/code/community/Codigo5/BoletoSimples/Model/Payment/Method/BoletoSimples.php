<?php

class Codigo5_BoletoSimples_Model_Payment_Method_BoletoSimples extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'boletosimples';

    protected $_code = self::CODE;

    protected $_canUseInternal         = true;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded     = true;

    public function initialize($action, $stateObject)
    {
        if ($action === 'init') {
            $status = $this->getConfigData('order_status');

            if ($status) {
                $stateObject->setStatus($status);

                $helper = Mage::helper('codigo5_boletosimples');
                $state = $helper->getOrderStateByStatus($status);

                $stateObject->setState($state);
                $stateObject->setIsNotified(true);
            }
        }

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('codigo5_boletosimples/payment/request');
    }

    public function process(Mage_Sales_Model_Order $order)
    {
        $helper = Mage::helper('codigo5_boletosimples');
        $helper->ensureLibrariesLoad();

        $builder = Mage::getModel('codigo5_boletosimples/order_builder')->build($order);
        $bankBillet = BoletoSimples\BankBillet::create($builder->getData());

        if (!$bankBillet->isPersisted()) {
            throw new Codigo5_BoletoSimples_Exception(
                Mage::helper('codigo5_boletosimples/webservice')->humanizeResourceErrors($bankBillet)
            );
        }

        $paymentMethod = $helper->getPaymentMethod($order->getStoreId());
        $newStatus = 'boletosimples_waiting_payment';

        $order
            ->setBoletosimplesBankBilletId($bankBillet->id)
            ->setBoletosimplesBankBilletUrl($bankBillet->shorten_url)
            ->setState(
               $helper->getOrderStateByStatus($newStatus),
               $newStatus,
               $helper->__('Bank billet has been created'),
               false
            )
            ->save();
    }

    public function handleWebhook(array $webhook)
    {
        switch ($webhook['event_code']) {
            case 'bank_billet.paid':
                $orderIncrementId = @$webhook['object']['meta'];
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
                $newStatus = 'boletosimples_paid';

                if ($order->getId() && $order->getStatus() !== $newStatus) {
                    $helper = Mage::helper('codigo5_boletosimples');
                    $comment = $helper->__('Bank billet has been paid');
                    $notify = true;
                    $transactionSave = Mage::getModel('core/resource_transaction');

                    // Capture
                    if ($order->canInvoice()) {
                        $invoice = $order->prepareInvoice();
                        $invoice->register();

                        $invoice->addComment($comment, $notify);
                        $invoice->setEmailSent($notify);

                        $transactionSave->addObject($invoice);

                        $invoice->sendEmail($notify, $comment);
                    }

                    // Change status
                    $order->setState(
                        $helper->getOrderStateByStatus($newStatus),
                        $newStatus,
                        $comment,
                        true
                    );
                    $transactionSave->addObject($order);

                    // Transactionally save
                    $transactionSave->save();
                }
                break;
        }
    }

    public function getSupportedWebhooksEvents()
    {
        return array('bank_billet.paid');
    }
}
