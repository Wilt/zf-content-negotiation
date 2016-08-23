<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use ZF\ContentNegotiation\ContentNegotiationOptions;
use ZF\ContentNegotiation\ContentTypeListener;
use ZF\ContentNegotiation\Parser\ParserPluginManager;

class ContentTypeListenerFactory
{
    /**
     * @param  ContainerInterface $container
     * @return ContentTypeListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $parserPluginManager = $container->get(ParserPluginManager::class);
        $options = $container->get(ContentNegotiationOptions::class);
        $parsers = $options->getParsers();
        $listener = new ContentTypeListener($parserPluginManager, $parsers);
        return $listener;
    }
}
