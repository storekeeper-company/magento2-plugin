<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Export extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'StoreKeeper_StoreKeeper::export';
}
