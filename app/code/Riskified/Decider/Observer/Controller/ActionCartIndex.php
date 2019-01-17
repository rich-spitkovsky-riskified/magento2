<?php

namespace Riskified\Decider\Observer\Controller;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;

class ActionCartIndex implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ResponseFactory $responseFactory
     * @param UrlInterface $url
     */
    public function __construct(CheckoutSession $checkoutSession, ResponseFactory $responseFactory, UrlInterface $url)
    {
        $this->checkoutSession = $checkoutSession;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (true === $this->checkoutSession->getToRedirect()) {
            $redirect = $this->responseFactory->create();
            $url = $this->url->getUrl('checkout');
            $redirect->setRedirect($url)->sendResponse();
            $this->checkoutSession->unsToRedirect();
            die();
        }
    }
}