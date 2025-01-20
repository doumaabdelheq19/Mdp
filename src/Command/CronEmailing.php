<?php

namespace App\Command;

use App\Entity\ExercisingClaimRequest;
use App\Entity\Subscription;
use App\Services\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CronEmailing extends Command {
    private $container;
    private $doctrine;
    private $sendEmailService;

    public function __construct(Container $containers, EntityManagerInterface $doctrine, SendEmailService $sendEmailService)
    {
        parent::__construct();
        $this->container = $containers;
        $this->doctrine = $doctrine;
        $this->sendEmailService = $sendEmailService;
    }

    protected function configure()
    {
        // On set le nom de la commande
        $this->setName('cron:emailing');

        // On set la description
        $this->setDescription("Permet d'envoyer des rappels par mail");

        // On set l'aide
        $this->setHelp("Cette commande ne prend pas d'arguments");
    }

    public function execute(InputInterface $input, OutputInterface $output){
        $output->writeln("============ START CRON ============");

        $now = new \DateTime('now');

        $exercisingClaimRequests = $this->doctrine->getRepository(ExercisingClaimRequest::class)->findForReviving($now);

        if (count($exercisingClaimRequests)) {
            foreach ($exercisingClaimRequests as $exercisingclaim) {
                if ($exercisingclaim->getRequestDate() && !$exercisingclaim->getAnswerDate()) {
                    if ($exercisingclaim->getAccountantEmail()) {
                        if (filter_var($exercisingclaim->getAccountantEmail(), FILTER_VALIDATE_EMAIL)) {
                            $content = "<p>Bonjour,<br/>
                        <br/>
                        Pour rappel, vous avez été assigné comme responsable d'une demande d’exercice de droits<br/>
                        <br/>
                        Personne concernée: ".$exercisingclaim->getCustomer()."<br/>
                        Date de la demande: ".$exercisingclaim->getRequestDate()->format("d/m/Y")."<br/>
                        Droit: ".$exercisingclaim->getRights()."<br/>
                        Précisions sur la demande: ".nl2br($exercisingclaim->getPrecisions())."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
                            $this->sendEmailService->send(
                                "Rappel de demande d’exercice de droits",
                                $exercisingclaim->getAccountantEmail(),
                                'template_emails/left_text.html.twig',
                                [
                                    "title" => "Rappel de demande d’exercice de droits",
                                    "content" => $content
                                ]
                            );
                        }
                    }
                }
            }
        }

        $em = $this->doctrine;

        $subscriptions = $em->getRepository(Subscription::class)->findExpired($now);

        foreach ($subscriptions as $subscription) {
            if ($subscription->getType()) {
                $endDate = clone $subscription->getEndDate();

                switch ($subscription->getType()->getCode()) {
                    case "ABOPLS":
                    case "ABOSTD":
                    case "PARTEN":
                        $endDate->add(new \DateInterval("P1Y"));
                        $subscription->setEndDate($endDate);
                        $em->flush();
                        break;
                    case "ABOLIB":
                        $endDate->add(new \DateInterval("P1M"));
                        $subscription->setEndDate($endDate);
                        $em->flush();
                        break;
                }
            }
        }

        $subscriptions = $em->getRepository(Subscription::class)->findNearlyExpired($now);

        foreach ($subscriptions as $subscription) {
            $content = "<p>L'abonnement “".$subscription->getType()->getLibelle()."“ du client ".$subscription->getUser()->getCompanyName()." expire dans 30 jours. Merci de vous rapprocher des équipes techniques pour procéder au renouvellement de l'abonnement ou, en cas de résiliation, à la désactivation du compte.<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $this->sendEmailService->send(
                "Alerte fin d'abonnement",
                "contact@mydigitplace.com",
                'template_emails/left_text.html.twig',
                [
                    "title" => "Alerte fin d'abonnement",
                    "content" => $content
                ]
            );
        }

        $output->writeln("============ END CRON ============");
    }

}