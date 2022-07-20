<?php

namespace App\Service;

class EmailSender
{
    public const CONTACT_EMAIL = 'contact@obs-saisons.fr';

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
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

    public function getSubjectFromTitle($message, $default = 'Subject')
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/ims', $message, $matches) ? $matches[1] : $default;
    }
}
