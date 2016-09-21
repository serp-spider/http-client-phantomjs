<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\PhantomJsClient;
use Serps\Test\HttpClient\HttpClientTestsCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Zend\Diactoros\Request;

use Zend\Diactoros\Response;

/**
 * @covers Serps\HttpClient\PhantomJsClient
 */
class PhantomJsClientTest extends HttpClientTestsCase
{
    public function getHttpClient()
    {
        return new PhantomJsClient(__DIR__ . '/../../vendor/bin/phantomjs');
    }

    public function testCookies()
    {
        $this->markTestSkipped('Cookies not supported');
    }

    public function testSetCookies()
    {
        $this->markTestSkipped('Cookies not supported');
    }


    public function testProcessFails()
    {
        $client = new PhantomJsClient('exit 1 &&');
        $request = new Request('http://httpbin.org/get', 'GET');
        $this->setExpectedException(ProcessFailedException::class);
        $client->sendRequest($request);
    }

    public function testSocks4Proxy()
    {
        $this->markTestSkipped('Phantomjs does not support socks4 proxies');
    }

    public function testHttpProxy()
    {
        $this->markTestSkipped('http proxy server does not work yet');
    }

    public function testSocks5Proxy()
    {
        $this->markTestSkipped('socks5 proxy server does not work yet');
    }
}
