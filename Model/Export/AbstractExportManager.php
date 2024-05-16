<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

abstract class AbstractExportManager
{
    const CONSUMER_NAME = 'storekeeper.data.export';

    private Resolver $localeResolver;
    private StoreManagerInterface $storeManager;
    private StoreConfigManagerInterface $storeConfigManager;
    private Auth $authHelper;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Auth $authHelper
    ) {
        $this->localeResolver = $localeResolver;
        $this->storeManager = $storeManager;
        $this->storeConfigManager = $storeConfigManager;
        $this->authHelper = $authHelper;
    }

    /**
     * @param array $headersPaths
     * @param array $headersLabels
     * @return array
     */
    public function getMappedHeadersLabels(array $headersPaths, array $headersLabels): array
    {
        return array_combine($headersPaths, $headersLabels);
    }

    /**
     * @return string
     */
    public function getCurrentLocale(): string
    {
        $storeId = $this->storeManager->getStore()->getId();

        return $this->authHelper->getLanguageForStore($storeId);
    }

    /**
     * @return int
     */
    public function getProductEntityTypeId(): int
    {
        return CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID;
    }

    /**
     * @param $name
     * @param $r
     * @return array|string|string[]|null
     */
    protected function formatAlias($name, $r = '_')
    {
        $name = trim($name);
        $name = mb_strtolower($name);
        $name = preg_replace('/\s/', $r, $name);

        return preg_replace('/\\'.$r.'+/', $r, $name);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentStoreLanguageCode(): string
    {
        $storeConfigs = $this->storeConfigManager->getStoreConfigs([$this->storeManager->getStore()->getCode()]);
        foreach ($storeConfigs as $config) {
            $languageCode = strstr($config->getLocale(), '_', true);
        }

        return $languageCode;
    }
}
