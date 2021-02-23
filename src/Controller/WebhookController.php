<?php

/*
 * Mailchimp Webhook for synchronizing newsletter audience on database:
 * MailChimp calls this webhook every time a user subscribes, resubscribes or unsubscribes, from the newsletter's mail link,
 * or when admin fires one of these events from our audience on our mailchimp account.
 * It sends with method POST one of the responses below depending on subscribe or unsubscribe event
 * This allows us to synchronize our users's subscription param in database with our audience on Mailchimp
 * see : https://mailchimp.com/developer/marketing/guides/sync-audience-data-webhooks/
 * expected json subscribe responses:
    "{
        "type": "subscribe",
        "fired_at": "2009-03-26 21:35:57",
        "data": {
            "id": "8a25ff1d98",
            "list_id": "a6b5da1054",
            "email": "mail@example.com",
            "email_type": "html",
            "ip_opt": "10.20.10.30",
            "ip_signup": "10.20.10.30"
            "merges": {
                "EMAIL": "mail@example.com",
                "FNAME": "Example First Name",
                "LNAME": "Example Last Name",
                "INTERESTS": "Group1,Group2"
            }
        }
    }"

 * expected json unsubscribe responses:
    "{
        "type": "unsubscribe",
        "fired_at": "2009-03-26 21:40:57",
        "data": {
            "action": "unsub",
            "reason": "manual",
            "id": "8a25ff1d98",
            "list_id": "a6b5da1054",
            "email": "mail@example.com",
            "email_type": "html",
            "ip_opt": "10.20.10.30",
            "campaign_id": "cb398d21d2",
            "merges": {
                "EMAIL": "mail@example.com",
                "FNAME": "Example First Name",
                "LNAME": "Example Last Name",
                "INTERESTS": "Group1,Group2"
            }
        }
    }"

 * All we need, are the informations on type (response['type'] : subscribe/unsubscribe), and email (response['data']['email']).
 * The route is designed to check if last part of url matches mailchimp.list_id param, as the call must target the right list,
 * and require list ID limits access to this controller.
 * As Mailchimp tests this callback url with an empty request, as soon as the url (with list ID) check is ok, the controller sends response code 200.
 * Otherwise, as the url does't exist, it's a 404 code.
 * When controller gets request, if all informations are correct we can synchronize isNewletterSubscriber param to true or false depending on POST provided "type".
 */

namespace App\Controller;

use App\Entity\User;
use App\Service\MailchimpSyncContact;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    /**
     * @Route("/webhook/sync-mailchimp-contact/{listId}", name="webhook_sync_mailchimp_contact", requirements={"listId"="%mailchimp.list_id%"}, methods={"POST"})
     */
    public function syncMailchimpContactsWebhook(
        Request $request,
        EntityManagerInterface $manager,
        LoggerInterface $logger
    ) {
        $type = $request->request->get('type');
        $data = $request->request->get('data');
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
            }
        }

        return new Response(
            null,
            Response::HTTP_OK
        );
    }
}
