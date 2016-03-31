<?php
/**
 * @license see LICENSE
 */
namespace Serps\HttpClient;

use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\PhantomJsClient;
use Serps\Test\HttpClient\HttpClientTestsCase;
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
}
