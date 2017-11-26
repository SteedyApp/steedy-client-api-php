<?php 
# Copyright (c) 2017, Steedy SAS.
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
#   * Redistributions of source code must retain the above copyright
#     notice, this list of conditions and the following disclaimer.
#   * Redistributions in binary form must reproduce the above copyright
#     notice, this list of conditions and the following disclaimer in the
#     documentation and/or other materials provided with the distribution.
#   * Neither the name of Steedy SAS nor the
#     names of its contributors may be used to endorse or promote products
#     derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY Steedy SAS AND CONTRIBUTORS ``AS IS'' AND ANY
# EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL Steedy SAS AND CONTRIBUTORS BE LIABLE FOR ANY
# DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
/**
 * This file contains code about \Steedy\Api class
 */
namespace Steedy;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Wrapper to manage authentication and requests with Steedy API
 *
 * @package  steedy-client-api-php
 * @category API
 */
class API
{
    
    /**
     * Urls to communicate with Steedy API
     *
     * @var array
     */
    private $endpoints = [
    'v1'        => 'https://www.1steedy.fr/api/v1',
    'v1test'    => 'https://www.1steedy.fr/api/v1/test'
    ];

    /**
     * Contain endpoint selected to choose API
     *
     * @var string
     */
    private $endpoint = null;

    /**
     * Contain id of the current application
     *
     * @var string
     */
    private $client_id = null;

    /**
     * Contain secret of the current application
     *
     * @var string
     */
    private $client_secret = null;

    /**
     * Contain token of the current application
     *
     * @var string
     */
    private $token = null;
    
    /**
     * Contain http client connection
     *
     * @var Client
     */
    private $http_client = null;
    
    /**
     * Contain the last HTTP response
     *
     * @var Response
     */
    private $http_response = null;

    /**
     * Construct a new wrapper instance
     *
     * @param string $client_id          key of your application.
     *                                   For Steedy APIs, you can create a application's credentials on
     *                                   https://api.ovh.com/createApp/
     * @param string $client_secret      secret of your application.
     * @param string $api_endpoint       name of api selected
     * @param string $token              If you have already a token, this parameter prevent to do a
     *                                   new authentication
     * @param Client $http_client        instance of http client
     *
     * @throws Exceptions\InvalidParameterException if one parameter is missing or with bad value
     */
    public function __construct(
        $client_id,
        $client_secret,
        $api_endpoint = 'v1',
        $token = null,
        Client $http_client = null
    ) {
        
        if (!isset($client_id)) {
            throw new Exceptions\InvalidParameterException("Client ID parameter is empty");
        }

        if (!isset($client_secret)) {
            throw new Exceptions\InvalidParameterException("Client secret parameter is empty");
        }

        if (!isset($api_endpoint)) {
            throw new Exceptions\InvalidParameterException("Endpoint parameter is empty");
        }

        if (!array_key_exists($api_endpoint, $this->endpoints)) {
            throw new Exceptions\InvalidParameterException("Unknown endpoint");
        }

        if (!isset($http_client)) {
            $http_client = new Client([
                'timeout'         => 25,
                'connect_timeout' => 5,
            ]);
        }

        $this->client_id          = $client_id;
        $this->client_secret      = $client_secret;
        $this->endpoint           = $this->endpoints[$api_endpoint];
        $this->http_client        = $http_client;
        $this->token              = $token;
        $this->time_delta         = null;
    }
    
    /**
     * Request a token from the API
     *
     * @param array  $accessRules list of rules your application need.
     * @param string $redirection url to redirect on your website after authentication
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException if HTTP request had error
     */
    public function auth() {
        $parameters                = new \StdClass();
        $parameters->client_id     = $this->client_id;
        $parameters->client_secret = $this->client_secret;
        
        $response = $this->decodeResponse(
            $this->rawCall(
                'POST',
                '/auth',
                $parameters,
                false
            )
        );
        
        $this->token = $response["token"];
        
        return $response;
    }
    
