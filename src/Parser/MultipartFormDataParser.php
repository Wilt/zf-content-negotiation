<?php

namespace ZF\ContentNegotiation\Parser;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use ZF\ContentNegotiation\FileCleanupListener;
use ZF\ContentNegotiation\MultipartContentParser;
use Zend\Http\Request;
use ZF\ContentNegotiation\Exception;

class MultipartFormDataParser implements ParserInterface, EventManagerAwareInterface
{
    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * MultipartFormDataParser constructor.
     *
     * @param EventManagerInterface $eventManager
     */
    public function __construct(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Parse JSON from request
     *
     * @param Request $request
     * @throws Exception\ExceptionInterface
     * @return array
     */
    public function parse(Request $request)
    {
        $bodyParams = [];
        $method = $request->getMethod();
        switch($method){
            case Request::METHOD_POST:
                $bodyParams = $request->getPost()->toArray();
                break;
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
            case $request::METHOD_DELETE:
                $contentType = $request->getHeader('Content-Type');
                $parser = new MultipartContentParser($contentType, $request);
                $uploadTmpDir = $parser->getUploadTempDir();
                $bodyParams = $parser->parse();
                if ($request->getFiles()->count()) {
                    $this->attachFileCleanupListener($uploadTmpDir);
                }
                break;
        }
        return $bodyParams;
    }

    /**
     * Attach the file cleanup listener
     *
     * @param string $uploadTmpDir Directory in which file uploads were made
     */
    protected function attachFileCleanupListener($uploadTmpDir)
    {
        $listener = new FileCleanupListener($uploadTmpDir);
        $eventManager = $this->getEventManager();
        $eventManager->attach('finish', $listener);
    }

    /**
     * @inheritdoc
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }
}