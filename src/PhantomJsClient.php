<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\ProxyInterface;
use Serps\Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Zend\Diactoros\Response;

class PhantomJsClient implements HttpClientInterface
{

    /**
     * @var BaseClient
     */
    protected $phantomJS;

    public function __construct($phantomJsBinary = 'phantomjs')
    {
        $this->phantomJS = $phantomJsBinary;
    }

    public function sendRequest(RequestInterface $request, ProxyInterface $proxy = null)
    {
        $commandOptions = [];
        if ($proxy) {
            $proxyHost = $proxy->getIp() . ':' . $proxy->getPort();
            $commandOptions[]= '--proxy=' . $proxyHost;

            if ($user = $proxy->getUser()) {
                $proxyAuth = $user;
                if ($password = $proxy->getPassword()) {
                    $proxyAuth .= ':' . $proxyAuth;
                }
                $commandOptions[]= '--proxy-auth=' . $proxyAuth;
            }
        }


        $commandArg = [
            'method' => $request->getMethod(),
            'url'    => (string)$request->getUri(),
            'headers'=> []
        ];

        foreach ($request->getHeaders() as $headerName => $headerValues) {
            $commandArg['headers'][$headerName] = implode(',', $headerValues);
        }

        $data = (string)$request->getBody();
        if ($data) {
            $commandArg['data'] = $data;
        }

        $scriptFile = __DIR__ . '/phantomjs.js';
        $commandOptions = implode(' ', $commandOptions);
        $commandArg = json_encode($commandArg);

        $process = new Process("$this->phantomJS $commandOptions $scriptFile '$commandArg'");
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $dataResponse = json_decode($process->getOutput(), true);
        if (!$dataResponse) {
            throw new Exception('Unable to parse Phantomjs response: ' . json_last_error_msg());
        }

        $headers = [
            'X-SERPS-PROXY' => $proxy ? (string)$proxy : '',
            'X-SERPS-EFFECTIVE-URL' => $dataResponse['url'],
            'X-SERPS-JAVASCRIPT-ENABLED' => 1
        ];

        $response = new Response(
            'php://memory',
            $dataResponse['status'],
            $dataResponse['headers'] + $headers
        );

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $body->write($dataResponse['content']);


        return $response;

    }
}
