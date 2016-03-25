<?php
/**
 * @license see LICENSE
 */
namespace Serps\HttpClient;

use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\PhantomJsClient;
use Zend\Diactoros\Request;

use Zend\Diactoros\Response;

/**
 * @covers Serps\HttpClient\PhantomJsClient
 */
class PhantomJsClientTest extends \PHPUnit_Framework_TestCase
{

    public function mockRequest()
    {

    }

    public function testGetRequest()
    {
        $client = new PhantomJsClient(__DIR__ . '/../../vendor/bin/phantomjs');

        $request = new Request('http://httpbin.org/get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');
        $request = $request->withHeader('Accept', 'application/json');

        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);

        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl());
    }

    public function testRedirectRequest()
    {
        $client = new PhantomJsClient(__DIR__ . '/../../vendor/bin/phantomjs');

        $request = new Request('http://httpbin.org/redirect-to?url=get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');

        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);

        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl());
    }
}
