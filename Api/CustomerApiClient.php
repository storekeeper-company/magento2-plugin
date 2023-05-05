<?php

namespace StoreKeeper\StoreKeeper\Api;

use StoreKeeper\ApiWrapper\Exception\GeneralException;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use Psr\Log\LoggerInterface;

class CustomerApiClient
{
    private OrderApiClient $orderApiClient;
    private LoggerInterface $logger;

    /**
     * CustomerApiClient constructor.
     * @param OrderApiClient $orderApiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderApiClient $orderApiClient,
        LoggerInterface $logger
    ) {
        $this->orderApiClient = $orderApiClient;
        $this->logger = $logger;
    }

    /**
     * Find customer relation dataId by email
     *
     * @param string $email
     * @param string $storeId
     * @return false|int
     */
    public function findCustomerRelationDataIdByEmail(string $email, string $storeId): ?int
    {
        $id = false;
        if (!empty($email)) {
            try {
                $customer = $this->orderApiClient->getShopModule($storeId)->findShopCustomerBySubuserEmail([
                    'email' => $email
                ]);
                $id = (int)$customer['id'];
            } catch (GeneralException $exception) {
                // Customer not found in StoreKeeper
                $this->logger->error($exception->getMessage());
            }
        }

        return $id;
    }
}
