<?php

namespace PG\Gateway;

class Gateway
{
    const VARIABLE_PATTERN = '/(@|#)%s/i';

    const TYPE_BASE64 = 'base64';
    const TYPE_MD5 = 'md5';

    protected $address;

    protected $method;

    protected $isJson;

    protected $headers = [];

    protected $params = [];

    protected $types = [];

    protected $fields = [];

    public static function make()
    {
        return new static;
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setIsJson($bool)
    {
        $this->isJson = $bool;

        return $this;
    }

    public function getIsJson()
    {
        return $this->isJson;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setTypes($types)
    {
        $this->types = $types;

        return $this;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function hasType($name)
    {
        $exists = array_key_exists($name, $this->types);

        if ($exists) {
            return true;
        }

        return false;
    }

    public function getType($name)
    {
        $exists = $this->hasType($name);

        if ($exists) {
            return $this->types[$name];
        }

        return null;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getResponse()
    {
        $address = $this->translate($this->address);

        $headers = $this->mergeHeaders();

        $params = $this->mergeParams();

        if ($this->isJson) {
            $params = json_encode($params);
        } else {
            $params = http_build_query($params);
        }

        return $this->send($address, $headers, $params, $this->method);
    }

    protected function mergeHeaders()
    {
        $listOfHeaders = [];

        foreach ($this->headers as $name => $value) {

            $value = $this->translate($value);

            $listOfHeaders[] = "{$name}:{$value}";
        }

        return $listOfHeaders;
    }

    protected function mergeParams()
    {
        $listOfParams = [];

        foreach ($this->params as $name => $value) {

            $value = $this->translate($value);

            if ($this->hasType($name)) {

                $value = $this->convert($this->getType($name), $value);
            }

            array_set($listOfParams, $name, $value);
        }

        return $listOfParams;
    }

    protected function send($address, $headers, $params, $method)
    {
        $curl = curl_init($address);

        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 30];

        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        if ($params) {
            $options[CURLOPT_POSTFIELDS] = $params;
        }

        if ($method) {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return Response::make()->setStatus($status)
            ->setResponse($response);
    }

    protected function convert($type, $value)
    {
        if ($type == static::TYPE_BASE64) {

            return base64_encode($value);
        }

        if ($type == static::TYPE_MD5) {

            return md5($value);
        }

        return $value;
    }

    protected function getVariablePattern($name)
    {
        $pattern = sprintf(static::VARIABLE_PATTERN, $name);

        return $pattern;
    }

    protected function translate($content)
    {
        foreach ($this->fields as $name => $value) {

            $content = preg_replace($this->getVariablePattern($name), $this->toString($value), $this->toString($content));
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
