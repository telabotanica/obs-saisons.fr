<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Twig\Environment;

class MailchimpSyncContact
{
    const STATUS_ADDED = 'added';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const WEBHOOK_RESPONSE_TYPE_SUBSCRIBE = 'subscribe';

    private $logger;
    private $params;
    private $httpClient;
    private $flashBag;
    private $mailer;
    private $twig;

    public function __construct(
        LoggerInterface $logger,
        ParameterBagInterface $params,
        FlashBagInterface $flashBag,
        EmailSender $mailer,
        Environment $twig
    ) {
        $this->logger = $logger;
        $this->params = $params;
        $this->httpClient = HttpClient::createForBaseUri($this->params->get('brevo.api_base_uri'), [
//            'auth_basic' => 'key:'.$this->params->get('mailchimp.api_key'),
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key' => $this->params->get('brevo.api_key'),
                'accept' => 'application/json'
            ],
        ]);
        $this->flashBag = $flashBag;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    private function generateUrl(User $user = null)
    {
        $url = $this->params->get('brevo.api_url');

        if ($user) {
            $url .= '/'.urlencode($user->getEmail());
        }
        return $url;
    }

    /**
     * Calls Mailchimp API to subscribe a contact to our Mailchimp audience.
     * Or adds contact with "subscribed" status if not in audience.
     * Used on user profile creation/edition.
     */
    public function subscribe(User $user)
    {
        $subscriptionStatus = $this->checkSubscriptionStatus($user);

        // Si on a pas d'info, l'utilisateur n'existe pas dans brevo donc on le crée et on l'inscrit à la NL.
        if (!$subscriptionStatus) {
            $this->addContact($user);
        } else {
            if(empty($subscriptionStatus->listIds) || !in_array($this->params->get('brevo.list_id'), $subscriptionStatus->listIds)){

                $url = $this->params->get('brevo.api_url_list').'/contacts/add';

                $subscription = $this->requestApi(
                        $user,
                        'POST',
                        $url,
                        ['emails' => [$user->getEmail()]]
                    );
                if (!$subscription) {
                    $this->manageSyncFail($user, self::STATUS_SUBSCRIBED);
                }
            }
        }
/*
        // Request fail or 404 response is interpreted as unregistered contact.
        if (!$subscriptionStatus || 404 === $subscriptionStatus) {
            // On profile creation
            $this->addContact($user);
        } else {
            // on profile edition
            $url = $this->params->get('brevo.api_url_list').'/contacts/add';

            $this->requestApi(
                $user,
                'POST',
                $url,
//                ['emails' => md5(strtolower($user->getEmail()))]
                ['emails' => $user->getEmail()]
            );
        }
*/
    }

    /**
     * Calls Mailchimp API to add and subscribe a contact to our Mailchimp audience.
     * As trying to subscribe leads to add contact on check contact status fail,
     * this method doesn't need to be public.
     */
    private function addContact(User $user)
    {
        $subscription = $this->requestApi(
                $user,
                'POST',
                $this->generateUrl(),
                [
                    'email' => $user->getEmail(),
                    'listIds' => [3],
                    "ext_id" => (string)$user->getId(),
                    'attributes' => [
                        'PRENOM' => $user->getName()
                    ],
                ]
            );
        if (!$subscription){
            $this->manageSyncFail($user, self::STATUS_ADDED);
        }

    }

    /**
     * Calls Mailchimp API to unsubscribe a contact to our Mailchimp audience.
     * Used on user profile creation/edition.
     */
    public function unsubscribe(User $user)
    {
        $subscriptionStatus = $this->checkSubscriptionStatus($user);

        if ($subscriptionStatus && !empty($subscriptionStatus->listIds) && in_array($this->params->get('brevo.list_id'), $subscriptionStatus->listIds)) {
            $url = $this->params->get('brevo.api_url_list').'/contacts/remove';

            $subscription = $this->requestApi(
                    $user,
                    'POST',
                    $url,
                    ['emails' => [$user->getEmail()]]
                );

            if (!$subscription){
                $this->manageSyncFail($user, self::STATUS_UNSUBSCRIBED);
            }
        }
    }

    /**
     * Calls Mailchimp API to check a contact's status our Mailchimp audience.
     */
    public function checkSubscriptionStatus(User $user)
    {
        return $this->requestApi(
            $user,
            'GET',
            $this->generateUrl($user)
        );
    }

    /**
     * Mailchimp API call method.
     */
    private function requestApi(
        User $user,
        string $method,
        string $url,
        array $data = null,
        bool $isNewContact = false
    ) {
        $options = [];
        // string $expectedStatus used on register/subscription/unsubscription request fail,
        // for the purpose of informing user and admin about it.
        // no particular contact status expectation when self::checkSubscriptionStatus() calls the API
        $expectedStatus = null;
        if ($data) {
            $options = ['body' => json_encode($data)];
            // deduce status expectation from request body data parsing.
            /*
            if ($data['status']) {
                // on register new contact API call Mailchimp expects body['status'] === "subscribed",
                // but if request fails we need $expectedStatus to hold distinct information of registration or subscription,
                $expectedStatus = $isNewContact ? self::STATUS_ADDED : $data['status'];
            }
            */
        }

        try {
            // request API and deduce contact status from Response content.
            // expected json response:
            /*
                 {
                    "id": "...a string id...",
                    "email_address": "mail@examplel.com",
                    "unique_email_id": "...another string id...",
                    "web_id": ...an int id..,
                    "email_type": "html",
                    "status": "... subscribed/unsubscribed...",
                    "merge_fields": {
                        "FNAME": "Example First Name",
                        "LNAME": "Example Last Name",
                        "ADDRESS": "",
                        "PHONE": "",
                        "BIRTHDAY": ""
                    },
                    ...other informations...
                }
            */
            $jsonContent = $this->httpClient->request(
                $method,
                $url,
                $options
            )->getContent();

            $content = json_decode($jsonContent);

            if (!empty($content)) {
                return $content;
            }
        } catch (\Exception $e) {
//            $this->logger->error($e);
            return null;
        }

        /*
        // only fired when request failed, or getting content failed,
        // and a particular status was expected
        if ($expectedStatus) {
            $this->manageSyncFail($user, $expectedStatus);
        }
*/
        return null;
    }

    /**
     * On register/subscription/unsubscription request fail:
     * Informs user and admin about request fail (flash message and mail).
     * And rolls back user newsletter subscription status on database,
     * to keep it synchronized with mailchimp audience.
     */
    private function manageSyncFail(User $user, string $type)
    {
        $isSubscription = self::STATUS_UNSUBSCRIBED !== $type;
        $action = $isSubscription ? 'inscrire à' : 'désincrire de';

        $this->flashBag->add(
            'error',
            'Nous ne sommes pas parvenus à vous '.$action.' notre lettre d’actualités.<br>'.
            'Un message a été envoyé à l’administrateur du site afin de régler le problème'
        );

        $mailMessage = $this->twig->render('emails/newsletter-sync.html.twig', [
            'user' => $user,
            'type' => $type,
        ]);

        $this->mailer->send(
            ['contact@obs-saisons.fr', $user->getEmail()],
            $this->mailer->getSubjectFromTitle($mailMessage),
            $mailMessage
        );

        // user newsletter subscription status rollback on database
        $user->setIsNewsletterSubscriber(!$isSubscription);
    }
}
