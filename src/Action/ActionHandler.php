<?php

namespace PluginMaster\Foundation\Action;

use PluginMaster\Contracts\Action\ActionHandlerInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;

class ActionHandler implements ActionHandlerInterface
{
    /**
     * @var string
     */
    protected $methodSeparator = '@';


    /**
     * controller namespace
     * @var string
     */
    protected $controllerNamespace = '';

    /**
     * @var object
     */
    protected $appInstance;


    /**
     * @param $instance
     * @return $this
     */
    public function setAppInstance( $instance ) {
        $this->appInstance = $instance;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setControllerNamespace( $namespace ) {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param $shortcodeFile
     * @return void
     */
    public function loadFile( $actionFile ) {
        require $actionFile;
    }

    /**
     * @param $name
     * @param $callback
     * @param int $priority
     */
    public function add( $name, $callback, $priority = 10 ) {
        $options = [ "methodSeparator" => $this->methodSeparator, 'namespace' => $this->controllerNamespace, 'container' => $this->appInstance ];
        add_action( $name, CallbackResolver::resolve( $callback, $options ), $priority, 20 );
    }

}