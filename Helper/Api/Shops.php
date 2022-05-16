<?php
namespace StoreKeeper\StoreKeeper\Helper\Api;

class Shops extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    public function listShops(string $query, string $lang, int $offset, int $limit, array $order, array $filters)
    {
        return $this->getModule('ShopModule')->listShops(
            $query,
            $lang,
            $offset,
            $limit,
            $order,
            $filters
        );        
    }
}