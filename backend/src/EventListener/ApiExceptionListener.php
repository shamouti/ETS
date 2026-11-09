<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/** Rend toutes les erreurs HTTP de l'API au format JSON. */
#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $payload = ['error' => $exception->getMessage()];

        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $payload['error'] = 'Validation failed';
            $payload['violations'] = [];
            foreach ($previous->getViolations() as $violation) {
                $payload['violations'][$violation->getPropertyPath()] = $violation->getMessage();
            }
        }

        $event->setResponse(new JsonResponse($payload, $exception->getStatusCode(), $exception->getHeaders()));
    }
}