    /**
     * This is the main method of this wrapper. It will
     * sign a given query and return its result.
     *
     * @param string               $method           HTTP method of request (GET,POST,PUT,DELETE)
     * @param string               $path             relative url of API request
     * @param \stdClass|array|null $content          body of the request
     * @param bool                 $requires_auth    if the request needs authentication
     *
     * @return array
     * @throws \GuzzleHttp\Exception\ClientException if HTTP request had error
     */
    protected function rawCall($method, $path, $content = null, $requires_auth = true, $headers = null)
    {
        if(!in_array(strtoupper($method), array('GET','POST','PUT','DELETE'))){
            throw new Exceptions\InvalidParameterException("Method must be one of GET,POST,PUT,DELETE");
        }
        
        $url     = $this->endpoint . $path;
        $request = new Request($method, $url);
        
        if (isset($content) && $method == 'GET') { // GET request
            $query_string = $request->getUri()->getQuery();
            $query = array();
            if (!empty($query_string)) {
                $queries = explode('&', $query_string);
                foreach ($queries as $element) {
                    $key_value_query = explode('=', $element, 2);
                    $query[$key_value_query[0]] = $key_value_query[1];
                }
            }
            $query = array_merge($query, (array)$content);
            // convert boolean to string equivalents
            foreach ($query as $key => $value) {
                if ($value === false) {
                    $query[$key] = "false";
                } elseif ($value === true) {
                    $query[$key] = "true";
                }
            }
            
            $query = \GuzzleHttp\Psr7\build_query($query);
            $url     = $request->getUri()->withQuery($query);
            $request = $request->withUri($url);
            $body    = "";
        } elseif (isset($content)) { 
            $body = json_encode($content, JSON_UNESCAPED_SLASHES);
            $request->getBody()->write($body);
        } else {
            $body = "";
        } if(!is_array($headers)){
            $headers = [];
        }
        
        $headers['Content-Type']      = 'application/json; charset=utf-8';
        $headers['X-Steedy-ClientID'] = $this->client_id;
            
        if ($requires_auth) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        /** @var Response $response */
        $this->http_response = $this->http_client->send($request, ['headers' => $headers]);

        return $this->http_response;
    }
    
    /**
     * Decode a Response object body to an Array
     *
     * @param  Response $response
     *
     * @return array
     */
    private function decodeResponse(Response $response)
    {
        return json_decode($response->getBody(), true);
    }
    
    /**
     * Wrap call to Steedy API for barebone GET requests
     *
     * @param string $path    path to be requested inside api
     * @param array  $content body of request
     *
     * @return array
     * @throws \GuzzleHttp\Exception\ClientException if HTTP request had error
     */
    public function get($path, $content = null, $headers = null)
    {
        return $this->decodeResponse(
            $this->rawCall("GET", $path, $content, true, $headers)
        );
    }
    
    /**
     * Wrap call to Steedy API for POST requests
     *
     * @param string $path    path to be requested inside api
     * @param array  $content body of request
     *
     * @return array
     * @throws \GuzzleHttp\Exception\ClientException if http request is an error
     */
    public function post($path, $content = null, $headers = null)
    {
        return $this->decodeResponse(
            $this->rawCall("POST", $path, $content, true, $headers)
        );
    }
    
    /**
     * Wrap call to Steedy API for DELETE requests
     *
     * @param string $path    path ask inside api
     * @param array  $content content to send inside body of request
     *
     * @return array
     * @throws \GuzzleHttp\Exception\ClientException if http request is an error
     */
    public function delete($path, $content = null, $headers = null)
    {
        return $this->decodeResponse(
            $this->rawCall("DELETE", $path, $content, true, $headers)
        );
    }
    
    /**
     * Get the current token
     */
    public function getToken()
    {
        return $this->token;
    }
    
    /**
     * Return instance of http client
     */
    public function getHttpClient()
    {
        return $this->http_client;
    }
    
    /**
     * Return instance of http response
     */
    public function getHttpResponse()
    {
        return $this->http_response;
    }
}