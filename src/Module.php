<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\DispatchableInterface;

class Module implements InitProviderInterface
{
    /**
     * Return module-specific configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen to bootstrap event.
     *
     * Attaches the ContentTypeListener, AcceptFilterListener, and
     * ContentTypeFilterListener to the application event manager.
     *
     * Attaches the AcceptListener as a shared listener for controller dispatch
     * events.
     *
     * @param MvcEvent $e
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $services = $app->getServiceManager();
        $eventManager = $app->getEventManager();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, $services->get(ContentTypeListener::class), -625);

        $services->get(AcceptFilterListener::class)->attach($eventManager);
        $services->get(ContentTypeFilterListener::class)->attach($eventManager);

        $sharedEventManager = $eventManager->getSharedManager();
        $sharedEventManager->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            $services->get(AcceptListener::class),
            -10
        );
    }

    /**
     * Register a specification for the FilterManager with the ServiceListener.
     *
     * @param ModuleManagerInterface|ModuleManager $moduleManager
     * @return void
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'ParserPluginManager',
            'parser_manager',
            'Zend\ModuleManager\Feature\FilterProviderInterface',
            'getFilterConfig'
        );
    }
}
