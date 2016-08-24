<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Symfony\Component\Yaml\Exception\ParseException;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Parameters;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\ContentTypeListener;
use ZF\ContentNegotiation\MultipartContentParser;
use ZF\ContentNegotiation\ParameterDataContainer;
use ZF\ContentNegotiation\Parser\JsonParser;
use ZF\ContentNegotiation\Parser\ParserInterface;
use ZF\ContentNegotiation\Parser\ParserPluginManager;
use ZF\ContentNegotiation\Request as ContentNegotiationRequest;

class ContentTypeListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /**
     * @var array
     */
    protected $parsers = [
        'application/json' => 'JsonParser',
        'content/type' => 'MissingContentTypeParser'
    ];

    /**
     * @var ParserPluginManager
     */
    protected $parserManager;

    /**
     * @var ContentTypeListener
     */
    protected $listener;

    /**
     * @var ParserInterface|ObjectProphecy
     */
    protected $parser;

    public function setUp()
    {
        $this->parserManager = $parserManager = $this->prophesize(ParserPluginManager::class);
        $this->parser = $parser = $this->prophesize(ParserInterface::class);
        $parserManager->has('JsonParser')->willReturn(true);
        $parserManager->get('JsonParser')->willReturn($parser);
        $parserManager->has('MissingContentTypeParser')->willReturn(false);
        $this->listener = new ContentTypeListener($parserManager->reveal(), $this->parsers);
    }

    public function methods()
    {
        return [
            'post' => ['POST'],
            'patch' => ['PATCH'],
            'put' => ['PUT'],
            'delete' => ['DELETE'],
        ];
    }

    /**
     * @group 3
     * @dataProvider methods
     */
    public function testMissingContentTypeInParserMapReturnsProblemResponse($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'missing/type');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($this->createRouteMatch([]));

        $result = $listener($event);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(415, $problem->status);
        $this->assertContains('Unsupported Media Type', $problem->detail);
    }

    /**
     * @group 3
     * @dataProvider methods
     */
    public function testNonExistingParserInMapReturnsProblemResponse($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'content/type');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($this->createRouteMatch([]));

        $result = $listener($event);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(415, $problem->status);
        $this->assertContains('Unsupported Media Type', $problem->detail);
    }

    /**
     * @group 3
     * @dataProvider methods
     */
    public function testParseExceptionReturnsProblemResponse($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($this->createRouteMatch([]));

        $parseMethod = new MethodProphecy($this->parser, 'parse', [$request]);
        $parseMethod->willThrow(ParseException::class);

        $result = $listener($event);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains('JSON decoding', $problem->detail);
    }

    /**
     * @group 3
     * @dataProvider methods
     */
    public function testParseResultWillBeSetAsBodyParams($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($this->createRouteMatch([]));

        $parseResult = ['foo' => 'bar'];
        $parseMethod = new MethodProphecy($this->parser, 'parse', [$request]);
        $parseMethod->willReturn($parseResult);

        $listener($event);
        /** @var ParameterDataContainer $parameterData */
        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
        $bodyParams = $result = $parameterData->getBodyParams();
        $this->assertEquals($parseResult, $bodyParams);
    }


