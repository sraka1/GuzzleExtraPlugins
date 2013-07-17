<?php

namespace sraka1\Guzzle;

use Guzzle\Http\Message\Request;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * BasicAuthPlugin plugin
 *
 * A plugin to sign basic auth requests
 *
 * @api
 */
class BasicAuthPlugin implements EventSubscriberInterface
{

    /** @var int Application config file */
    private $config;

    /** @var int Connection data from the database */
    private $connData;

    /**
     * Constructor.
     *
     * @param $config Config
     */
    public function __construct($config, $connData)
    {
        $this->config = $config;
        $this->connData = $connData;
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
     * Replace variables with the ones from the DB
     * @param  array $config   Config array.
     * @param  array $connData Connection data.
     * @return void
     */
    /*private function replaceVars()
    {
        $tempArray = array();
        $config = json_encode($this->config);
        foreach ($this->connData as $k => $v) //$connData is now array(:token => "sklmn", :key => "blkmn")
        {
            $x = str_replace(':'.$k, $v, $config);
        }
        $this->config = json_decode($config, true);

    }*/


    /**
     * Add auth to every request
     *
     * @param \Guzzle\Common\Event $event The `request.before_send` event object.
     */
    public function onRequestBeforeSend(Event $event)
    {
        /** @var $request \Guzzle\Http\Message\Request */
        $request = $event['request'];

        foreach($this->config['auth'] as $key => $param)
        {
            if ($param['type']==='query') {
                $request->getQuery()->set($param['key'], $param['value']);
            }
            else if ($param['type']==='header') {
                $request->addHeader($param['key'], $param['value']);
            }
            else if ($param['type']==='httpAuth') {
                $request->setAuth($param['key'], $param['value']);
            }
        }
    }
}