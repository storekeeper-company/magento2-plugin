<?php

namespace StoreKeeper\StoreKeeper\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Locale\TranslatedLists;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder\CollectionFactory as StoreKeeperFailedSyncOrderCollectionFactory;

class Info
{
    private Auth $authHelper;
    private CollectionFactory $orderCollectionFactory;
    private ComponentRegistrar $componentRegistrar;
    private Config $configHelper;
    private DesignInterface $design;
    private FileDriver $fileDriver;
    private Json $json;
    private ProductMetadataInterface $productMetadata;
    private StoreKeeperFailedSyncOrderCollectionFactory $storeKeeperFailedSyncOrderCollectionFactory;
    private TimezoneInterface $timezone;
    private TranslatedLists $translatedLists;
    private UrlInterface $backendUrl;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param CollectionFactory $orderCollectionFactory
     * @param Config $configHelper
     * @param Json $json
     * @param ProductMetadataInterface $productMetadata
     * @param StoreKeeperFailedSyncOrderCollectionFactory $storeKeeperFailedSyncOrderCollectionFactory
     * @param TimezoneInterface $timezone
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        Auth $authHelper,
        CollectionFactory $orderCollectionFactory,
        Config $configHelper,
        ComponentRegistrar $componentRegistrar,
        DesignInterface $design,
        FileDriver $fileDriver,
        Json $json,
        ProductMetadataInterface $productMetadata,
        StoreKeeperFailedSyncOrderCollectionFactory $storeKeeperFailedSyncOrderCollectionFactory,
        TimezoneInterface $timezone,
        TranslatedLists $translatedLists,
        UrlInterface $backendUrl
    ) {
        $this->authHelper = $authHelper;
        $this->configHelper = $configHelper;
        $this->componentRegistrar = $componentRegistrar;
        $this->design = $design;
        $this->fileDriver = $fileDriver;
        $this->json = $json;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productMetadata = $productMetadata;
        $this->storeKeeperFailedSyncOrderCollectionFactory = $storeKeeperFailedSyncOrderCollectionFactory;
        $this->timezone = $timezone;
        $this->translatedLists = $translatedLists;
        $this->backendUrl = $backendUrl;
    }

    /**
     * @param string $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInfohookData(string $storeId): array
    {
        // retrieve current module version
        $moduleDir = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'StoreKeeper' . '_' . 'StoreKeeper'
        );
        $composerJsonPath = $moduleDir . DIRECTORY_SEPARATOR . 'composer.json';
        $composerFile = $this->fileDriver->fileGetContents($composerJsonPath);
        $composerJson = $this->json->unserialize($composerFile);
        $capabilities = [
            'b2s_payment_method', // has the capability to use the payment method from backoffice
            's2b_report_system_status' // display failed order and other(?) report stats on dashboard
        ];

        $sync_mode = null;

        if ($this->configHelper->hasMode($storeId, Config::SYNC_NONE)) {
            $sync_mode = 'sync-mode-none';
        } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_ALL)) {
            $sync_mode = 'sync-mode-full-sync';
        } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS)) {
            $sync_mode = 'sync-mode-product-only';
        } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_ORDERS)) {
            $sync_mode = 'sync-mode-order-only';
        }

        $response = [
            "success" => true,
            'vendor' => $this->authHelper->getVendor(),
            'platform_name' => $this->authHelper->getPlatformName(),
            'platform_version' => $this->productMetadata->getVersion(),
            'software_name' => $this->authHelper->getSoftwareName(),
            'software_version' => $composerJson['version'],
            'task_failed_quantity' => $this->getAmountOfFailedTasks(),
            'plugin_settings_url' => $this->getPluginSettingsUrl(),
            'extra' => [
                'name' => $composerJson['name'],
                'description' => $composerJson['description'],
                'now_date' => $this->getCurrentDateTime(),
                'active_theme' => $this->getCurrentThemeTitle(),
                'url' => $this->authHelper->getStoreBaseUrl(),
                'sync_mode' => $sync_mode,
                'active_capability' => $capabilities,
                'language' => $this->getLanguageLabel($storeId),
                'system_status' => [
                    'order' => [
                        'last_date' => $this->getLastOrderDateTime(),
                        'last_synchronized_date' => $this->getLastSynchronizedOrderDateTime(),
                        'ids_with_failed_tasks' => $this->getIdsWithFailedTasks(),
                        'last_date_of_failed_task' => $this->getLastDateOfFailedTask()
                    ]
                ]
            ],
        ];

        return $response;
    }

    /**
     * @return string
     */
    public function getCurrentThemeTitle()
    {
        return $this->design->getConfigurationDesignTheme('frontend');
    }

    /**
     * @return int
     */
    private function getAmountOfFailedTasks(): int
    {
        return count($this->getIdsWithFailedTasks());
    }

    /**
     * @return array
     */
    private function getIdsWithFailedTasks(): array
    {
        $failedSyncOrderCollection = $this->storeKeeperFailedSyncOrderCollectionFactory->create()
            ->addFieldToFilter('is_failed', 1);

        return $failedSyncOrderCollection->getAllIds();
    }

    /**
     * @return string
     */
    private function getPluginSettingsUrl(): string
    {
        $sectionId = 'storekeeper_general';
        $params = [
            '_nosid' => true,
            '_query' => ['key' => $this->backendUrl->getSecretKey()]
        ];
        $url = $this->backendUrl->getUrl(
            'admin/system_config/edit',
            ['section' => $sectionId],
            $params
        );
        return $url;
    }

    /**
     * @return string
     */
    private function getCurrentDateTime(): string
    {
        return $this->timezone->date()->format(\StoreKeeper\StoreKeeper\Api\Webhook\Webhook::DATE_TIME_FORMAT);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getLastOrderDateTime(): string
    {
        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSort('created_at', 'desc');
        $lastOrder = $orderCollection->getFirstItem();
        $lastOrderDateTime = $this->timezone->date($lastOrder->getCreatedAt())
            ->format(\StoreKeeper\StoreKeeper\Api\Webhook\Webhook::DATE_TIME_FORMAT);

        return $lastOrderDateTime;
    }

    /**
     * @return string
     */
    private function getLastSynchronizedOrderDateTime(): string
    {
        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSort('storekeeper_order_last_sync', 'desc');
        $lastSynchronizedOrder = $orderCollection->getFirstItem();
        $lastSynchronizedOrderDateTime = $this->timezone->date($lastSynchronizedOrder
            ->getData('storekeeper_order_last_sync'))
            ->format(\StoreKeeper\StoreKeeper\Api\Webhook\Webhook::DATE_TIME_FORMAT);

        return $lastSynchronizedOrderDateTime;
    }

    /**
     * @return string
     */
    private function getLastDateOfFailedTask(): string
    {
        $failedOrders = $this->storeKeeperFailedSyncOrderCollectionFactory->create()
            ->addFieldToFilter('is_failed', 1)
            ->addOrder('updated_at');
        $lastFailedOrder = $failedOrders->getFirstItem();
        $lastFailedOrderDateTime = $this->timezone->date($lastFailedOrder->getData('updated_at'))
            ->format(\StoreKeeper\StoreKeeper\Api\Webhook\Webhook::DATE_TIME_FORMAT);

        return $lastFailedOrderDateTime;
    }

    private function getLanguageLabel(string $storeId): string
    {
        $localeCode = $this->configHelper->getLocaleCode($storeId);
        $locales = $this->translatedLists->getOptionLocales();

        $languageLabel = 'Unknown';
        foreach ($locales as $locale) {
            if ($locale['value'] === $localeCode) {
                $languageLabel = $locale['label'];
                break;
            }
        }

        return $languageLabel;
    }
}
