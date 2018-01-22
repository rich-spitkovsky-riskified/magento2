<?php

namespace Riskified\Decider\Model\Resource;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DeclineSent extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('riskified_decider_declination_sent', 'entity_id');
    }
}