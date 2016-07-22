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
    protected $phantomJsOptions = [];

    public function __construct($phantomJsBinary = 'phantomjs')
    {
        $this->phantomJS = $phantomJsBinary;
    }

    /**
     * Set options for phantomjs command line
     *
     * example: ``$client->setPhantomJsOption('--ignore-ssl-errors=true');``
     *
     * @param string $option option to pass to the phantomjs command line
     */
    public function setPhantomJsOption($option)
    {
        $this->phantomJsOptions[] = $option;
    }

    public function getPhantomJsOptions()
    {
        return $this->phantomJsOptions;
    }

    public function sendRequest(
        RequestInterface $request,
        ProxyInterface $proxy = null,
        CookieJarInterface $cookieJar = null
    ) {

        $commandOptions = $this->phantomJsOptions;
        if ($proxy) {
            $proxyHost = $proxy->getIp() . ':' . $proxy->getPort();
            $commandOptions[]= '--proxy=' . $proxyHost;

            if ($user = $proxy->getUser()) {
                $proxyAuth = $user;
                if ($password = $proxy->getPassword()) {
                    $proxyAuth .= ':' . $password;
                }
                $commandOptions[]= '--proxy-auth=' . $proxyAuth;
            }

            if ($proxy->getType() == 'SOCKS5') {
                $commandOptions[]= '--proxy-type=socks5';
            } elseif ($proxy->getType() == 'SOCKS4') {
                $commandOptions[]= '--proxy-type=socks4';
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
