<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\ContactType;
use App\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    /**
     * @Route("/newsletter/{postId}", name="envoi_newsletter")
     */
    public function sendNewsletters(
        int $postId,
        Request $request,
        EmailSender $mailer,
        EntityManagerInterface $manager
    ) {
        $newsletter = $manager->getRepository(Post::class)->find($postId);

        if (!$newsletter) {
            throw $this->createNotFoundException('La newsletter n’existe pas');
        }

        $message = $this->renderView('emails/newsletter.html.twig', [
            'content' => $newsletter->getContent(),
            'cover' => $newsletter->getCover()
        ]);

        $subject = $newsletter->getTitle();
        // TODO changer ça pour récupérer la liste des inscrits à la NL
        $emails = ['julien@tela-botanica.org','sf-test@tela-botanica.org'];

        foreach ($emails as $email){
            $mailer->sendNewsletter(
                $email,
                $subject,
                $message
            );
        }

        $this->addFlash('success', 'La newsletter a été envoyée');

        return $this->redirectToRoute('admin_newsletters_list');
    }
}
