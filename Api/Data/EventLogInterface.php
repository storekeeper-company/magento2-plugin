<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api\Data;

interface EventLogInterface
{

    const EVENTLOG_ID = 'eventlog_id';
    const REQUEST_ROUTE = 'request_route';
    const REQUEST_BODY = 'request_body';
    const REQUEST_ACTION = 'request_action';
    const DATE = 'date';
    const RESPONSE_CODE = 'response_code';
    const REQUEST_METHOD = 'request_method';

    /**
     * Get eventlog_id
     * @return string|null
     */
    public function getEventlogId();

    /**
     * Set eventlog_id
     * @param string $eventlogId
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setEventlogId($eventlogId);

    /**
     * Get request_route
     * @return string|null
     */
    public function getRequestRoute();

    /**
     * Set request_route
     * @param string $requestRoute
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setRequestRoute($requestRoute);

    /**
     * Get request_body
     * @return string|null
     */
    public function getRequestBody();

    /**
     * Set request_body
     * @param string $requestBody
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setRequestBody($requestBody);

    /**
     * Get request_method
     * @return string|null
     */
    public function getRequestMethod();

    /**
     * Set request_method
     * @param string $requestMethod
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setRequestMethod($requestMethod);

    /**
     * Get request_action
     * @return string|null
     */
    public function getRequestAction();

    /**
     * Set request_action
     * @param string $requestAction
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setRequestAction($requestAction);

    /**
     * Get response_code
     * @return string|null
     */
    public function getResponseCode();

    /**
     * Set response_code
     * @param string $responseCode
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setResponseCode($responseCode);

    /**
     * Get date
     * @return string|null
     */
    public function getDate();

    /**
     * Set date
     * @param string $date
     * @return \StoreKeeper\StoreKeeper\EventLog\Api\Data\EventLogInterface
     */
    public function setDate($date);
}

