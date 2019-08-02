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
	 * Index action.
	 *
	 * @Route("/", name="homepage")
	 */
	public function index()
	{
		return $this->render('pages/accueil.html.twig');
	}

	/*
	 * @Route("/saisie-obs", name="saisie_obs")
	 */
	public function saisieObs()
	{
		return $this->render('pages/saisie-obs.html.twig');
	}

}
