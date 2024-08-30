<?php
namespace StoreKeeper\StoreKeeper\Plugin\Magento\InventorySalesApi\Api;

use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySalesApi\Api\Data\ReservationInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;

class DisableStockReservations
{
    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param Config $configHelper
     */
    public function __construct (
        Auth $authHelper,
        Config $configHelper
    ) {
        $this->authHelper = $authHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Disable reservations for sales events
     *
     * @param PlaceReservationsForSalesEventInterface $subject
     * @param callable $proceed
     * @param ReservationInterface[] $reservations
     * @return void
     */
    public function aroundExecute(
        PlaceReservationsForSalesEventInterface $subject,
        callable $proceed,
        array $reservations
    ) {
        $storeId = $this->authHelper->getStoreId();
        if (
            $this->authHelper->isConnected($storeId)
            && $this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)
        ) {
            return;
        } else {
            return $proceed($reservations);
        }
    }
}
