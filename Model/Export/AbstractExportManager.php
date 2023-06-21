<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

abstract class AbstractExportManager
{
    private Resolver $localeResolver;
    private StoreManagerInterface $storeManager;
    private StoreConfigManagerInterface $storeConfigManager;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager
    ) {
        $this->localeResolver = $localeResolver;
        $this->storeManager = $storeManager;
        $this->storeConfigManager = $storeConfigManager;
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
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);

        return $languageCode;
    }


    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentStoreLanguageCode(): string
    {
        $storeConfigs = $this->storeConfigManager->getStoreConfigs([$this->storeManager->getStore()->getCode()]);
        foreach ($storeConfigs as $config) {
            $languageCode = strstr($config->getCode(), '_', true);
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
