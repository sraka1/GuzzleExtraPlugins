<?php

namespace sraka1\Guzzle;


use Guzzle\Common\Event;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maintains a list of requests and responses sent using a request or client
 */
class RedisHistoryPlugin implements EventSubscriberInterface, \IteratorAggregate, \Countable
{
    /** @var Rediska Redis handler */
    protected $redis;

    /** @var int The maximum number of requests to maintain in the history */
    protected $limit = 10;

    /** @var int The maximum number of requests to maintain in the history */
    protected $expire = 86400;

    /** @var array Requests and responses that have passed through the plugin */
    protected $transactions = array();

    /** @var string A unique ID being saved to redis */
    protected $id;

    public static function getSubscribedEvents()
    {
        return array('request.sent' => array('onRequestSent', 9999));
    }

    /**
     * Convert to a string that contains all request and response headers
     *
     * @return string
     */
    public function __toString()
    {
        $lines = array();
        foreach ($this->transactions as $entry) {
            $response = isset($entry['response']) ? $entry['response'] : '';
            $lines[] = '> ' . trim($entry['request']) . "\n\n< " . trim($response) . "\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Add a request to the history
     *
     * @param RequestInterface $request  Request to add
     * @param Response         $response Response of the request
     *
     * @return HistoryPlugin
     */
    public function add(RequestInterface $request, Response $response = null)
    {
        if (!$response && $request->getResponse()) {
            $response = $request->getResponse();
        }

        $transaction = array('request' => $request, 'response' => $response);
        $this->saveTransaction
        (
            json_encode
            (
                array
                (
                    'request' => $request->getRawHeaders(),
                    'response' => array
                    (
                        'headers' => $response->getRawHeaders(),
                        'body' => $response->getBody(true)
                    )
                )
            )
        );

        $this->transactions[] = $transaction;
        if (count($this->transactions) > $this->getlimit()) {
            array_shift($this->transactions);
        }

        return $this;
    }

    /**
     * Save the request/response pair using the appID 
     * @param  array $transaction An array containing request/response objects
     * @return void
     */
    private function saveTransaction($transaction)
    {
        //$this->redis->ZADD($this->id, microtime(true), json_encode($transaction));
        $this->redis->addToSortedSet($this->id, $transaction, time());
        $this->redis->expire($this->id, $this->expire);
    }

    /**
     * Set the max number of requests to store
     *
     * @param int $limit Limit
     *
     * @return HistoryPlugin
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Set the expiration in seconds
     *
     * @param int $expire Expiration
     *
     * @return HistoryPlugin
     */
    public function setExpire($expire)
    {
        $this->expire = (int) $expire;

        return $this;
    }

    /**
     * Set the unique ID
     *
     * @param string $id Limit
     *
     * @return HistoryPlugin
     */
    public function setID($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set redis handler
     *
     * @param Rediska $redis Limit
     *
     * @return HistoryPlugin
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * Get the request limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get all of the raw transactions in the form of an array of associative arrays containing
     * 'request' and 'response' keys.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->transactions;
    }

    /**
     * Get the requests in the history
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        // Return an iterator just like the old iteration of the HistoryPlugin for BC compatibility (use getAll())
        return new \ArrayIterator(array_map(function ($entry) {
            $entry['request']->getParams()->set('actual_response', $entry['response']);
            return $entry['request'];
        }, $this->transactions));
    }

    /**
     * Get the number of requests in the history
     *
     * @return int
     */
    public function count()
    {
        return count($this->transactions);
    }

    /**
     * Get the last request sent
     *
     * @return RequestInterface
     */
    public function getLastRequest()
    {
        $last = end($this->transactions);

        return $last['request'];
    }

    /**
     * Get the last response in the history
     *
     * @return Response|null
     */
    public function getLastResponse()
    {
        $last = end($this->transactions);

        return isset($last['response']) ? $last['response'] : null;
    }

    /**
     * Clears the history
     *
     * @return HistoryPlugin
     */
    public function clear()
    {
        $this->transactions = array();

        return $this;
    }

    public function onRequestSent(Event $event)
    {
        $this->add($event['request'], $event['response']);
    }
}
