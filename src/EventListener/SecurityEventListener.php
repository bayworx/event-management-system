<?php

namespace App\EventListener;

use App\Service\ApplicationLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityEventListener
{
    public function __construct(
        private ApplicationLogger $appLogger,
        private RequestStack $requestStack
    ) {
    }

    #[AsEventListener(event: AuthenticationSuccessEvent::class)]
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $this->requestStack->getCurrentRequest();
        
        $this->appLogger->logSecurityEvent(
            'authentication_success',
            $user,
            $request,
            [
                'authentication_method' => get_class($event->getAuthenticationToken()),
            ]
        );
    }

    #[AsEventListener(event: InteractiveLoginEvent::class)]
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $event->getRequest();
        
        $this->appLogger->logSecurityEvent(
            'user_login',
            $user,
            $request,
            [
                'login_type' => 'interactive',
                'user_type' => $user::class,
            ]
        );
    }

    #[AsEventListener(event: LogoutEvent::class)]
    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        $request = $event->getRequest();
        
        if ($user) {
            $this->appLogger->logSecurityEvent(
                'user_logout',
                $user,
                $request,
                [
                    'user_type' => $user::class,
                ]
            );
        }
    }
}