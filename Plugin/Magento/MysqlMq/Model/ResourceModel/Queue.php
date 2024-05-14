<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Plugin\Magento\MysqlMq\Model\ResourceModel;

use Magento\MysqlMq\Model\ResourceModel\Queue as subjectQueue;
use StoreKeeper\StoreKeeper\Model\Consumer as eventConsumer;
class Queue
{
    const SK_MESSAGE_QUEUES = [
        eventConsumer::CONSUMER_NAME
    ];
    
    public function afterSaveMessage(subjectQueue $subject, $result, $message)
    {
        
        if (in_array($message, self::SK_MESSAGE_QUEUES)) {
            $queueMessageId = $result;
        }

        return $result;
    }
}
