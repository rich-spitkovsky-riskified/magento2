<?php

namespace Riskified\Decider\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Riskified\Decider\Api\Config;
use Magento\Framework\View\Element\Template;

class Js extends Template
{
    private $apiConfig;
    private $session;
    private $checkoutSession;

    public function __construct(
        Context $context,
        Config $apiConfig,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->apiConfig = $apiConfig;
        $this->session = $context->getSession();
        $this->checkoutSession = $checkoutSession;
        
        parent::__construct($context, $data);
    }

    public function getSessionId()
    {
        return $this->session->getSessionId();
    }

    public function isEnabled()
    {
        return $this->apiConfig->isEnabled();
    }

    public function getShopDomain()
    {
        return $this->apiConfig->getShopDomain();
    }

    public function getConfigStatusControlActive()
    {
        return $this->apiConfig->getConfigStatusControlActive();
    }

    public function getExtensionVersion()
    {
        return $this->apiConfig->getExtensionVersion();
    }

    public function getConfigBeaconUrl()
    {
        return $this->apiConfig->getConfigBeaconUrl();
    }

    /**
     * @return bool
     */
    public function isDecoEnabled()
    {
        return $this->apiConfig->isDecoEnabled();
    }
    /**
     * @return bool|null
     */
    public function getDecision()
    {
        $decision = $this->checkoutSession->getDecision();
        $this->checkoutSession->unsDecision();

        return $decision;
    }
    
}
