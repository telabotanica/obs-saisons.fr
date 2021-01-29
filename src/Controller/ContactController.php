<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\BreadcrumbsGenerator;
use App\Service\EmailSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PagesController.
 */
class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function contact(
        Request $request,
        EmailSender $mailer
    ) {
        $form = $this->createForm(ContactType::class);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $vars = $request->request->get('contact');

                $message = $message = $this->renderView('emails/contact.html.twig', [
                    'userEmail' => $vars['email'],
                    'subject' => $vars['subject'],
                    'message' => $vars['message'],
                ]);

                $mailer->send(
                    $vars['email'],
                    'contact@obs-saisons.fr',
                    $mailer->getSubjectFromTitle($message),
                    $message
                );
                $this->addFlash('success', 'Votre message a été envoyé');
            } else {
                $this->addFlash('error', 'Votre message n’a pas pu être envoyé');
            }

            return $this->redirectToRoute('homepage');
        }

        return $this->render('pages/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
