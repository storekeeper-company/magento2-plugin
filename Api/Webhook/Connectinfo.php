<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Json\Helper\Data;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class Connectinfo implements InfoWebhookInterface
{
    private Auth $authHelper;
    private Http $request;
    private Data $jsonHelper;

    /**
     * @param Auth $authHelper
     * @param Http $request
     * @param Data $jsonHelper
     */
    public function __construct(
        Auth $authHelper,
        Http $request,
        Data $jsonHelper
    ) {
        $this->authHelper = $authHelper;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExecute(): void
    {
        $storeId = $this->request->getQuery('storeId');
        $token = $this->request->getQuery('token');
        $infoHookData = $this->authHelper->generateInfoHookData($storeId);

        http_response_code(200);
        header("Content-Type: application/json");
        echo $this->jsonHelper->jsonEncode($infoHookData);
        exit;
    }
}
