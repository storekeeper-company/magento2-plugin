<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use PHPUnit\Exception;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\ApiWrapper\ModuleApiWrapper;
use StoreKeeper\ApiWrapper\Iterator\ListCallByIdPaginatedIterator;
use Magento\Payment\Model\Config;

class ConfigProvider implements ConfigProviderInterface
{
    const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';

    private $methodCodes = [
        'storekeeper_payment_alipay',
        'storekeeper_payment_amex',
        'storekeeper_payment_applepay',
        'storekeeper_payment_bataviacadeaukaart',
        'storekeeper_payment_billink',
        'storekeeper_payment_blik',
        'storekeeper_payment_cartebleue',
        'storekeeper_payment_creditclick',
        'storekeeper_payment_dankort',
        'storekeeper_payment_eps',
        'storekeeper_payment_fashioncheque',
        'storekeeper_payment_fashiongiftcard',
        'storekeeper_payment_gezondheidsbon',
        'storekeeper_payment_giropay',
        'storekeeper_payment_givacard',
        'storekeeper_payment_ideal',
        'storekeeper_payment_maestro',
        'storekeeper_payment_multibanco',
        'storekeeper_payment_nexi',
        'storekeeper_payment_overboeking',
        'storekeeper_payment_payconiq',
        'storekeeper_payment_paypal',
        'storekeeper_payment_paysafecard',
        'storekeeper_payment_postepay',
        'storekeeper_payment_przelewy24',
        'storekeeper_payment_spraypay',
        'storekeeper_payment_telefonischbetalen',
        'storekeeper_payment_visamastercard',
        'storekeeper_payment_wechatpay',
        'storekeeper_payment_yourgift',
        'storekeeper_payment'
    ];

    private $methods;

    private Auth $authHelper;

    private PaymentHelper $paymentHelper;

    private Config $paymentConfig;

    /**
     * ConfigProvider constructor.
     * @param Auth $authHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Auth $authHelper,
        PaymentHelper $paymentHelper,
        Config $paymentConfig
    ) {
        $this->authHelper = $authHelper;
        $this->paymentHelper = $paymentHelper;
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $config = [];
        $config['storekeeper_payment_methods'] = $this->getMappedPaymentMethods();
        $config['magento_active_payment_methods'] = array_keys($this->paymentConfig->getActiveMethods());

        return $config;
    }

    /**
     * @return array
     */
    private function getStoreKeeperPaymentMethods(): array
    {
        $ShopModule =  $this->getShopModule();
        $storeKeeperPaymentMethods = new ListCallByIdPaginatedIterator(function (ListCallByIdPaginatedIterator $iter) use ($ShopModule) {
            return $ShopModule->listTranslatedPaymentMethodForHooks(
                0,
                $iter->getStart(),
                $iter->getPerPage(),
                [
                    [
                        'name' => 'id',
                        'dir' => 'asc'
                    ],
                ]
            );
        });

        $paymentMethods = [];
        foreach ($storeKeeperPaymentMethods as $storeKeeperPaymentMethod) {
            $paymentMethods[$storeKeeperPaymentMethod['id']] = [
                'storekeeper_payment_method_id' => $storeKeeperPaymentMethod['id'],
                'storekeeper_payment_method_title' => $storeKeeperPaymentMethod['title'],
                'storekeeper_payment_method_logo_url' => $storeKeeperPaymentMethod['image_url'] ?? '',
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
    public function getMappedPaymentMethods(): array
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
