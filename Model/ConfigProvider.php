<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\ApiWrapper\ModuleApiWrapper;

class ConfigProvider implements ConfigProviderInterface
{
    const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';

    private $methodCodes = [
        'storekeeper_payment_ideal',
        'storekeeper_payment'
    ];

    private $methods;

    private Auth $authHelper;

    private PaymentHelper $paymentHelper;

    /**
     * ConfigProvider constructor.
     * @param Auth $authHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Auth $authHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->authHelper = $authHelper;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $config = [];
        $config['storekeeper_payment_methods'] = $this->getMappedPaymentMethods();

        return $config;
    }

    /**
     * @return array
     */
    private function getStoreKeeperPaymentMethods(): array
    {
        $storeKeeperPaymentMethods =  $this->getShopModule()->listTranslatedPaymentMethodForHooks('0', 0, 10, null, []);
        foreach ($storeKeeperPaymentMethods['data'] as $storeKeeperPaymentMethod) {
            $paymentMethods[$storeKeeperPaymentMethod['id']] = [
                'storekeeper_payment_method_id' => $storeKeeperPaymentMethod['id'],
                'storekeeper_payment_method_title' => $storeKeeperPaymentMethod['title'],
                'storekeeper_payment_method_logo_url' => $storeKeeperPaymentMethod['image_url'],
                'storekeeper_payment_method_eId' => $storeKeeperPaymentMethod['eid']
            ];
        }

        return $paymentMethods;
    }

    /**
     * @return ModuleApiWrapper
     */
    private function getShopModule(): ModuleApiWrapper
    {
        return $this->authHelper->getModule(self::STOREKEEPER_SHOP_MODULE_NAME, $this->getStoreId());
    }

    /**
     * @return string
     */
    private function getStoreId(): string
    {
        return $this->authHelper->storeManager->getStore()->getId();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getMappedPaymentMethods(): array
    {
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = [
                'eId' => $this->paymentHelper->getMethodInstance($code)->getEId(),
                'code' => $this->paymentHelper->getMethodInstance($code)->getCode()
            ];
        }

        foreach ($this->getStoreKeeperPaymentMethods() as $storeKeeperPaymentMethod) {
            foreach ($this->methods as $code => $method) {
                if ($method['eId'] == $storeKeeperPaymentMethod['storekeeper_payment_method_eId']) {
                    $storeKeeperPaymentMethod['magento_payment_method_code'] = $code;
                }
            }
            $mappedPaymentMethods[] = $storeKeeperPaymentMethod;
        }

        return $mappedPaymentMethods;
    }
}
