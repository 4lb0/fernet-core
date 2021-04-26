<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Config;
use Fernet\Framework;
use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Run
{
    public function __construct(private Router $router, private Logger $log, private Config $config)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(Stringable | string $component, Request $request): Response
    {
        try {
            $response = $this->router->route($component, $request);
        } catch (NotFoundException $notFoundException) {
            $this->log->notice('Route not found');

            return new Response(
                $this->showError($notFoundException, 'error404'),
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $error) {
            $this->log->error($error->getMessage());
            $response = new Response(
                $this->showError($error),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $response->prepare($request);

        return $response;
    }

    /**
     * @throws Throwable
     */
    public function showError(Throwable $error, string $type = 'error500'): ?string
    {
        if (!Framework::config('devMode')) {
            try {
                $component = $this->config->errorPages[$type];

                return (new ComponentElement($component))->render();
            } catch (Throwable $e) {
                $this->log->error('Error when trying to show the error', [$e]);

                return
                    'Error: '.$error->getMessage()
                    .' (Failing to display error: '.$e->getMessage().')';
            }
        }

        throw $error;
    }
}
