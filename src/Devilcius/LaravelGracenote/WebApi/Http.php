<?php

namespace Devilcius\LaravelGracenote\WebAPI;

use Illuminate\Support\Facades\Config;

/**
 * A class to handle all external communication via HTTP(S)
 *
 */
class Http
{

    // Constants
    const GET = 0;
    const POST = 1;

    // Members
    private $url;                  // URL to send the request to.
    private $timeout;              // Seconds before we give up.
    private $headers = array();   // Any headers to send with the request.
    private $postData = null;      // The POST data.
    private $curlHandle = null;      // cURL handle
    private $httpMethod = HTTP::GET; // Default is GET
    private $debugMode = false;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Ctor

    public function __construct($url, $timeout = 10000)
    {
        $this->url = $url;
        $this->timeout = $timeout;
        $this->debugMode = Config::get('gracenote.debug_mode');

        // Prepare the cURL handle.
        $this->curlHandle = curl_init();

        // Set connection options.
        curl_setopt($this->curlHandle, CURLOPT_URL, $this->url);     // API URL
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, "laravel-gracenote"); // Set our user agent
        curl_setopt($this->curlHandle, CURLOPT_FAILONERROR, true);            // Fail on error response.
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, true);            // Follow any redirects
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);            // Put the response into a variable instead of printing.
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT_MS, $this->timeout); // Don't want to hang around forever.
    }

    // Dtor
    public function __destruct()
    {
        if ($this->curlHandle != null) {
            curl_close($this->curlHandle);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Prepare the cURL handle
    private function prepare()
    {
        // Set header data
        if ($this->headers != null) {
            $hdrs = array();
            foreach ($this->headers as $header => $value) {
                // If specified properly (as string) use it. If name=>value, convert to name:value.
                $hdrs[] = ((strtolower(substr($value, 0, 1)) === "x") && (strpos($value, ":") !== false)) ? $value : $header . ":" . $value;
            }
            curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $hdrs);
        }

        // Add POST data if it's a POST request
        if ($this->httpMethod == HTTP::POST) {
            curl_setopt($this->curlHandle, CURLOPT_POST, true);
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $this->postData);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////

    public function execute()
    {
        // Prepare the request
        $this->prepare();

        // Now try to make the call.
        $response = null;
        try {
            if ($this->debugMode) {
                echo("http: external request " . (($this->httpMethod == HTTP::GET) ? "GET" : "POST") . " url=" . $this->url . ", timeout=" . $this->timeout . "\n");
            }

            // Execute the request
            $response = curl_exec($this->curlHandle);
        } catch (Exception $e) {
            throw new GNException(GNError::HTTP_REQUEST_ERROR);
        }

        // Validate the response, or throw the proper exceptionS.
        $this->validateResponse($response);

        return $response;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // This validates a cURL response and throws an exception if it's invalid in any way.
    public function validateResponse($response, $errno = null)
    {
        $curl_error = ($errno === null) ? curl_errno($this->curlHandle) : $errno;
        if ($curl_error !== CURLE_OK) {
            switch ($curl_error) {
                case CURLE_HTTP_NOT_FOUND: throw new GNException(GNError::HTTP_RESPONSE_ERROR_CODE, $this->getResponseCode());
                case CURLE_OPERATION_TIMEOUTED: throw new GNException(GNError::HTTP_REQUEST_TIMEOUT);
            }

            throw new GNException(GNError::HTTP_RESPONSE_ERROR, $curl_error);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////

    public function getHandle()
    {
        return $this->curlHandle;
    }

    public function getResponseCode()
    {
        return curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPOST()
    {
        $this->httpMethod = HTTP::POST;
    }

    public function setGET()
    {
        $this->httpMethod = HTTP::GET;
    }

    public function setPOSTData($data)
    {
        $this->postData = $data;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function setCurlOpt($o, $v)
    {
        curl_setopt($this->curlHandle, $o, $v);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Wrappers
    public function get()
    {
        $this->setGET();
        return $this->execute();
    }

    public function post($data = null)
    {
        if ($data != null) {
            $this->postData = $data;
        }
        $this->setPOST();
        return $this->execute();
    }

}
