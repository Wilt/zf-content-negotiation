<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\ContentNegotiation\Parser\JsonParser;
use Zend\Http\Request as HttpRequest;

class JsonParserTest extends TestCase
{
    /**
     * @var JsonParser
     */
    protected $parser;

    public function setUp()
    {
        $this->parser = new JsonParser();
    }

    /**
     * @expectedException \ZF\ContentNegotiation\Exception\InvalidJsonException
     */
    public function testJsonParserThrowsInvalidJsonExceptionForInvalidJson()
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent('Invalid JSON data');
        $parser->parse($request);
    }

    /**
     * @group 3
     * @expectedException \ZF\ContentNegotiation\Exception\InvalidJsonException
     */
    public function testJsonDecodeStringThrowsInvalidJsonException()
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent('"1"');

        $parser->parse($request);
    }

    /**
     * @expectedException \ZF\ContentNegotiation\Exception\InvalidJsonException
     */
    public function testJsonParserErrorsThrowsInvalidJsonException()
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent('Invalid JSON data');
        $parser->parse($request);
    }

    public function blankBodies()
    {
        return [
            'empty'             => [''],
            'space'             => [' '],
            'lines'             => ["\n\n"],
            'lines-and-space'   => ["  \n  \n"],
        ];
    }

    /**
     * @group 36
     * @dataProvider blankBodies
     * @param string $content
     */
    public function parserReturnsArrayWhenContentIsWhitespace($content)
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent($content);
        $result = $parser->parse($request);
        $this->assertEquals([], $result);
    }

    public function jsonContentWithLeadingWhitespace()
    {
        return [
            'space'             => [' {"foo": "bar"}'],
            'lines'             => ["\n\n{\"foo\": \"bar\"}"],
            'lines-and-space'   => ["  \n  \n{\"foo\": \"bar\"}"],
        ];
    }

    /**
     * @group 36
     * @dataProvider jsonContentWithLeadingWhitespace
     * @param string $content
     */
    public function testWillHandleJsonContentWithLeadingWhitespace($content)
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent($content);
        $result = $parser->parse($request);
        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function jsonContentWithTrailingWhitespace()
    {
        return [
            'space'             => ['{"foo": "bar"} '],
            'lines'             => ["{\"foo\": \"bar\"}\n\n"],
            'lines-and-space'   => ["{\"foo\": \"bar\"}  \n  \n"],
        ];
    }

    /**
     * @group 36
     * @dataProvider jsonContentWithTrailingWhitespace
     * @param string $content
     */
    public function testWillHandleJsonContentWithTrailingWhitespace($content)
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent($content);
        $result = $parser->parse($request);
        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function jsonContentWithLeadingAndTrailingWhitespace()
    {
        return [
            'space'             => [' {"foo": "bar"} '],
            'lines'             => ["\n\n{\"foo\": \"bar\"}\n\n"],
            'lines-and-space'   => ["  \n  \n{\"foo\": \"bar\"}  \n  \n"],
        ];
    }

    /**
     * @group 36
     * @dataProvider jsonContentWithLeadingAndTrailingWhitespace
     * @param string $content
     */
    public function testWillHandleJsonContentWithLeadingAndTrailingWhitespace($content)
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent($content);
        $result = $parser->parse($request);
        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function jsonContentWithWhitespaceInsideBody()
    {
        return [
            'space'             => ['{"foo": "bar foo"}'],
        ];
    }

    /**
     * @dataProvider jsonContentWithWhitespaceInsideBody
     * @param string $content
     */
    public function testWillNotRemoveWhitespaceInsideBody($content)
    {
        $parser = $this->parser;

        $request = new HttpRequest();
        $request->setContent($content);
        $result = $parser->parse($request);
        $this->assertEquals(['foo' => 'bar foo'], $result);
    }
}
