<?php

namespace Riskified\Decider\Model;
class DeclineSent extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Riskified\Decider\Model\Resource\DeclineSent');
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getEntryId()
    {
        return $this->getData('entry_id');
    }

    /**
     * Get order ID
     *
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    /**
     * Set ID
     *
     * @param int $entry_id
     * @return int|object
     */
    public function setEntryId($entry_id)
    {
        return $this->setData('entry_id', $entry_id);
    }

    /**
     * Set order ID
     *
     * @param int $order_id
     * @return int|object
     */
    public function setOrderId($order_id)
    {
        return $this->setData('order_id', $order_id);
    }
}