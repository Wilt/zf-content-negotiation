<?php

namespace ZF\ContentNegotiation\Parser;

use Zend\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager to collect parsers
 */
class ParserPluginManager extends AbstractPluginManager
{
    /**
     * @inheritdoc
     */
    protected $instanceOf = ParserInterface::class;
}
