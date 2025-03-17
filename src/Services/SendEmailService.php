<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 02/01/2020
 * Time: 18:11
 */

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Swift_Attachment;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendEmailService
{
    private $mailer;
    private $templating;

    public function __construct(MailerInterface $mailer, Environment $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public function send($subject, $to, $template, $vars, $attachments = [])
    {
        $email = (new Email())
            ->from('noreply@mdp-data.com')
            ->to($to)
            ->subject($subject)
            ->html($this->templating->render($template, $vars));

        foreach ($attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['fileName']);
        }

        $this->mailer->send($email);
    }
}



