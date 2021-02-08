<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailchimpSyncContact;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    /**
     * @Route("/webhook/sync-mailchimp-contact/{listId}", name="webhook_sync_mailchimp_contact", methods={"POST"})
     */
    public function syncMailchimpContactsWebhook(
        string $listId,
        EntityManagerInterface $manager,
        LoggerInterface $logger
    ) {
        if ($this->getParameter('mailchimp.list_id') === $listId) {
            if ($_POST) {
                $type = $_POST['type'];
                $data = $_POST['data'];
                if ($type && $data && $data['email']) {
                    /**
                     * @var User $user
                     */
                    $user = $manager->getRepository(User::class)
                        ->findOneBy(['email' => $data['email']]);

                    if (!$user) {
                        $logger->alert(sprintf('unsubscribed user with email %s not found, check mailchimp audience', $data['email']));
                    } else {
                        $isNewsLetterSubscriber = MailchimpSyncContact::WEBHOOK_RESPONSE_TYPE_SUBSCRIBE === $type;
                        if ($isNewsLetterSubscriber !== $user->getIsNewsLetterSubscriber()) {
                            $user->setIsNewsLetterSubscriber($isNewsLetterSubscriber);
                            $manager->flush();
                        }

                        return new Response(
                            null,
                            Response::HTTP_OK
                        );
                    }
                }
            }

        }

        return new Response(
            null,
            Response::HTTP_NOT_FOUND
        );

    }
}
