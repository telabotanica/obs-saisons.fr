<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait OriginPageTrait
{
    public Session $session;
    public UrlGeneratorInterface $router;

    public function __construct(
        SessionInterface $session,
        UrlGeneratorInterface $router
    ) {
        $this->session = $session ?? new Session();
        $this->router = $router;
    }

    public function setOrigin(string $origin)
    {
        if ($this->session->has('origin')) {
            $this->session->remove('origin');
        }

        $this->session->set('origin', $origin);
    }

    public function getOrigin()
    {
        return $this->session->get('origin');
    }

    public function generateOriginUrl(string $defaultRoute = 'homepage')
    {
        return $this->getOrigin() ?? $this->router->generate($defaultRoute) ?? $this->router->generate('homepage');
    }
}
