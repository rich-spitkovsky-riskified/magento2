<?php
namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Api;

class OrderSaveAfter implements ObserverInterface
{
    private $_logger;
    private $_orderApi;

    public function __construct(
        \Riskified\Decider\Logger\Order $logger,
        \Riskified\Decider\Api\Order $orderApi
    )
    {
        $this->_logger = $logger;
        $this->_orderApi = $orderApi;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_logger->info(
            __("Called Riskified OrderSaveAfter Event Observer. Starting order processing.")
        );
        $order = $observer->getOrder();

        if (!$order) {
            $this->_logger->info(
                __("Order is not recognized.")
            );

            return;
        }

        $this->_logger->info(
            __("Order #" . $order->getIncrementId())
        );

        if ($order->dataHasChangedFor('state')) {
            $this->_logger->info(
                __("State of the order was changed. Processing Post Action.")
            );

            if ($order->getPayment()->getMethod() == 'authorizenet_directpost') {
                $this->_logger->info(
                    __("Order has been paid with authorize.net method. Processing.")
                );
                try {
                    $this->_orderApi->post($order, Api::ACTION_UPDATE);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            } else {
                $this->_logger->info(
                    __("Order was not paid with authorize.net. Aborting function.")
                );
            }
        } else {
            $this->_logger->info(
                __("State is not changed. Aborting sync.")
            );
        }
    }
}
