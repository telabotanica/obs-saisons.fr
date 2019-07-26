<?php
// src/Controller/PagesController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PagesController.
 */
class PagesController extends AbstractController
{
	/*
	 * Index axtion.
	 *
	 * @param Request $request
	 *
	 * @Route("/", name="homepage)"
	 */
	public function index(Request $request)
	{
		return $this->render('pages/accueil.html.twig');
	}
}
