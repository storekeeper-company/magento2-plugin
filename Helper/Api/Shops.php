<?php
namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;

class Shops extends AbstractHelper
{
    private Auth $authHelper;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     */
    public function __construct(
        Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    /**
     * Get list of Shops
     *
     * @param string $query
     * @param string $lang
     * @param int $offset
     * @param int $limit
     * @param array $order
     * @param array $filters
     * @return mixed
     */
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
