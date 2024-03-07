<?php

namespace PG\Gateway;

class Response
{
    const VARIABLE_PATTERN = '/(@|#)([a-z_.]+)/i';

    protected $response;

    protected $content;

    public function __construct($response)
    {
        $this->setResponse($response);
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    public function __toString()
    {
        return $this->response;
    }

    public function getJsonResponse()
    {
        return json_decode($this->response, true);
    }

    public function getProperty($name)
    {
        $array = $this->getJsonResponse();

        $value = array_get($array, $name);

        return $value;
    }

    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    protected function findVariables()
    {
        preg_match_all(static::VARIABLE_PATTERN, $this->content, $matches);

        return array_shift($matches);
    }

    protected function getVariables()
    {
        $variables = $this->findVariables();

        foreach ($variables as $variable) {

            $variables[$variable] = substr($variable, 1);
        }

        return $variables;
    }

    public function getTranslatedContent()
    {
        $content = $this->getContent();

        $variables = $this->getVariables();

        foreach ($variables as $variable => $property) {

            $value = $this->getProperty($property);

            $content = str_replace($variable, $this->toString($value), $this->toString($content));
        }

        return $content;
    }

    protected function toString($value)
    {
        $isArray = is_array($value);

        if ($isArray) {
            return json_encode($value);
        }

        return strval($value);
    }
}