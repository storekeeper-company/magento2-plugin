<?php
namespace StoreKeeper\StoreKeeper\Helper\Api;

use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Wrapper\FullJsonAdapter;
use Zend_Http_Response_Stream;

class Auth extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
    }

    public function setAuthDataForWebsite($storeId, $authData)
    {
        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_sync_auth",
            json_encode($authData['sync_auth']),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_guest_auth",
            json_encode($authData['guest_auth']),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_shop_id",
            $authData['shop']['id'],
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_shop_name",
            $authData['shop']['name'],
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        file_put_contents("webhook.log", "Added auth data for website {$storeId}\n" . gettype($authData['guest_auth']) . "\n", FILE_APPEND);

    }

    public function authCheck($storeId)
    {
        $json = json_encode(
            [
                'token' => "abc123", // Needs to the same over the applications lifespan.
                'webhook_url' => "{$this->storeManager->getStore()->getBaseUrl()}/rest/V1/storekeeper/webhook?storeId={$storeId}", // Endpoint
            ]
        );

        $base64 = base64_encode($json);

        // wrong
        // eyJ0b2tlbiI6ImFiYzEyMyIsIndlYmhvb2tfdXJsIjoiaHR0cDpcL1wvc3RvcmVrZWUycGVyLm0yLmRldjEuZG5vdm8tZGV2LmV1XC9yZXN0XC9WMVwvc3RvcmVrZWVwZXJcL3dlYmhvb2s/c3RvcmVJZD0xIn0=

        // store
        // eyJ0b2tlbiI6ImFiYzEyMyIsIndlYmhvb2tfdXJsIjoiaHR0cDpcL1wvc3RvcmVrZWVwZXIubTIuZGV2MS5kbm92by1kZXYuZXVcL3Jlc3RcL1YxXC9zdG9yZWtlZXBlclwvd2ViaG9vaz9zdG9yZUlkPTEifQ==

        // website 1
        // eyJ0b2tlbiI6ImFiYzEyMyIsIndlYmhvb2tfdXJsIjoiaHR0cDpcL1wvc3RvcmVrZWVwZXIubTIuZGV2MS5kbm92by1kZXYuZXVcL3Jlc3RcL1YxXC9zdG9yZWtlZXBlclwvd2ViaG9vaz93ZWJzaXRlSWQ9MSJ9

        // store 3
        // eyJ0b2tlbiI6ImFiYzEyMyIsIndlYmhvb2tfdXJsIjoiaHR0cDpcL1wvc3RvcmVrZWVwZXIubTIuZGV2MS5kbm92by1kZXYuZXVcL3Jlc3RcL1YxXC9zdG9yZWtlZXBlclwvd2ViaG9vaz9zdG9yZUlkPTMifQ==
        return $base64;

    }

    private $storeShopIds = null;

    public function getStoreShopIds()
    {
        if (is_null($this->storeShopIds)) {
            $this->storeShopIds = [];
            foreach ($this->storeManager->getStores() as $store) {
                $value = $this->getScopeConfigValue('storekeeper_general/general/storekeeper_shop_id', $store->getId());
                $this->storeShopIds[$value] = $store->getId();
            }
        }
        return $this->storeShopIds;
    }

    private $websiteShopIds = null;

    public function getWebsiteShopIds()
    {
        if (is_null($this->websiteShopIds)) {
            $this->websiteShopIds = [];
            foreach ($this->storeManager->getWebsites() as $website) {
                $value = $this->getScopeConfigValue(
                    'storekeeper_general/general/storekeeper_shop_id',
                    $website->getId(),
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
                );
                $this->websiteShopIds[$value] = $website->getId();
            }
        }
        return $this->websiteShopIds;
    }

    public function getAdapter()
    {
        $apiUrl = 'https://api-creativectdev.storekeepercloud.com/';
        $adapter = new FullJsonAdapter($apiUrl);

        return $adapter;
    }

    public function getModule(string $module, $storeId)
    {
        $api = new ApiWrapper($this->getAdapter(), $this->getAuthWrapper($storeId));
        return $api->getModule($module);
    }

    private $auth = null;

    public function getAuthWrapper($storeId)
    {
        if (is_null($this->auth)) {
            $sync_auth = $this->getSyncAuth($storeId);
            $this->auth = new \StoreKeeper\ApiWrapper\Auth();
            $this->auth->setSubuser($sync_auth['subaccount'], $sync_auth['user']);
            $this->auth->setApiKey($sync_auth['apikey']);
            $this->auth->setAccount($sync_auth['account']);
        }

        return $this->auth;
    }

    private function getSyncAuth($storeId)
    {
        return json_decode(
            $this->getScopeConfigValue(
                "storekeeper_general/general/storekeeper_sync_auth",
                $storeId,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES
            ),
            true
        );
    }

    public function getLanguageForStore($storeId)
    {
        $lang = $this->getScopeConfigValue(
            'storekeeper_general/general/storekeeper_shop_language',
            $storeId,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES
        );

        if (empty($lang)) {
            $lang = ' ';
        }
        return $lang;
    }

    private function getScopeConfigValue(string $key, $id = 0, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            $key,
            $scope,
            $id
        );
    }


}
