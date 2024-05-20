<?php

namespace StoreKeeper\StoreKeeper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StoreKeeper\StoreKeeper\Api\ApiClient;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Framework\Stdlib\DateTime\DateTime;
use StoreKeeper\StoreKeeper\Helper\Api\Auth as AuthHelper;
use StoreKeeper\StoreKeeper\Logger\Logger;

class ProductApiClient extends ApiClient
{
    const STOREKEEPER_PRODUCTS_MODULE_NAME = 'ProductsModule';
    const SYNC_STATUS_SUCCESS = 'success';
    const SYNC_STATUS_FAILED = 'failed';
    const PRODUCT_UPDATE_STATUS_SUCCESS = 'success';
    const PRODUCT_UPDATE_STATUS_ERROR = 'error';

    private OrderApiClient $orderApiClient;
    private ProductUrl $productUrl;
    private BackendUrl $backendUrl;
    private DateTime $dateTime;
    private AuthHelper $authHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OrderApiClient $orderApiClient,
        ProductUrl $productUrl,
        BackendUrl $backendUrl,
        DateTime $dateTime,
        AuthHelper $authHelper,
        Logger $logger
    ) {
        parent::__construct($scopeConfig, $logger);
        $this->orderApiClient = $orderApiClient;
        $this->productUrl = $productUrl;
        $this->backendUrl = $backendUrl;
        $this->dateTime = $dateTime;
        $this->authHelper = $authHelper;
    }

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    private function getProductModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_PRODUCTS_MODULE_NAME, $storeId);
    }

    /**
     * @param string $storeId
     * @param string $countryId
     * @return array
     * @throws \Exception
     */
    public function getTaxRates(string $storeId, string $countryId): array
    {
        return $this->getProductModule($storeId)->listTaxRates(
            0,
            100,
            null,
            [
                [
                    'name' => 'country_iso2__=',
                    'val' => $countryId
                ]
            ]
        );
    }

    /**
     * @param string $storeId
     * @param string $storeKeeperProductId
     * @param ProductInterface|null $product
     * @param string $status
     * @param array $exceptionData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    public function setShopProductObjectSyncStatusForHook(string $storeId, string $storeKeeperProductId, ?ProductInterface $product, string $status, array $exceptionData): bool
    {
        $shopInfo = $this->authHelper->getShopInfo($storeId);
        $pluginVersion = implode(', ', [
            $shopInfo["platform_name"] . ': ' . $shopInfo["platform_version"],
            $shopInfo["software_name"] . ': ' . $shopInfo["software_version"]
        ]);

        $data = [
            'status' => self::SYNC_STATUS_FAILED,
            'shop_product_id' => $storeKeeperProductId,
            'extra' => [
                'plugin_version' => $pluginVersion
            ]
        ];

        if ($exceptionData) {
            $data['last_error_message'] = $exceptionData['last_error_message'];
            $data['last_error_details'] = $exceptionData['last_error_details'];
        }

        if ($product) {
            $viewUrl = $this->productUrl->getUrlInStore($product);
            $editUrl = $this->backendUrl->getUrl('catalog/product/edit', ['id' => $product->getId()]);
            $dateSynchronized = $this->dateTime->date();
            $productId = $product->getId();

            if ($status == self::PRODUCT_UPDATE_STATUS_SUCCESS) {
                $data['status'] = self::SYNC_STATUS_SUCCESS;
                $data['extra'] = [
                    'product_id' => $productId,
                    'view_url' => $viewUrl,
                    'edit_url' => $editUrl,
                    'date_synchronized' => $dateSynchronized,
                    'plugin_version' => $pluginVersion
                ];
            } elseif ($status == self::PRODUCT_UPDATE_STATUS_ERROR) {
                $data['extra'] = [
                    'product_id' => $productId,
                    'view_url' => $viewUrl,
                    'edit_url' => $editUrl,
                    'plugin_version' => $pluginVersion
                ];
            }
        }

        $result = true;
        try {
            $this->orderApiClient->getShopModule($storeId)->setShopProductObjectSyncStatusForHook($data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
            $result = false;
        }

        return $result;
    }
}
