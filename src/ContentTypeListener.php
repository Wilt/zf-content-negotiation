<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Http\Header\ContentType;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\Parser\ParserInterface;
use ZF\ContentNegotiation\Parser\ParserPluginManager;

class ContentTypeListener
{
    /**
     * @var array
     */
    protected $methods = [
        Request::METHOD_POST,
        Request::METHOD_PATCH,
        Request::METHOD_PUT,
        Request::METHOD_DELETE
    ];

    /**
     * ContentTypeListener constructor.
     *
     * @param ParserPluginManager $parserPluginManager
     * @param array $parsers
     */
    public function __construct(ParserPluginManager $parserPluginManager, $parsers)
    {
        $this->parserPluginManager = $parserPluginManager;
        $this->parsers = $parsers;
    }

    /**
     * @var ParserPluginManager
     */
    protected $parserPluginManager;

    /**
     * Content-Type parser map
     *
     * @var array
     */
    protected $parsers;

    /**
     * Perform content negotiation
     *
     * For HTTP methods expecting body content, attempts to match the incoming
     * content-type against the list of allowed content types, and then performs
     * appropriate content deserialization.
     *
     * If an error occurs during deserialization, an ApiProblemResponse is
     * returned, indicating an issue with the submission.
     *
     * @param  MvcEvent $event
     * @return void|ApiProblemResponse
     */
    public function __invoke(MvcEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }
        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeMatch  = $event->getRouteMatch();
        $routeParams = $routeMatch->getParams();
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($request->getQuery()->toArray());

        // body parameters:
        $bodyParams  = [];
        $method = $request->getMethod();

        if (in_array($method, $this->methods)) {
            $contentType = $request->getHeader('Content-Type');
            $parser = $this->getParserForContentType($contentType);
            if (!$parser){
                $apiProblemResponse = new ApiProblemResponse(new ApiProblem(
                    415,
                    'Unsupported Media Type'

                ));
                return $apiProblemResponse;
            }
            try {
                $bodyParams = $parser->parse($request);
            } catch (Exception\ParseException $exception) {
                $apiProblemResponse = new ApiProblemResponse(new ApiProblem(
                    400,
                    $exception
                ));
                return $apiProblemResponse;
            }
        }

        $parameterData->setBodyParams($bodyParams);
        $event->setParam('ZFContentNegotiationParameterData', $parameterData);
    }

    /**
     * Get parser for content type
     *
     * @param ContentType $contentType
     * @return ParserInterface|false
     */
    protected function getParserForContentType(ContentType $contentType)
    {
        $map = $this->parsers;
        $parserPluginManager = $this->getParserPluginManager();

        foreach ($map as $key => $parser) {
            if ($contentType->match($key)) {
                if (!$parserPluginManager->has($parser)) {
                    return false;
                }
                return $parserPluginManager->get($parser);
            }
        }
        return false;
    }

    /**
     * Get parser plugin manager
     *
     * @return ParserPluginManager
     */
    public function getParserPluginManager()
    {
        return $this->parserPluginManager;
    }

    /**
     * Get parser plugin manager
     *
     * @param ParserPluginManager $parserPluginManager
     * @return self
     */
    public function setParserPluginManager(ParserPluginManager $parserPluginManager)
    {
        $this->parserPluginManager = $parserPluginManager;
        return $this;
    }
}
