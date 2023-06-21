<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;

abstract class AbstractExportManager
{
    private Resolver $localeResolver;

    public function __construct(
        Resolver $localeResolver
    ) {
        $this->localeResolver = $localeResolver;
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
}
