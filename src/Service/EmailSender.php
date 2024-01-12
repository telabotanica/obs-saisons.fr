<?php

namespace App\Service;

use Swift_Image;
use Symfony\Component\HttpKernel\KernelInterface;

class EmailSender
{
    public const CONTACT_EMAIL = 'contact@obs-saisons.fr';

    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    private $rootDir;

    public function __construct(\Swift_Mailer $mailer,KernelInterface $kernel )
    {
        $this->mailer = $mailer;
        $this->rootDir = $kernel->getProjectDir();
    }

    public function send($to, $subject, $message, $replyTo = self::CONTACT_EMAIL)
    {
        $message = ( new \Swift_Message($subject) )
            ->setFrom(self::CONTACT_EMAIL)
            ->setTo($to)
            ->setReplyTo($replyTo)
            ->setBody($message, 'text/html');

        return $this->mailer->send($message);
    }

    public function sendNewsletter($to, $subject, $message)
    {
        // Créer une nouvelle instance de Swift_Message
        $emailMessage = (new \Swift_Message($subject))
            ->setFrom(self::CONTACT_EMAIL)
            ->setTo($to);

        // Trouver et incorporer les images dans le contenu HTML
        preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/', $message, $matches);
        foreach ($matches[1] as $imageSrc) {
            $fileDirectoryPath = $this->rootDir.'/public';
            $imagePath = $fileDirectoryPath . $imageSrc;

            if (file_exists($imagePath)) {
                $image = $emailMessage->embed(Swift_Image::fromPath($imagePath));
                $message = str_replace($imageSrc, $image, $message);
            }
        }

        // Définir le message HTML modifié avec les images incorporées comme corps de l'e-mail
        $emailMessage->setBody($message, 'text/html');
        // Envoyer l'e-mail
        return $this->mailer->send($emailMessage);
    }

    public function getSubjectFromTitle($message, $default = 'Subject')
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/ims', $message, $matches) ? $matches[1] : $default;
    }
}
