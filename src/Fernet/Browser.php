<?php

declare(strict_types=1);

namespace Fernet;

use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;

class Browser extends AbstractBrowser
{
    private string $mainComponent;

    public function setMainComponent(string $component): void
    {
        $this->mainComponent = $component;
    }

    /**
     * @param \Symfony\Component\BrowserKit\Request $request
     * @return Response
     */
    protected function doRequest($request): Response
    {
        $_SESSION = [];
        $httpRequest = Request::create($request->getUri(), $request->getMethod(), $request->getParameters(), $request->getCookies(), $request->getFiles(), $request->getServer(), $request->getContent());
        $response = Framework::getInstance()->run($this->mainComponent, $httpRequest);

        return new Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
    }
}
