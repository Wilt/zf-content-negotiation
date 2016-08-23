<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use ZF\ContentNegotiation\Parser\MultipartFormDataParser;

class MultiPartFormDataParserFactory
{
    /**
     * @param  ContainerInterface $container
     * @return MultipartFormDataParser
     */
    public function __invoke(ContainerInterface $container)
    {
        $eventManager = $container->get(EventManager::class);
        $parser = new MultipartFormDataParser($eventManager);
        return $parser;
    }
}
