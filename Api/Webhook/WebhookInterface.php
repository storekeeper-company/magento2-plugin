<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

interface WebhookInterface
{
    /**
     * GET for Webhook api
     * @param string $storeId
     * @return void
     */
    public function getExecute($storeId);

    /**
     * POST for Wehbook api
     * @param mixed $storeId
     * @return void
     */
    public function postExecute($storeId);
}
