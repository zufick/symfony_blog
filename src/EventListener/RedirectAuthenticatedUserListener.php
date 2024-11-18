<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectAuthenticatedUserListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $restrictedRoutes = [
            '/register',
            '/login',
        ];

        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')
            && in_array($request->getPathInfo(), $restrictedRoutes, true)) {
            $event->setResponse(new RedirectResponse('/'));
        }
    }
}
