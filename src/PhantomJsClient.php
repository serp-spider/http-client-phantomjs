<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\ProxyInterface;
use Zend\Diactoros\Response;

class PhantomJsClient implements HttpClientInterface{

    /**
     * @var BaseClient
     */
    protected $phantomJS;

    public function __construct($phantomJsPath = null)
    {

    }

    public function sendRequest(RequestInterface $request, ProxyInterface $proxy = null)
    {
        $phjsRequest = $this->phantomJS->getMessageFactory()->createRequest();
        $phjsResponse = $this->phantomJS->getMessageFactory()->createResponse();

        $phjsRequest->setMethod($request->getMethod());
        $phjsRequest->setUrl($request->getUri());
        $phjsRequest->setHeaders($request->getHeaders());

        $oldOptions = $this->phantomJS->getEngine()->getOptions();
        $this->phantomJS->getEngine()->setOptions([]);

        if($proxy){
            $proxyHost = $proxy->getIp() . ':' . $proxy->getPort();
            $this->phantomJS->getEngine()->addOption('--proxy=' . $proxyHost);

            if ($user = $proxy->getUser()) {
                $proxyAuth = $user;
                if ($password = $proxy->getPassword()) {
                    $proxyAuth .= ':' . $proxyAuth;
                }
                $this->phantomJS->getEngine()->addOption('--proxy-auth=' . $proxyAuth);
            }
        }

        $this->phantomJS->send($phjsRequest, $phjsResponse);
        $this->phantomJS->getEngine()->setOptions($oldOptions);


        if($phjsResponse->isRedirect()){
            $effectiveUrl = $phjsResponse->getRedirectUrl();
        }else{
            $effectiveUrl = $phjsResponse->getUrl();
        }

        if(!$effectiveUrl){
            $effectiveUrl = "";
        }

        $headers = $phjsResponse->getHeaders() + [
            'X-SERPS-PROXY' => $proxy ? (string)$proxy : '',
            'X-SERPS-EFFECTIVE-URL' => $effectiveUrl
        ];




        $response = new Response(
            'php://memory',
            $phjsResponse->getStatus(),
            $headers
        );

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $body->write($phjsResponse->getContent());


        return $response;

    }


}
