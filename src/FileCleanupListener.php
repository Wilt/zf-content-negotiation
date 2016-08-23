<?php

namespace ZF\ContentNegotiation;

use Zend\Http\Request;
use Zend\Mvc\MvcEvent;

class FileCleanupListener
{
    /**
     * @var string
     */
    protected $uploadTmpDir;

    /**
     * FileCleanupListener constructor.
     *
     * @param string $uploadTmpDir
     */
    public function __construct($uploadTmpDir)
    {
        $this->uploadTmpDir = $uploadTmpDir;
    }

    /**
     * Remove upload files if still present in filesystem
     *
     * @param MvcEvent $event
     */
    public function __invoke(MvcEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();

        foreach ($request->getFiles() as $fileInfo) {
            if (dirname($fileInfo['tmp_name']) !== $this->uploadTmpDir) {
                // File was moved
                continue;
            }

            if (! preg_match('/^zfc/', basename($fileInfo['tmp_name']))) {
                // File was moved
                continue;
            }

            if (! file_exists($fileInfo['tmp_name'])) {
                continue;
            }

            unlink($fileInfo['tmp_name']);
        }
    }
}