//    public function multipartFormDataMethods()
//    {
//        return [
//            'patch'  => ['patch'],
//            'put'    => ['put'],
//            'delete' => ['delete'],
//        ];
//    }
//
//    /**
//     * @dataProvider multipartFormDataMethods
//     */
//    public function testCanDecodeMultipartFormDataRequestsForPutPatchAndDeleteOperations($method)
//    {
//        $request = new Request();
//        $request->setMethod($method);
//        $request->getHeaders()->addHeaderLine(
//            'Content-Type',
//            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
//        );
//        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data.txt'));
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//        $event->setRouteMatch($this->createRouteMatch([]));
//
//        $listener = $this->listener;
//        $result = $listener($event);
//
//        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
//        $params = $parameterData->getBodyParams();
//        $this->assertEquals([
//            'mime_type' => 'md',
//        ], $params);
//
//        $files = $request->getFiles();
//        $this->assertEquals(1, $files->count());
//        $file = $files->get('text');
//        $this->assertInternalType('array', $file);
//        $this->assertArrayHasKey('error', $file);
//        $this->assertArrayHasKey('name', $file);
//        $this->assertArrayHasKey('tmp_name', $file);
//        $this->assertArrayHasKey('size', $file);
//        $this->assertArrayHasKey('type', $file);
//        $this->assertEquals('README.md', $file['name']);
//        $this->assertRegexp('/^zfc/', basename($file['tmp_name']));
//        $this->assertTrue(file_exists($file['tmp_name']));
//    }
//
//    /**
//     * @dataProvider multipartFormDataMethods
//     */
//    public function testCanDecodeMultipartFormDataRequestsFromStreamsForPutAndPatchOperations($method)
//    {
//        $request = new ContentNegotiationRequest();
//        $request->setMethod($method);
//        $request->getHeaders()->addHeaderLine(
//            'Content-Type',
//            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
//        );
//        $request->setContentStream('file://' . realpath(__DIR__ . '/TestAsset/multipart-form-data.txt'));
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//        $event->setRouteMatch($this->createRouteMatch([]));
//
//        $listener = $this->listener;
//        $result = $listener($event);
//
//        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
//        $params = $parameterData->getBodyParams();
//        $this->assertEquals([
//            'mime_type' => 'md',
//        ], $params);
//
//        $files = $request->getFiles();
//        $this->assertEquals(1, $files->count());
//        $file = $files->get('text');
//        $this->assertInternalType('array', $file);
//        $this->assertArrayHasKey('error', $file);
//        $this->assertArrayHasKey('name', $file);
//        $this->assertArrayHasKey('tmp_name', $file);
//        $this->assertArrayHasKey('size', $file);
//        $this->assertArrayHasKey('type', $file);
//        $this->assertEquals('README.md', $file['name']);
//        $this->assertRegexp('/^zfc/', basename($file['tmp_name']));
//        $this->assertTrue(file_exists($file['tmp_name']));
//    }
//
//    public function testDecodingMultipartFormDataWithFileRegistersFileCleanupEventListener()
//    {
//        $request = new Request();
//        $request->setMethod('PATCH');
//        $request->getHeaders()->addHeaderLine(
//            'Content-Type',
//            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
//        );
//        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data.txt'));
//
//        $target = new TestAsset\EventTarget();
//        $events = $this->getMock('Zend\EventManager\EventManagerInterface');
//        $events->expects($this->once())
//            ->method('attach')
//            ->with(
//                $this->equalTo('finish'),
//                $this->equalTo([$this->listener, 'onFinish']),
//                $this->equalTo(1000)
//            );
//        $target->events = $events;
//
//        $event = new MvcEvent();
//        $event->setTarget($target);
//        $event->setRequest($request);
//        $event->setRouteMatch($this->createRouteMatch([]));
//
//        $listener = $this->listener;
//        $result = $listener($event);
//    }
//
//    public function testOnFinishWillRemoveAnyUploadFilesUploadedByTheListener()
//    {
//        $tmpDir  = MultipartContentParser::getUploadTempDir();
//        $tmpFile = tempnam($tmpDir, 'zfc');
//        file_put_contents($tmpFile, 'File created by ' . __CLASS__);
//
//        $files = new Parameters([
//            'test' => [
//                'error'    => UPLOAD_ERR_OK,
//                'name'     => 'test.txt',
//                'type'     => 'text/plain',
//                'tmp_name' => $tmpFile,
//                'size'     => filesize($tmpFile),
//            ],
//        ]);
//        $request = new Request();
//        $request->setFiles($files);
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//
//        $r = new ReflectionObject($this->listener);
//        $p = $r->getProperty('uploadTmpDir');
//        $p->setAccessible(true);
//        $p->setValue($this->listener, $tmpDir);
//
//        $this->listener->onFinish($event);
//        $this->assertFalse(file_exists($tmpFile));
//    }
//
//    public function testOnFinishDoesNotRemoveUploadFilesTheListenerDidNotCreate()
//    {
//        $tmpDir  = MultipartContentParser::getUploadTempDir();
//        $tmpFile = tempnam($tmpDir, 'php');
//        file_put_contents($tmpFile, 'File created by ' . __CLASS__);
//
//        $files = new Parameters([
//            'test' => [
//                'error'    => UPLOAD_ERR_OK,
//                'name'     => 'test.txt',
//                'type'     => 'text/plain',
//                'tmp_name' => $tmpFile,
//                'size'     => filesize($tmpFile),
//            ],
//        ]);
//        $request = new Request();
//        $request->setFiles($files);
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//
//        $this->listener->onFinish($event);
//        $this->assertTrue(file_exists($tmpFile));
//        unlink($tmpFile);
//    }
//
//    public function testOnFinishDoesNotRemoveUploadFilesThatHaveBeenMoved()
//    {
//        $tmpDir  = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__);
//        mkdir($tmpDir);
//        $tmpFile = tempnam($tmpDir, 'zfc');
//
//        $files = new Parameters([
//            'test' => [
//                'error'    => UPLOAD_ERR_OK,
//                'name'     => 'test.txt',
//                'type'     => 'text/plain',
//                'tmp_name' => $tmpFile,
//            ],
//        ]);
//        $request = new Request();
//        $request->setFiles($files);
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//
//        $this->listener->onFinish($event);
//        $this->assertTrue(file_exists($tmpFile));
//        unlink($tmpFile);
//        rmdir($tmpDir);
//    }
//
//
//
//    /**
//     * @group 42
//     */
//    public function testReturns400ResponseWhenBodyPartIsMissingName()
//    {
//        $request = new Request();
//        $request->setMethod('PUT');
//        $request->getHeaders()->addHeaderLine(
//            'Content-Type',
//            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
//        );
//        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data-missing-name.txt'));
//
//        $event = new MvcEvent();
//        $event->setRequest($request);
//        $event->setRouteMatch($this->createRouteMatch([]));
//
//        $listener = $this->listener;
//        $result = $listener($event);
//
//        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $result);
//        $this->assertEquals(400, $result->getStatusCode());
//        $details = $result->getApiProblem()->toArray();
//        $this->assertContains('does not contain a "name" field', $details['detail']);
//    }
//
//    public function testReturnsArrayWhenFieldNamesHaveArraySyntax()
//    {
//        $request = new Request();
//        $request->setMethod('PUT');
//        $request->getHeaders()->addHeaderLine(
//            'Content-Type',
//            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
//        );
//        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data-array.txt'));
//        $event = new MvcEvent();
//        $event->setRequest($request);
//        $event->setRouteMatch($this->createRouteMatch([]));
//        $listener = $this->listener;
//        $result = $listener($event);
//        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
//        $params = $parameterData->getBodyParams();
//        $this->assertEquals([
//            'string_value' => 'string_value_with&amp;ersand',
//            'array_name' => [
//                'array_name[0]',
//                'array_name[1]',
//                'a' => 'array_name[a]',
//                'b' => [
//                    0 => 'array_name[b][0]',
//                    'b' => 'array_name[b][b]',
//                ],
//            ],
//        ], $params);
//    }
//

}
