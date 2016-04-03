<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient;

use Psr\Http\Message\RequestInterface;
use Serps\Core\Cookie\CookieJarInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\ProxyInterface;
use Serps\Core\Http\SearchEngineResponse;
use Serps\Core\UrlArchive;
use Serps\Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhantomJsClient implements HttpClientInterface
{

    protected $phantomJS;

    public function __construct($phantomJsBinary = 'phantomjs')
    {
        $this->phantomJS = $phantomJsBinary;
    }

    public function sendRequest(
        RequestInterface $request,
        ProxyInterface $proxy = null,
        CookieJarInterface $cookieJar = null
    ) {
    
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

        $initialUrl = (string)$request->getUri();

        $commandArg = [
            'method' => $request->getMethod(),
            'url'    => $initialUrl,
            'headers'=> []
        ];

        foreach ($request->getHeaders() as $headerName => $headerValues) {
            $commandArg['headers'][$headerName] = implode(',', $headerValues);
        }

        if (isset($commandArg['headers']['Host'])) {
            unset($commandArg['headers']['Host']);
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

        $response = new SearchEngineResponse(
            $dataResponse['headers'],
            $dataResponse['status'],
            $dataResponse['content'],
            true,
            UrlArchive::fromString($initialUrl),
            UrlArchive::fromString($dataResponse['url']),
            $proxy
        );

        return $response;

    }
}
