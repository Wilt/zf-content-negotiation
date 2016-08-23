<?php
/**
 * @link      http://github.com/zendframework/zend-validator for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Validator;

/**
 * Hint to the zend-modulemanager ServiceListener that a module provides parsers.
 *
 * Module classes implementing this interface hint to
 * Zend\ModuleManager\ServiceListener that they provide parsers for the
 * ParserPluginManager.
 *
 * For users of zend-mvc/zend-modulemanager v2, this poses no backwards-compatibility
 * break as the method getParserConfig is still duck-typed within Zend\Parser\Module
 * when providing configuration to the ServiceListener.
 */
interface ParserManagerProviderInterface
{
    /**
     * Provide parser plugin manager configuration for parsers.
     *
     * @return array
     */
    public function getParserManagerConfig();
}
