<?php
namespace Sonic;

/**
 * Curl
 *
 * @category Sonic
 * @package Curl
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Curl
{
    /**
     * @var string
     */
    protected $_url;

    /**
     * @var array
     */
    protected $_params = array();

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * constructor
     *
     * @param string $url base url of request
     * @return void
     */
    public function __construct($url)
    {
        $this->_url = $url;
    }

    /**
     * adds a querystring/post parameter to the request
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addParam($key, $value)
    {
        $this->_params[$key] = $value;
    }

    /**
     * adds an array of parameters
     *
     * @param array $params
     * @return void
     */
    public function addParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->_params[$key] = $value;
        }
    }

    /**
     * sets a curl option for the request
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }

    /**
     * adds an array of options
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->_options[$key] = $value;
        }
    }

    /**
     * sets timeout
     *
     * @param int $timeout
     * @return void
     */
    public function setTimeout($time)
    {
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $time);
    }

    /**
     * tells this request to use post
     *
     * @return void
     */
    public function usePost()
    {
        $this->setOption(CURLOPT_POST, true);
    }

    /**
     * gets the response for the request
     *
     * @return string || false on failure
     */
    public function getResponse()
    {
        $url = $this->_url;

        if (count($this->_params)) {
            $query_string = http_build_query($this->_params);
            $url .= '?' . $query_string;
        }

        $ch = curl_init();

        // set some default values
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // set user generated values
        foreach ($this->_options as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function getHeaders($options = array())
    {
        $this->setOption(CURLOPT_HEADER, 1);
        $this->setOption(CURLOPT_NOBODY, 1);
        $this->setOptions($options);

        $response = $this->getResponse();

        $lines = explode("\n", $response);
        $headers = array();
        foreach ($lines as $line) {
            if (strpos($line, ':') === false)
                continue;
            $bits = explode(':', $line);

            // for urls
            if (isset($bits[2])) {
                $bits[1] = $bits[1] . ':' . $bits[2];
            }

            $headers[$bits[0]] = trim($bits[1]);
        }
        return $headers;
    }
}
