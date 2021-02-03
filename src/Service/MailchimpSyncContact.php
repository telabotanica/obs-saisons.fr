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
use Symfony\Contracts\HttpClient\ResponseInterface;
use Twig\Environment;

class MailchimpSyncContact
{
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
                'POST',
                $this->container->getParameter('mailchimp.api_url'),
                [
                    'email_address' => $user->getEmail(),
                    'status' => 'subscribed',
                    'merge_fields' => [
                        'FNAME' => $user->getName(),
                        'LNAME' => $user->getName(),
                    ],
                ]
            );

            if (!$isAdded) {
                $this->manageSyncFail($user, true);
            }
        } else {
            $this->subscribe($user, $subscriptionStatus);
        }
    }

    public function subscribe(User $user, $subscriptionStatus = null)
    {
        $subscriptionStatus = $subscriptionStatus ?? $this->checkSubscriptionStatus($user);
        $isSubscribed = $subscriptionStatus && 'subscribed' === $subscriptionStatus;

        if (!$isSubscribed) {
            $isSubscribed = $this->requestApi(
                'PUT',
                $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail())),
                ['status' => 'subscribed']
            );
        }

        if (!$isSubscribed) {
            $this->manageSyncFail($user, true);
        }
    }

    public function unsubscribe(User $user)
    {
        $subscriptionStatus = $this->checkSubscriptionStatus($user);
        $isUnubscribed = $subscriptionStatus && 'unsubscribed' === $subscriptionStatus;

        if (!$isUnubscribed) {
            $isUnubscribed = $this->requestApi(
                'PUT',
                $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail())),
                ['status' => 'unsubscribed']
            );
        }

        if (!$isUnubscribed) {
            $this->manageSyncFail($user, false);
        }
    }

    public function checkSubscriptionStatus(User $user)
    {
        if (!empty($this->httpClient)) {
            try {
                $jsonContent = $this->httpClient->request(
                    'GET',
                    $this->container->getParameter('mailchimp.api_url').'/'.md5(strtolower($user->getEmail()))
                )
                ->getContent();

                $content = json_decode($jsonContent);
                if (!empty($content) && !empty($content['status'])) {
                    return $content['status'];
                }
            } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
                $this->logger->error($e);
            }
        }

        return null;
    }

    private function requestApi(string $method, string $url, array $data)
    {
        if (!empty($this->httpClient)) {
            try {
                $response = $this->httpClient->request(
                    $method,
                    $url,
                    [
                        'body' => json_encode($data),
                    ]
                );
            } catch (TransportExceptionInterface $e) {
                $this->logger->error($e);
            }
        }

        return !empty($response) ? $this->checkResponse($response) : false;
    }

    private function checkResponse(ResponseInterface $response)
    {
        try {
            $statusCode = $response->getStatusCode();
            if (200 === $statusCode) {
                return true;
            } else {
                $this->logger->error($statusCode.':'.$response->getInfo());
            }
        } catch (TransportExceptionInterface $e) {
            $this->logger->error($e);
        }

        return false;
    }

    private function manageSyncFail(User $user, bool $isSubscription = true)
    {
        $action = $isSubscription ? 'inscrire à' : 'désincrire de';

        $flashMessage = 'Nous ne sommes pas parvenus à vous '.$action.' notre lettre d’actualités.<br>Un message a été envoyé à l’administrateur du site afin de régler le problème';
        $this->flashBag->add('error', $flashMessage);

        $mailMessage = $this->twig->render('emails/newsletter-sync.html.twig', [
            'user' => $user,
        ]);

        $this->mailer->send(
            $user->getEmail(),
            'contact@obs-saisons.fr',
            $this->mailer->getSubjectFromTitle($mailMessage),
            $mailMessage
        );

        $user->setIsNewsletterSubscriber(!$isSubscription);
    }
}
