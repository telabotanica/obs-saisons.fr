<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;

class MailchimpSyncContact
{
    const STATUS_ADDED = 'added';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    private $logger;
    private $container;
    private $httpClient;
    private $flashBag;
    private $mailer;
    private $twig;

    public function __construct(
        LoggerInterface $logger,
        ContainerInterface $container,
        FlashBagInterface $flashBag,
        EmailSender $mailer,
        Environment $twig
    ) {
        $this->logger = $logger;
        $this->container = $container;
        $this->httpClient = HttpClient::create([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => [
                    'Basic' => base64_encode(sprintf('key:%d', $this->container->getParameter('mailchimp.api_key'))),
                ],
            ],
        ]);
        $this->flashBag = $flashBag;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function addContact(User $user)
    {
        $subscriptionStatus = $this->checkSubscriptionStatus($user);

        if (!$subscriptionStatus || 404 === $subscriptionStatus) {
            $isAdded = $this->requestApi(
                $user,
                'POST',
                $this->container->getParameter('mailchimp.api_url'),
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
            $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail())),
            ['status' => self::STATUS_SUBSCRIBED]
        );
    }

    public function unsubscribe(User $user)
    {
        $this->requestApi(
            $user,
            'PUT',
            $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail())),
            ['status' => self::STATUS_UNSUBSCRIBED]
        );
    }

    public function checkSubscriptionStatus(User $user)
    {
        return $this->requestApi(
            $user,
            'GET',
            $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail()))
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
            )
                ->getContent();

            $content = json_decode($jsonContent);
            if (!empty($content) && !empty($content['status'])) {
                return $content['status'];
            }
        } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
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
            $user->getEmail(),
            //'contact@obs-saisons.fr',
            'idir.alliche.tb@gmail.com',
            $this->mailer->getSubjectFromTitle($mailMessage),
            $mailMessage
        );

        $user->setIsNewsletterSubscriber($isSubscription);
    }
}
