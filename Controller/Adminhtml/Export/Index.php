<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Export;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\ImportExport\Controller\Adminhtml\Export as ExportController;
use Magento\Framework\Controller\ResultFactory;

class Index extends ExportController implements HttpGetActionInterface
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('StoreKeeper_StoreKeeper::system_convert_sk_export_data');
        $resultPage->getConfig()->getTitle()->prepend(__('Import/Export'));
        $resultPage->getConfig()->getTitle()->prepend(__('StoreKeeper Export Data'));
        $resultPage->addBreadcrumb(__('Export'), __('Export'));

        return $resultPage;
    }
}
