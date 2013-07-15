<?php

namespace sraka1\Guzzle;

use Guzzle\Http\Message\Request;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * ZeppBasicAuthPlugin plugin
 *
 * A plugin to sign basic auth requests
 *
 * @api
 */
class BasicAuthPlugin implements EventSubscriberInterface
{
    /**
     * Constructor.
     *
     * @param $config Config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return array Plugin event subscriptions.
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => array('onRequestBeforeSend', -9999)
        );
    }

    /**
     * Add auth to every request
     *
     * @param \Guzzle\Common\Event $event The `request.before_send` event object.
     */
    public function onRequestBeforeSend(Event $event)
    {
        /** @var $request \Guzzle\Http\Message\Request */
        $request = $event['request'];

        foreach($config as $key => $param)
        {
            if ($param['type']==='query') {
                $request->getQuery()->set($param['key'], $param['value']);
            }
            else if ($param['type']==='header') {
                $request->addHeader($param['key'], $param['value']);
            }
        }
    }
}