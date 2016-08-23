<?php

namespace ZF\ContentNegotiation\Parser;

use ZF\ContentNegotiation\Exception;
use Zend\Http\Request;

class HalJsonParser extends JsonParser
{
    /**
     * Parse JSON
     *
     * @param Request $request
     * @throws Exception\ExceptionInterface
     * @return array
     */
    public function parse(Request $request)
    {
        $data = parent::parse($request);

        // Decode 'application/hal+json' to 'application/json' by merging _embedded into the array
        if (isset($data['_embedded'])) {
            foreach ($data['_embedded'] as $key => $value) {
                $data[$key] = $value;
            }
            unset($data['_embedded']);
        }
    }
}