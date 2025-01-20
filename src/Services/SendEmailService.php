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

class SendEmailService
{
    private $doctrine;
    private $container;

    private $mailer;
    private $templating;

    public function __construct(ContainerInterface $container, EntityManagerInterface $doctrine, \Swift_Mailer $mailer, Environment $templating)
    {
        $this->container = $container;
        $this->doctrine = $doctrine;

        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public function send($subject, $to, $template, $vars, $attachements = [])
    {
        $message = new \Swift_Message();
        $message->setSubject($subject)

            ->setTo($to)
            ->setBody($this->templating->render($template, $vars), 'text/html');

        if ($attachements) {
            foreach ($attachements as $attachement) {
                $message->attach(Swift_Attachment::fromPath($attachement['path'])->setFilename($attachement['fileName']));
            }
        }

        $this->mailer->send($message);
    }

}


