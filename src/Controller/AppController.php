<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/dump")
     */
    public function dump()
    {
        $output = ';-)';

        $user = $this->getUser();

        if ($user && $user->isAdmin()) {
            ob_start();
            phpinfo();
            $output = ob_get_clean();
        }

        return new Response($output);
    }
}
