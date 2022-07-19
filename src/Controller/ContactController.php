<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\EmailSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\String\u;

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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vars = $request->request->get('contact');

            $honeyPot = $vars[ContactType::HONEYPOT_FIELD_NAME];
            if (u($honeyPot)->trim()->isEmpty()) {
                $message = $this->renderView('emails/contact.html.twig', [
                    'userEmail' => $vars[ContactType::EMAIL_FIELD_NAME],
                    'subject' => $vars['subject'],
                    'message' => $vars['message'],
                ]);

                $mailer->send(
                    EmailSender::CONTACT_EMAIL,
                    $mailer->getSubjectFromTitle($message),
                    $message,
                    $vars[ContactType::EMAIL_FIELD_NAME],
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
