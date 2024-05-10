<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Framework\Model\AbstractModel;
use StoreKeeper\StoreKeeper\Api\Data\EventLogInterface;

class EventLog extends AbstractModel implements EventLogInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog::class);
    }

    /**
     * @inheritDoc
     */
    public function getEventlogId()
    {
        return $this->getData(self::EVENTLOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEventlogId($eventlogId)
    {
        return $this->setData(self::EVENTLOG_ID, $eventlogId);
    }

    /**
     * @inheritDoc
     */
    public function getRequestRoute()
    {
        return $this->getData(self::REQUEST_ROUTE);
    }

    /**
     * @inheritDoc
     */
    public function setRequestRoute($requestRoute)
    {
        return $this->setData(self::REQUEST_ROUTE, $requestRoute);
    }

    /**
     * @inheritDoc
     */
    public function getRequestBody()
    {
        return $this->getData(self::REQUEST_BODY);
    }

    /**
     * @inheritDoc
     */
    public function setRequestBody($requestBody)
    {
        return $this->setData(self::REQUEST_BODY, $requestBody);
    }

    /**
     * @inheritDoc
     */
    public function getRequestMethod()
    {
        return $this->getData(self::REQUEST_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setRequestMethod($requestMethod)
    {
        return $this->setData(self::REQUEST_METHOD, $requestMethod);
    }

    /**
     * @inheritDoc
     */
    public function getRequestAction()
    {
        return $this->getData(self::REQUEST_ACTION);
    }

    /**
     * @inheritDoc
     */
    public function setRequestAction($requestAction)
    {
        return $this->setData(self::REQUEST_ACTION, $requestAction);
    }

    /**
     * @inheritDoc
     */
    public function getResponseCode()
    {
        return $this->getData(self::RESPONSE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setResponseCode($responseCode)
    {
        return $this->setData(self::RESPONSE_CODE, $responseCode);
    }

    /**
     * @inheritDoc
     */
    public function getDate()
    {
        return $this->getData(self::DATE);
    }

    /**
     * @inheritDoc
     */
    public function setDate($date)
    {
        return $this->setData(self::DATE, $date);
    }
}

