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
        $this->httpClient = HttpClient::createForBaseUri($this->params->get('mailchimp.api_base_uri'), [
            'auth_basic' => 'key:'.$this->params->get('mailchimp.api_key'),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->flashBag = $flashBag;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    private function generateUrl(User $user = null)
    {
        $url = $this->params->get('mailchimp.api_url');
        if ($user) {
            $url .= '/'.md5(strtolower($user->getEmail()));
        }

        return $url;
    }

    public function addContact(User $user)
    {
        $subscriptionStatus = $this->checkSubscriptionStatus($user);

        if (!$subscriptionStatus || 404 === $subscriptionStatus) {
            $this->requestApi(
                $user,
                'POST',
                $this->generateUrl(),//no user email info in url
                [
                    'email_address' => $user->getEmail(),
                    'status' => self::STATUS_SUBSCRIBED,
                    'merge_fields' => [
                        'FNAME' => $user->getName(),
                        'LNAME' => $user->getName(),
                    ],
                ]
            );
        }
    }

    public function subscribe(User $user)
    {
        $this->requestApi(
            $user,
            'PUT',
            $this->generateUrl($user),
            ['status' => self::STATUS_SUBSCRIBED]
        );
    }

    public function unsubscribe(User $user)
    {
        $this->requestApi(
            $user,
            'PUT',
            $this->generateUrl($user),
            ['status' => self::STATUS_UNSUBSCRIBED]
        );
    }

    public function checkSubscriptionStatus(User $user)
    {
        return $this->requestApi(
            $user,
            'GET',
            $this->generateUrl($user)
        );
    }

    private function requestApi(
        User $user,
        string $method,
        string $url,
        array $data = null,
        bool $isNewContact = false
    ) {
        $options = [];
        $expectedStatus = null;
        if ($data) {
            $options = ['body' => json_encode($data)];
            if ($data['status']) {
                $expectedStatus = $isNewContact ? self::STATUS_ADDED : $data['status'];
            }
        }

        try {
            $jsonContent = $this->httpClient->request(
                $method,
                $url,
                $options
            )->getContent();

            $content = json_decode($jsonContent);
            if (!empty($content) && !empty($content->status)) {
                return $content->status;
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        if ($expectedStatus) {
            $this->manageSyncFail($user, $expectedStatus);
        }

        return null;
    }

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
            'contact@obs-saisons.fr',
            ['contact@obs-saisons.fr', $user->getEmail()],
            $this->mailer->getSubjectFromTitle($mailMessage),
            $mailMessage
        );

        $user->setIsNewsletterSubscriber(!$isSubscription);
    }
}
