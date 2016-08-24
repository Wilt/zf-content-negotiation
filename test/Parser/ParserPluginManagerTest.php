<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use ZF\ContentNegotiation\Parser\ParserInterface;
use ZF\ContentNegotiation\Parser\ParserPluginManager;

class ParserPluginManagerTest extends TestCase
{
    /**
     * @var ParserPluginManager
     */
    protected $manager;

    public function setUp()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $this->manager = new ParserPluginManager($container->reveal());
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceException
     */
    public function testRegisteringInvalidElementRaisesException()
    {
        $this->manager->setService('test', $this);
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceException
     */
    public function testLoadingInvalidElementRaisesException()
    {
        $this->manager->setInvokableClass('test', get_class($this));
        $this->manager->get('test');
    }

    /**
     * @covers Zend\InputFilter\ParserPluginManager::validate
     */
    public function testAllowLoadingInstancesOfParserInterface()
    {
        $parser = $this->getMock(ParserInterface::class);
        $this->assertNull($this->manager->validate($parser));
    }
}
