<?php
namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Framework\App\Cache\TypeListInterface;
use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Wrapper\FullJsonAdapter;

class Auth extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        TypeListInterface $cache
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->cache = $cache;
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

        $this->cache->cleanType('config');

        file_put_contents("webhook.log", "Added auth data for website {$storeId}\n" . json_encode($authData['sync_auth'], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    }

    public function getStoreInformation($storeId)
    {
        return $this->getModule('ShopModule', $storeId)->getShopSettingsForHooks();
    }

    public function getTaxRates($storeId, $countryId)
    {
        return $this->getModule('ProductsModule', $storeId)->listTaxRates(
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

    public function setStoreInformation($storeId, array $data)
    {
        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_store_information",
            json_encode($data),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $this->cache->cleanType('config');
        return true;
    }

    public function authCheck($storeId)
    {
        $token = $this->getScopeConfigValue('storekeeper_general/general/storekeeper_token', $storeId);

        if (empty($token)) {
            $token = md5(
                $storeId . uniqid()
            );
            $this->configWriter->save(
                "storekeeper_general/general/storekeeper_token",
                $token,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                $storeId
            );
            $this->cache->cleanType('config');
            header('location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
        }

        $json = json_encode(
            [
                'token' => $token, // Needs to the same over the applications lifespan.
                'webhook_url' => "{$this->storeManager->getStore()->getBaseUrl()}/rest/V1/storekeeper/webhook?storeId={$storeId}", // Endpoint
            ]
        );

        $base64 = base64_encode($json);

        return $base64;
    }

    public function getStoreBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
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

    public function getAdapter($storeId)
    {
        $syncAuth = $this->getSyncAuth($storeId);
        $apiUrl = null;
        if (!empty($syncAuth) && isset($syncAuth['account'])) {
            $apiUrl = "https://api-{$syncAuth['account']}.storekeepercloud.com/";
        } else {
            throw new \Exception("An error occurred: Store #{$storeId} is not connected to StoreKeeper");
        }

        $adapter = new FullJsonAdapter($apiUrl);
        return $adapter;
    }

    public function getModule(string $module, $storeId)
    {
        $api = new ApiWrapper($this->getAdapter($storeId), $this->getAuthWrapper($storeId));
        return $api->getModule($module);
    }

    private $auth = null;

    public function getAuthWrapper($storeId)
    {
        if (is_null($this->auth)) {
            $sync_auth = $this->getSyncAuth($storeId);
            if (empty($sync_auth)) {
                throw new \Exception("Unable to authenticate with StoreKeeper. Did you add your API key to your store?");
            }

            $this->auth = new \StoreKeeper\ApiWrapper\Auth();
            $this->auth->setSubuser($sync_auth['subaccount'], $sync_auth['user']);
            $this->auth->setApiKey($sync_auth['apikey']);
            $this->auth->setAccount($sync_auth['account']);
        }

        return $this->auth;
    }

    public function isEnabled($storeId)
    {
        return $this->getScopeConfigValue(
            "storekeeper_general/general/enabled",
            $storeId,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES
        );
    }

    private function getSyncAuth($storeId)
    {
        $sync_auth = $this->getScopeConfigValue(
            "storekeeper_general/general/storekeeper_sync_auth",
            $storeId,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES
        );

        if (!empty($sync_auth)) {
            return json_decode($sync_auth, true);
        }
        return null;
    }

    public function isConnected($storeId)
    {
        return $this->isEnabled($storeId) && !empty($this->getSyncAuth($storeId));
    }

    public function disconnectStore($storeId)
    {
        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_sync_auth",
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_guest_auth",
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_shop_id",
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_shop_name",
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            "storekeeper_general/general/storekeeper_store_information",
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->cache->cleanType('config');
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
