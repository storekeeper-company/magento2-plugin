<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

abstract class AbstractExportManager
{
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

    /**
     * @return string
     */
    public function isMainLanguage(): string
    {
        $result = 'yes';
        if ($this->getCurrentLocale() != $this->getCurrentStoreLanguageCode()) {
            $result = 'no';
        }

        return $result;
    }
}
