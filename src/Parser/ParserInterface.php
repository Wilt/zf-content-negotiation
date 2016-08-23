<?php

namespace ZF\ContentNegotiation\Parser;

use ZF\ContentNegotiation\Exception;
use Zend\Http\Request;

interface ParserInterface
{
    /**
     * Parse content body of the request
     *
     * @param Request $request
     * @throws Exception\ExceptionInterface
     * @return array
     */
    public function parse(Request $request);
}