<?php

namespace ZF\ContentNegotiation\Parser;

use Zend\Http\Request;
use ZF\ContentNegotiation\Exception;

class JsonParser implements ParserInterface
{
    /**
     * @var array
     */
    protected $jsonErrors = [
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
        JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    /**
     * Parse JSON
     *
     * @param Request $request
     * @throws Exception\ExceptionInterface
     * @return array
     */
    public function parse(Request $request)
    {
        $json = $request->getContent();
        // Trim whitespace from front and end of string to avoid parse errors
        $json = trim($json);

        // If the data is empty, return an empty array to prevent JSON decode errors
        if (empty($json)) {
            return [];
        }

        $data = json_decode($json, true);
        $isArray = is_array($data);

        if ($isArray) {
            return $data;
        }

        $error = json_last_error();
        if ($error === JSON_ERROR_NONE && $isArray) {
            return $data;
        }

        $message = array_key_exists($error, $this->jsonErrors) ? $this->jsonErrors[$error] : 'Unknown error';

        throw new Exception\InvalidJsonException(sprintf('JSON decoding error: %s', $message));
    }
}