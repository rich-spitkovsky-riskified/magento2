<?php

namespace Riskified\Decider\Observer\Controller;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ActionCybersourcePlaceorder implements ObserverInterface
{
    const CYBERSOURCE_DECISION_FAIL = 'DECLINE';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        $decision = $request->getParam('decision');

        if ($decision == self::CYBERSOURCE_DECISION_FAIL) {
            $this->checkoutSession->setDecision(false);
            $this->checkoutSession->setToRedirect(true);
        }
    }
}