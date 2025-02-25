<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Action;
use App\Entity\ActionStd;
use App\Entity\Credit;
use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\ExercisingClaimRequest;
use App\Entity\Incident;
use App\Entity\Info;
use App\Entity\Manager;
use App\Entity\Partner;
use App\Entity\Subcontractor;
use App\Entity\SubcontractorGrp;
use App\Entity\SubcontractorStd;
use App\Entity\Subuser;
use App\Entity\System;
use App\Entity\SystemStd;
use App\Entity\Training;
use App\Entity\TrainingCampain;
use App\Entity\TrainingRequest;
use App\Entity\TrainingRequestHistory;
use App\Entity\TrainingTeam;
use App\Entity\Treatment;
use App\Entity\TreatmentState;
use App\Entity\TreatmentStd;
use App\Entity\TreatmentStdCategory;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Form\ActionEditGrpType;
use App\Form\ActionGrpType;
use App\Form\ActionType;
use App\Form\ExercisingClaimRequestType;
use App\Form\IncidentType;
use App\Form\IncidentViewType;
use App\Form\SubcontractorGrpType;
use App\Form\SubcontractorStdType;
use App\Form\SubcontractorType;
use App\Form\SystemType;
use App\Form\TrainingTeamType;
use App\Form\TreatmentStdType;
use App\Form\TreatmentType;
use App\Form\UserType;
use App\Libraries\Pdf_concat;
use App\Pdf\MyPdf;
use App\Services\SendEmailService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use function Doctrine\ORM\QueryBuilder;
use App\Security\PasswordEncoder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @Route("/user", name="user_")
 */
class UserController extends AbstractController
{

    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }


    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, Security $security, EntityManagerInterface $em)
    {
        $infos = $this->getDoctrine()->getRepository(Info::class)->findBy(["enabled" => true], ["date" => "DESC"], 4);

        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

        $treatmentsStats = [
            "total" => 0,
            "inProgress" => 0,
            "toAudit" => 0,
            "valid" => 0,
        ];

        $treatmentsPiaStats = [
            "total" => 0,
            "inProgress" => 0,
            "valid" => 0,
            "reco" => 0
        ];

        foreach ($treatments as $treatment) {
            if ($treatment->getState()) {
                $treatmentsStats["total"]++;
                switch ($treatment->getState()->getId()) {
                    case 1:
                        $treatmentsStats["inProgress"]++;
                        break;
                    case 2:
                        $treatmentsStats["toAudit"]++;
                        break;
                    case 3:
                        $treatmentsStats["valid"]++;
                        break;
                }
            }
            if ($treatment->getPiaFile()) {
                $treatmentsPiaStats["total"]++;
                $treatmentsPiaStats["valid"]++;
            } else {
                if ($treatment->isPiaNeeded() && !$treatment->isPiaExoneration()) {
                    $treatmentsPiaStats["total"]++;
                    if ($treatment->getPiaFile()) {
                        $treatmentsPiaStats["valid"]++;
                    } else {
                        $treatmentsPiaStats["inProgress"]++;
                    }
                } else {
                    if (!$treatment->isPiaExoneration()) {
                        if ($treatment->isPiaNeeded() || $treatment->isSensitiveData() || count($treatment->getPiaCriteria())) {
                            if (!$treatment->isInsufficientCriteria()) {
                                $treatmentsPiaStats["total"]++;
                                $treatmentsPiaStats["reco"]++;
                            }
                        }
                    }
                }
            }
        }

        $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser()], ["name" => "ASC"]);
        $subcontractors_grp = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());

        $subcontractorsStats = [
            "total" => 0,
            "inProgress" => 0,
            "invalid" => 0,
            "valid" => 0,
        ];

        foreach ($subcontractors as $subcontractor) {
            if ($subcontractor->getConformity()) {
                $subcontractorsStats["total"]++;
                switch ($subcontractor->getConformity()->getId()) {
                    case 1:
                        $subcontractorsStats["invalid"]++;
                        break;
                    case 2:
                        $subcontractorsStats["inProgress"]++;
                        break;
                    case 3:
                        $subcontractorsStats["valid"]++;
                        break;
                }
            }
        }

        foreach ($subcontractors_grp as $subcontractor) {
            if ($subcontractor->getConformity()) {
                $subcontractorsStats["total"]++;
                switch ($subcontractor->getConformity()->getId()) {
                    case 1:
                        $subcontractorsStats["invalid"]++;
                        break;
                    case 2:
                        $subcontractorsStats["inProgress"]++;
                        break;
                    case 3:
                        $subcontractorsStats["valid"]++;
                        break;
                }
            }
        }

        $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());

        $actionsStats = [
            "total" => 0,
            "invalid" => 0,
            "valid" => 0,
        ];

        foreach ($actions as $action) {
            $actionsStats["total"]++;
            if ($action->isTerminated()) {
                $actionsStats["valid"]++;
            } else {
                $actionsStats["invalid"]++;
            }
        }

        $actionsToTreat = $this->getDoctrine()->getRepository(Action::class)->findToTreat($this->getUser()->getUser());
        $incidentsToTreat = $this->getDoctrine()->getRepository(Incident::class)->findBy(["user" => $this->getUser()->getUser(), "cnilInformed" => false, "peopleInformed" => false]);
        $exercisingClaimRequestsToTreat = $this->getDoctrine()->getRepository(ExercisingClaimRequest::class)->findBy(["user" => $this->getUser()->getUser(), "answerDate" => null]);

        $trainingsTotal = $this->getDoctrine()->getRepository(TrainingRequest::class)->countTotalForUser($this->getUser()->getUser());
        $trainingsSensibilized = $this->getDoctrine()->getRepository(TrainingCampain::class)->countTotalSensibilizedForUser($this->getUser()->getUser());
        $trainingsAvgNote = $this->getDoctrine()->getRepository(TrainingRequestHistory::class)->avgNoteForUser($this->getUser()->getUser());
        $trainingsAnswered = $this->getDoctrine()->getRepository(TrainingRequest::class)->countAnsweredForUser($this->getUser()->getUser());

        $printForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('user_dashboard_print'))
            ->setMethod('GET')
            ->add("types", ChoiceType::class, [
                "choices" => [
                    "Graphiques" => "graphics",
                    "Actions à réaliser" => "actions",
                    "Incidents à traiter" => "incidents",
                    "Demandes d'exercices de droits à traiter" => "exercisingclaims",
                ],
                "expanded" => true,
                "multiple" => true,
                "label" => "Choix des exports",
                "data" => ["graphics", "actions", "incidents", "exercisingclaims"]
            ])
            ->getForm();

        $formChildrenUsers = null;
        $originalUser = $this->getUser()->getUser();
        $originalUserId = 0;
        $originalUserSelectedChoice = null;

        $token = $security->getToken();

        if ($token instanceof SwitchUserToken) {
            $impersonatorUser = $token->getOriginalToken()->getUser();
            if ($impersonatorUser) {
                if ($impersonatorUser->getUser()) {
                    $originalUserId = $originalUser->getId();
                    $originalUser = $em->getRepository(User::class)->find($impersonatorUser->getUser()->getId());
                }
            }
        }

        if (count($originalUser->getChildrenUsers())) {
            $mainChoice = (object) [
                "value" => 0,
                "label" => ($request->getLocale() == "fr")?"Entité principale":"Main entity"
            ];
            $choices[] = $mainChoice;
            $originalUserSelectedChoice = $mainChoice;
            foreach ($originalUser->getChildrenUsers() as $childUser) {
                $childChoice = (object) [
                    "value" => $childUser->getId(),
                    "label" => $childUser->getCompanyName(),
                ];
                $choices[] = $childChoice;

                if ($childUser->getId() == $originalUserId) {
                    $originalUserSelectedChoice = $choices[count($choices)-1];
                }
            }
            //var_dump($choices);
            $formChildrenUsers = $this->createFormBuilder()
                ->add("comptes", ChoiceType::class, [
                    "choices" => $choices,
                    "required" => true,
                    "choice_label" => function ($entry) {
                        if (is_object($entry)) {
                            return $entry->label;
                        }
                        return "0";
                    },
                    "choice_value" => function ($entry) {
                        if (is_object($entry)) {
                            return $entry->value;
                        }
                        return "0";
                    },
                    "data" => $originalUserSelectedChoice,
                ])
                ->getForm();

            $formChildrenUsers->handleRequest($request);

            if ($formChildrenUsers->isSubmitted()) {

            }
        } else {

        }

        return $this->render('user/index.html.twig', [
            "infos" => $infos,
            "treatmentsStats" => $treatmentsStats,
            "treatmentsPiaStats" => $treatmentsPiaStats,
            "subcontractorsStats" => $subcontractorsStats,
            "actionsStats" => $actionsStats,
            "actionsToTreat" => $actionsToTreat,
            "incidentsToTreat" => $incidentsToTreat,
            "exercisingClaimRequestsToTreat" => $exercisingClaimRequestsToTreat,
            "trainingsTotal" => $trainingsTotal,
            "trainingsSensibilized" => $trainingsSensibilized,
            "trainingsAvgNote" => $trainingsAvgNote,
            "trainingsAnswered" => $trainingsAnswered,
            "printForm" => $printForm->createView(),
            "formChildrenUsers" => $formChildrenUsers ? $formChildrenUsers->createView() : null,
        ]);
    }

    /**
     * @Route("/dashboardprint", name="dashboard_print")
     */
    public function dashboardPrintAction(Request $request)
    {
        $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetAuthor('myDigitplace');
        $pdf->SetTitle("Tableau de bord");
        $pdf->SetMargins(10,22,10, true);
        $pdf->SetAutoPageBreak(TRUE, 35);
        $pdf->AddPage('L', 'A4');

        $html = $this->renderView('user/pdf/dashboard_top.html.twig', []);
        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

        if (in_array("graphics", $request->get("form")["types"])) {
            $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            $treatmentsStats = [
                "total" => 0,
                "inProgress" => 0,
                "toAudit" => 0,
                "valid" => 0,
            ];

            $treatmentsPiaStats = [
                "total" => 0,
                "inProgress" => 0,
                "valid" => 0,
                "reco" => 0
            ];

            foreach ($treatments as $treatment) {
                if ($treatment->getState()) {
                    $treatmentsStats["total"]++;
                    switch ($treatment->getState()->getId()) {
                        case 1:
                            $treatmentsStats["inProgress"]++;
                            break;
                        case 2:
                            $treatmentsStats["toAudit"]++;
                            break;
                        case 3:
                            $treatmentsStats["valid"]++;
                            break;
                    }
                }
                if ($treatment->getPiaFile()) {
                    $treatmentsPiaStats["total"]++;
                    $treatmentsPiaStats["valid"]++;
                } else {
                    if ($treatment->isPiaNeeded() && !$treatment->isPiaExoneration()) {
                        $treatmentsPiaStats["total"]++;
                        if ($treatment->getPiaFile()) {
                            $treatmentsPiaStats["valid"]++;
                        } else {
                            $treatmentsPiaStats["inProgress"]++;
                        }
                    } else {
                        if (!$treatment->isPiaExoneration()) {
                            if (!$treatment->isPiaNeeded() && $treatment->isSensitiveData()) {
                                $treatmentsPiaStats["total"]++;
                                $treatmentsPiaStats["reco"]++;
                            }
                        }
                    }
                }
            }

            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser()], ["name" => "ASC"]);

            $subcontractorsStats = [
                "total" => 0,
                "inProgress" => 0,
                "invalid" => 0,
                "valid" => 0,
            ];

            foreach ($subcontractors as $subcontractor) {
                if ($subcontractor->getConformity()) {
                    $subcontractorsStats["total"]++;
                    switch ($subcontractor->getConformity()->getId()) {
                        case 1:
                            $subcontractorsStats["invalid"]++;
                            break;
                        case 2:
                            $subcontractorsStats["inProgress"]++;
                            break;
                        case 3:
                            $subcontractorsStats["valid"]++;
                            break;
                    }
                }
            }

            $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());

            $actionsStats = [
                "total" => 0,
                "invalid" => 0,
                "valid" => 0,
            ];

            foreach ($actions as $action) {
                $actionsStats["total"]++;
                if ($action->isTerminated()) {
                    $actionsStats["valid"]++;
                } else {
                    $actionsStats["invalid"]++;
                }
            }

            $html = $this->renderView('user/pdf/dashboard_graphics.html.twig', [
                "treatmentsStats" => $treatmentsStats,
                "treatmentsPiaStats" => $treatmentsPiaStats,
                "subcontractorsStats" => $subcontractorsStats,
                "actionsStats" => $actionsStats,
            ]);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        }

        if (in_array("actions", $request->get("form")["types"])) {
            $html = $this->renderView('user/pdf/dashboard_actions.html.twig', [
                "actions" => $this->getDoctrine()->getRepository(Action::class)->findToTreat($this->getUser()->getUser())
            ]);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        }

        if (in_array("incidents", $request->get("form")["types"])) {
            $html = $this->renderView('user/pdf/dashboard_incidents.html.twig', [
                "incidents" => $this->getDoctrine()->getRepository(Incident::class)->findBy(["user" => $this->getUser()->getUser(), "cnilInformed" => false, "peopleInformed" => false])
            ]);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        }

        if (in_array("exercisingclaims", $request->get("form")["types"])) {
            $html = $this->renderView('user/pdf/dashboard_exercisingclaims.html.twig', [
                "exercisingclaims" => $this->getDoctrine()->getRepository(ExercisingClaimRequest::class)->findBy(["user" => $this->getUser()->getUser(), "answerDate" => null])
            ]);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        }

        $filename = 'Tableau_de_bord';


        return $pdf->Output($filename.".pdf",'I');
    }

    /**
     * @Route("/legales", name="legales")
     */
    public function legalesAction(Request $request)
    {
        return $this->render('user/legales.html.twig', [
        ]);
    }

    /**
     * @Route("/privacy", name="privacy")
     */
    public function privacyAction(Request $request)
    {
        return $this->render('user/privacy.html.twig', [
        ]);
    }

    /**
     * @Route("/account", name="account")
     */
    public function accountAction(Request $request, SendEmailService $sendEmailService)
    {
        $em = $this->getDoctrine()->getManager();

        $account = $this->getUser();

        $r_email = $account->getEmail();

        $form = $this->createForm(UserType::class, $account->getUser())
            ->add('save', SubmitType::class, [
                'label' => 'enregistrer',
                'translation_domain' => 'messages',
            ]);

        $form['email']->setData($account->getEmail());

        $form2 = $this->createFormBuilder()
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'les_mots_de_passe_ne_sont_pas_identiques',
                'first_options'  => array(
                    'attr' => array(
                        'placeholder' => 'changer_mot_de_passe'
                    ),
                    'constraints' =>[
                        new Assert\NotBlank([
                            'message' => 'merci_de_saisir_un_mot_de_passe'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^(?:(?=(\S*?[A-Z]){1})(?=(\S*?[a-z]){1})(?=(\S*?[0-9]){1})(?=\S*?[~!^(){}<>%@#&*+=_\-$`,.\/\\\;:\'"|\[\]]){1}.{12,})$/m',
                            'message' => "votre_mot_de_passe_doit_respecter_les_recommandations_de_l_anssi"
                        ])
                    ],
                    'label' => "nouveau_mot_de_passe"
                ),
                'second_options' => array(
                    'attr' => array(
                        'placeholder' => 'confirmation_mot_de_passe'
                    ),
                    'label' => "confirmez_le_mot_de_passe"
                ),
                'mapped' => false,
                'translation_domain' => 'messages',
            ))
            ->add('save', SubmitType::class, [
                'label' => 'enregistrer',
                'translation_domain' => 'messages',
            ])
            ->getForm();

        $hasFormSubusers = false;

        if ($this->isGranted("ROLE_USER")) {
            $hasFormSubusers = true;

            $subusers = $em->getRepository(Subuser::class)->findBy(['user' => $this->getUser()->getUser()]);

            $maxUsers = count($subusers);
            if ($maxUsers < 3) {
                $maxUsers++;
            }

            $form3 = $this->createFormBuilder();
            for ($i = 0; $i < $maxUsers; $i++) {
                $form3->add('email_'.$i, EmailType::class, [
                    'attr' => [
                        'placeholder' => 'email_identifiant_de_connexion'
                    ],
                    'label' => (($request->getLocale()=="fr")?'Utilisateur ':'User ').($i+1),
                    'data' => isset($subusers[$i]) ? $subusers[$i]->getAccount()->getEmail() : null,
                    'required' => false,
                    'mapped' => false
                ]);
            }

            $form3 = $form3
                ->add('save', SubmitType::class, [
                    'label' => 'enregistrer',
                    'translation_domain' => 'messages',
                ])->getForm();
        }

        $form->handleRequest($request);
        $form2->handleRequest($request);

        if ($hasFormSubusers) {
            $form3->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $account->getUser()->setPicture($fileName);
            }

            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Profil mis à jour');

            if ($r_email != $form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account && $other_account->getId() != $account->getId()) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $account->setEmail($form['email']->getData());
                        $account->getUser()->setEmail($form['email']->getData());

                        $em->flush();

                        $oldToken = $this->get('security.token_storage')->getToken();

                        $token = new UsernamePasswordToken(
                            $account, //user object with updated username
                            $oldToken->getFirewallName());
                        $this->get('security.token_storage')->setToken($token);

                        $this->get('session')->getFlashBag()->add('success', 'Identifiant de connexion modifié');
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail n\'est pas valide');
                }
            }

            return $this->redirectToRoute('user_account');
        }
        if ($form2->isSubmitted() && $form2->isValid()) {
            $salt = md5(uniqid());
            $pwd = $form2['password']->getData();
            $account->setSalt($salt);
            $PasswordEncoder = new PasswordEncoder;
            $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
            $account->setPassword($enc_pwd);

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Mot de passe mis à jour');

            return $this->redirectToRoute('user_account');
        }
        if ($hasFormSubusers) {
            if ($form3->isSubmitted() && $form3->isValid()) {
                $now = new \DateTime("now");
                $error = false;
                for ($key = 0; $key < $maxUsers; $key++) {
                    $email = $form3['email_'.$key]->getData();
                    if (isset($subusers[$key])) {
                        if ($subusers[$key]->getAccount()->getEmail() != $email) {
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $email]);
                                if ($other_account) {
                                    $this->get('session')->getFlashBag()->add('danger', 'L\'adresse mail '.$email.' est déjà utilisée par un autre utilisateur');
                                    $error = true;
                                } else {
                                    $newAccount = $em->getRepository(Account::class)->find($subusers[$key]->getAccount()->getId());
                                    if ($newAccount) {
                                        $newAccount->setEmail($email);
                                        $newAccount->setEnabled(true);
                                        $newAccount->setRegistrationDate($now);
                                        $newAccount->setRoles(["ROLE_SUBUSER"]);

                                        $salt = md5(uniqid());
                                        $pwd = "";
                                        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                                        $charactersLength = strlen($characters);
                                        for ($i = 0; $i < 12; $i++) {
                                            $pwd .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        $newAccount->setSalt($salt);
                                        $PasswordEncoder = new PasswordEncoder;
                                        $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
                                        $newAccount->setPassword($enc_pwd);

                                        $token = hash("sha256", uniqid());
                                        $newAccount->setPasswordRequest($token);
                                        $newAccount->setPasswordRequestDate(new \DateTime('now'));

                                        $em->flush();

                                        $content = "<p>Bonjour,<br/>
                                        <br/>
                                        Un accès vous a été créé sur le site myDigitplace et nous vous invitons à définir votre mot de passe.<br/>
                                        <br/>
                                        <br/>
                                        Voici votre lien pour définir un nouveau mot de passe:<br/><a href='".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                                        ".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."
                                        </a>
                                        <br/>
                                        <br/>
                                        Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                                        <br/>
                                        Bien cordialement,<br/>
                                        <br/>
                                        L’équipe myDigitplace<br/>
                                        <br/>
                                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                                        </p>";
                                        $sendEmailService->send(
                                            "Votre accès myDigiplace",
                                            $newAccount->getEmail(),
                                            'template_emails/left_text.html.twig',
                                            [
                                                "title" => "Votre accès myDigiplace",
                                                "content" => $content
                                            ]
                                        );
                                    }
                                }
                            } else {
                                $this->get('session')->getFlashBag()->add('danger', 'L\'adresse mail '.$email.' n\'est pas valide');
                            }
                        }
                    } else {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $email]);
                            if ($other_account) {
                                $this->get('session')->getFlashBag()->add('danger', 'L\'adresse mail '.$email.' est déjà utilisée par un autre utilisateur');
                                $error = true;
                            } else {
                                $newSubuser = new Subuser();
                                $newSubuser->setUser($this->getUser()->getUser());

                                $em->persist($newSubuser);

                                $newAccount = new Account();
                                $newAccount->setEmail($email);
                                $newAccount->setEnabled(true);
                                $newAccount->setSubuser($newSubuser);
                                $newAccount->setRegistrationDate($now);
                                $newAccount->setRoles(["ROLE_SUBUSER"]);

                                $salt = md5(uniqid());
                                $pwd = "";
                                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                                $charactersLength = strlen($characters);
                                for ($i = 0; $i < 12; $i++) {
                                    $pwd .= $characters[rand(0, $charactersLength - 1)];
                                }
                                $newAccount->setSalt($salt);
                                $PasswordEncoder = new PasswordEncoder;
                                $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
                                $newAccount->setPassword($enc_pwd);

                                $token = hash("sha256", uniqid());
                                $newAccount->setPasswordRequest($token);
                                $newAccount->setPasswordRequestDate(new \DateTime('now'));

                                $em->persist($newAccount);

                                $em->flush();

                                $content = "<p>Bonjour,<br/>
                                <br/>
                                Un accès vous a été créé sur le site myDigitplace et nous vous invitons à définir votre mot de passe.<br/>
                                <br/>
                                <br/>
                                Voici votre lien pour définir un nouveau mot de passe:<br/><a href='".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                                ".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."
                                </a>
                                <br/>
                                <br/>
                                Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                                <br/>
                                Bien cordialement,<br/>
                                <br/>
                                L’équipe myDigitplace<br/>
                                <br/>
                                <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                                </p>";
                                $sendEmailService->send(
                                    "Votre accès myDigiplace",
                                    $account->getEmail(),
                                    'template_emails/left_text.html.twig',
                                    [
                                        "title" => "Votre accès myDigiplace",
                                        "content" => $content
                                    ]
                                );
                            }
                        } else {
                            $this->get('session')->getFlashBag()->add('danger', 'L\'adresse mail '.$email.' n\'est pas valide');
                        }
                    }
                }

                if ($error) {
                    $this->get('session')->getFlashBag()->add('danger', 'Impossible de mettre à jour les utilisateurs. Veuillez vérifier les données saisies');
                } else {
                    $this->get('session')->getFlashBag()->add('success', 'Utilisateurs mis à jour');
                }

                return $this->redirectToRoute('user_account');
            }
        }

        $exportForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('user_account_export'))
            ->setMethod('GET')
            ->add("types", ChoiceType::class, [
                "choices" => [
                    "e_traitements" => "treatments",
                    "e_sous_traitance" => "subcontracting",
                    "e_Cartographie_du_si" => "systems",
                    "e_demandes_d_exercices_de_droits" => "exercisingclaims",
                    "e_incidents" => "incidents",
                    "e_plan_d_actions" => "actions",
                    "e_formations_des_equipes" => "campains",
                    "e_informations_utilisateur" => "user",
                ],
                "expanded" => true,
                "multiple" => true,
                "label" => "choix_des_exports",
                "data" => [],
                'translation_domain' => 'messages',
            ])
            ->getForm();

        return $this->render('user/account.html.twig', [
            'form' => $form->createView(),
            'form2' => $form2->createView(),
            'form3' => $hasFormSubusers ? $form3->createView() : null,
            'hasFormSubusers' => $hasFormSubusers,
            'maxUsers' => $hasFormSubusers ? $maxUsers : 0,
            'exportForm' => $exportForm->createView()
        ]);
    }

    /**
     * @Route("/account/print", name="account_print")
     */
    public function accountPrintAction(Request $request)
    {
        $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setUser($this->getUser()->getUser());
        $pdf->SetAuthor('myDigitplace');
        $pdf->SetTitle("Fiche société");
        $pdf->SetMargins(10,22,10, true);
        $pdf->SetAutoPageBreak(TRUE, 35);
        $pdf->AddPage('P', 'A4');

        $html = $this->renderView('manager/pdf/user.html.twig', [
            "user" => $this->getUser()->getUser()
        ]);

        $filename = 'fiche_societe';

        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        return $pdf->Output($filename.".pdf",'I');
    }

    /**
     * @Route("/account/export", name="account_export")
     */
    public function accountExportAction(Request $request, SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $data = [];

        //treatments
        if (in_array("treatments", $request->get("form")["types"])) {
            $entities = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "name",
                        "number",
                        "creationDate",
                        "editDate",
                        "mainPurpose",
                        "purpose1",
                        "purpose2",
                        "purpose3",
                        "purpose4",
                        "purpose5",
                        "othersPurpose",
                        "description",
                        "personalData",
                        "peopleData",
                        "transferOutsideUeCountries",
                        "sensitiveData",
                        "consentAsked",
                        "consentHow",
                        "piaCriteria",
                        "piaNeeded",
                        "piaFile",
                        "piaExoneration",
                        "legalBasis",
                        "dataSource",
                        "automatedDecision",
                        "insufficientCriteria",
                        "dataRetentionPeriod",
                        "treatmentAccountant",
                        "dpo",
                        "serviceAccountant",
                        "editor",
                        "group",
                        "subcontractors" => [
                            "id",
                            "name",
                        ],
                        "systems" => [
                            "id",
                            "name",
                        ],
                        "actions" => [
                            "id",
                            "name",
                        ],
                        "state" => [
                            "id",
                            "libelle",
                        ],
                    ]]
            );

            $data["treatments"] = json_decode($json);

            if (count($this->getUser()->getUser()->getGroupTreatments())) {
                $json = $serializer->serialize(
                    $this->getUser()->getUser()->getGroupTreatments(),
                    JsonEncoder::FORMAT,
                    [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                        AbstractNormalizer::ATTRIBUTES => [
                            "id",
                            "name",
                            "number",
                            "creationDate",
                            "editDate",
                            "mainPurpose",
                            "purpose1",
                            "purpose2",
                            "purpose3",
                            "purpose4",
                            "purpose5",
                            "othersPurpose",
                            "description",
                            "personalData",
                            "peopleData",
                            "transferOutsideUeCountries",
                            "sensitiveData",
                            "consentAsked",
                            "consentHow",
                            "piaCriteria",
                            "piaNeeded",
                            "piaFile",
                            "piaExoneration",
                            "legalBasis",
                            "dataSource",
                            "automatedDecision",
                            "insufficientCriteria",
                            "dataRetentionPeriod",
                            "treatmentAccountant",
                            "dpo",
                            "serviceAccountant",
                            "editor",
                            "group",
                            "subcontractors" => [
                                "id",
                                "name",
                            ],
                            "systems" => [
                                "id",
                                "name",
                            ],
                            "actions" => [
                                "id",
                                "name",
                            ],
                            "state" => [
                                "id",
                                "libelle",
                            ],
                        ]]
                );

                $data["treatmentsGroup"] = json_decode($json);
            }
        }

        //subcontracting
        if (in_array("subcontracting", $request->get("form")["types"])) {
            $entities = $em->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser(), "group" => false], ["name" => "ASC"]);

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "name",
                        "type",
                        "contactFirstName",
                        "contactLastName",
                        "contactPhone",
                        "contactEmail",
                        "privacyPolicyLink",
                        "date",
                        "editDate",
                        "group",
                        "conformity" => [
                            "id",
                            "libelle"
                        ],
                        "subcontractorType" => [
                            "id",
                            "libelle"
                        ],
                        "documents" => [
                            "id",
                            "name"
                        ],
                        "treatments" => [
                            "id",
                            "name"
                        ],
                    ]]
            );

            $data["subcontracting"] = json_decode($json);

            $entities = $em->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "name",
                        "type",
                        "contactFirstName",
                        "contactLastName",
                        "contactPhone",
                        "contactEmail",
                        "privacyPolicyLink",
                        "date",
                        "editDate",
                        "group",
                        "conformity" => [
                            "id",
                            "libelle"
                        ],
                        "subcontractorType" => [
                            "id",
                            "libelle"
                        ],
                        "documents" => [
                            "id",
                            "name"
                        ],
                        "treatments" => [
                            "id",
                            "name"
                        ],
                    ]]
            );

            $data["subcontractingGroup"] = json_decode($json);
        }

        //systems
        if (in_array("systems", $request->get("form")["types"])) {
            if ($this->getUser()->getUser()->getParentUser()) {
                $entities = $em->getRepository(System::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $entities = $em->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser()]);
            }

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "name",
                        "data",
                        "type",
                        "subtype",
                        "group",
                        "treatments" => [
                            "id",
                            "name",
                        ],
                    ]]
            );

            $data["systems"] = json_decode($json);

            if ($this->getUser()->getUser()->isMainGroupAgency()) {
                $entities = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser(), "group" => true]);

                $json = $serializer->serialize(
                    $entities,
                    JsonEncoder::FORMAT,
                    [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                        AbstractNormalizer::ATTRIBUTES => [
                            "id",
                            "name",
                            "data",
                            "type",
                            "subtype",
                            "group",
                            "treatments" => [
                                "id",
                                "name",
                            ],
                        ]]
                );

                $data["systemsGroup"] = json_decode($json);
            }
        }

        //exercisingclaims
        if (in_array("exercisingclaims", $request->get("form")["types"])) {
            $entities = $em->getRepository(ExercisingClaimRequest::class)->findBy(["user" => $this->getUser()->getUser()]);

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "requestDate",
                        "rights",
                        "customer",
                        "answerDate",
                        "accountantName",
                        "accountantEmail",
                        "precisions",
                        "file",
                    ]]
            );

            $data["exercisingclaims"] = json_decode($json);
        }

        //incidents
        if (in_array("incidents", $request->get("form")["types"])) {
            if ($this->getUser()->getUser()->getParentUser()) {
                $entities = $em->getRepository(Incident::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $entities = $em->getRepository(Incident::class)->findBy(["user" => $this->getUser()->getUser()]);
            }

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "cnilInformed",
                        "notice72H",
                        "type",
                        "peopleNumber",
                        "fileType",
                        "consequences",
                        "takenMeasures",
                        "peopleInformed",
                        "date",
                        "creationDate",
                        "editDate",
                        "group",
                        "file",
                    ]]
            );

            $data["incidents"] = json_decode($json);
        }

        //actions
        if (in_array("actions", $request->get("form")["types"])) {
            $entities = $em->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "name",
                        "budget",
                        "accountantLastName",
                        "accountantFirstName",
                        "accountantEmail",
                        "accountantPhone",
                        "goal",
                        "information",
                        "usefulLink",
                        "date",
                        "editDate",
                        "setUpDate",
                        "terminated",
                        "forDpo",
                        "treatments" => [
                            "id",
                            "name",
                        ],
                        "documents" => [
                            "id",
                            "name",
                        ],
                        "sheets" => [
                            "id",
                            "name",
                        ],
                    ]]
            );

            $data["actions"] = json_decode($json);
        }

        //campains
        if (in_array("campains", $request->get("form")["types"])) {
            $entities = $this->getDoctrine()->getRepository(TrainingCampain::class)->findBy(["user" => $this->getUser()->getUser()], ["creationDate" => "DESC"]);

            $json = $serializer->serialize(
                $entities,
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "creationDate",
                        "title",
                        "emails",
                        "emailsCount",
                        "traineeship",
                        "traineeshipDate",
                        "former",
                    ]]
            );

            $data["campains"] = json_decode($json);

            foreach ($data["campains"] as $key => $value) {
                $trainingRequests = $this->getDoctrine()->getRepository(TrainingRequest::class)->findBy(["trainingCampain" => $value->id]);

                $json = $serializer->serialize(
                    $trainingRequests,
                    JsonEncoder::FORMAT,
                    [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                        AbstractNormalizer::ATTRIBUTES => [
                            "id",
                            "email",
                            "answerDate",
                            "result",
                            "firstName",
                            "lastName",
                            "position",
                        ]]
                );

                $data["campains"][$key]->requests = json_decode($json);
            }
        }

        //user
        if (in_array("user", $request->get("form")["types"])) {
            $json = $serializer->serialize(
                $this->getUser()->getUser(),
                JsonEncoder::FORMAT,
                [AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => false,
                    AbstractNormalizer::ATTRIBUTES => [
                        "id",
                        "companyName",
                        "siret",
                        "address",
                        "address2",
                        "zipCode",
                        "city",
                        "companyPhone",
                        "phone",
                        "email",
                        "contactFirstName",
                        "contactLastName",
                        "contactEmail",
                        "contactPhone",
                        "contactJob",
                        "accountantFirstName",
                        "accountantLastName",
                        "accountantEmail",
                        "accountantPhone",
                        "accountantJob",
                        "managerDpo",
                        "employeesNumber",
                        "language",
                    ]]
            );

            $data["user"] = json_decode($json);
        }

        $response = new JsonResponse($data);

        $response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );

        return $response;
    }

    /**
     * @Route("/intervention", name="intervention")
     */
    public function interventionAction(Request $request, SendEmailService $sendEmailService)
    {
        $form = $this->createFormBuilder()
            ->add('firstName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Prénom*'
                ],
                'label' => 'Prénom*',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom*'
                ],
                'label' => 'Nom*',
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => [
                    'placeholder' => 'Téléphone*'
                ],
                'label' => 'Téléphone*',
                'required' => true,
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'placeholder' => 'Email*'
                ],
                'label' => 'Email*',
                'required' => true,
            ])
            ->add('object', TextType::class, [
                'attr' => [
                    'placeholder' => 'Objet de l’intervention*'
                ],
                'label' => 'Objet de l’intervention*',
                'required' => true,
            ])
            ->add('precision', TextType::class, [
                'attr' => [
                    'placeholder' => 'Précisions'
                ],
                'label' => 'Précisions',
                'required' => false,
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = "<p>Bonjour,<br/>
                        <br/>
                        Un nouvelle demande de devis d'intervention a été envoyée.<br/>
                        <br/>
                        <br/>
                        Nom: ".$form['lastName']->getData()."<br/><br/>
                        Prénom: ".$form['firstName']->getData()."<br/><br/>
                        Email: ".$form['email']->getData()."<br/><br/>
                        Téléphone: ".$form['phone']->getData()."<br/><br/>
                        Objet de l’intervention: ".$form['object']->getData()."<br/><br/>
                        Précisions: ".$form['precision']->getData()."<br/><br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Demande de devis d'intervention",
                "devis@mydigitplace.com",
                'template_emails/left_text.html.twig',
                [
                    "title" => "Demande de devis d'intervention",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Demande de devis d\'intervention envoyée');
            return $this->redirectToRoute("user_intervention");
        }

        return $this->render('user/intervention.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/partners", name="partners")
     */
    public function partnersAction(Request $request)
    {
        $partners = $this->getDoctrine()->getRepository(Partner::class)->findBy([], ["name" => "ASC"]);

        return $this->render('user/partners.html.twig', [
            "partners" => $partners
        ]);
    }

    /**
     * @Route("/partners/{id}/get", name="partners_get")
     */
    public function partnersGetAction(Request $request, Partner $partner, SendEmailService $sendEmailService)
    {
        $content = "<p>Bonjour,<br/>
                        <br/>
                        Un nouvelle demande d'accès à une remise partenaire a été effectuée.<br/>
                        <br/>
                        <br/>
                        Client: ".$this->getUser()->getUser()->getCompanyName()."<br/><br/>
                        Partenaire: ".$partner->getName()."<br/><br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
        $sendEmailService->send(
            "Demande d'accès à une remise partenaire",
            "partenaires@mydigitplace.com",
            'template_emails/left_text.html.twig',
            [
                "title" => "Demande d'accès à une remise partenaire",
                "content" => $content
            ]
        );

        $this->get('session')->getFlashBag()->add('success', 'Demande d\'accès à la remise envoyée');
        return $this->redirectToRoute("user_partners");
    }

    /**
     * @Route("/treatments", name="treatments")
     */
    public function treatmentsAction(Request $request)
    {
        $hasFilters = false;
        $filter1 = null;
        $filter2 = null;

        if ($request->get("filter")) {
            $filters = explode(",", $request->get("filter"));
            if (count($filters) == 2) {
                $filter1 = $filters[0];
                $filter2 = $filters[1];
                $hasFilters = true;
            }
        }

        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

        $treatmentsStats = [
            "total" => 0,
            "inProgress" => 0,
            "toAudit" => 0,
            "valid" => 0,
        ];

        $treatmentsPiaStats = [
            "total" => 0,
            "inProgress" => 0,
            "valid" => 0,
            "reco" => 0,
        ];

        //$treatments = new ArrayCollection(array_merge($treatments, $this->getUser()->getUser()->getGroupTreatments()->toArray()));

        $filteredTreatments = [];

        foreach ($treatments as $treatment) {
            $isTreatmentInProgress = false;
            $isTreatmentToAudit = false;
            $isTreatmentValid = false;
            $isPiaInProgress = false;
            $isPiaValid = false;
            $isPiaReco = false;

            if ($treatment->getState()) {
                $treatmentsStats["total"]++;
                switch ($treatment->getState()->getId()) {
                    case 1:
                        $treatmentsStats["inProgress"]++;
                        $isTreatmentInProgress = true;
                        break;
                    case 2:
                        $treatmentsStats["toAudit"]++;
                        $isTreatmentToAudit = true;
                        break;
                    case 3:
                        $treatmentsStats["valid"]++;
                        $isTreatmentValid = true;
                        break;
                }
            }
            if ($treatment->getPiaFile()) {
                $treatmentsPiaStats["total"]++;
                $treatmentsPiaStats["valid"]++;
                $isPiaValid = true;
            } else {
                if ($treatment->isPiaNeeded() && !$treatment->isPiaExoneration()) {
                    $treatmentsPiaStats["total"]++;
                    if ($treatment->getPiaFile()) {
                        $treatmentsPiaStats["valid"]++;
                        $isPiaValid = true;
                    } else {
                        $treatmentsPiaStats["inProgress"]++;
                        $isPiaInProgress = true;
                    }
                } else {
                    if (!$treatment->isPiaExoneration()) {
                        if ($treatment->isPiaNeeded() || $treatment->isSensitiveData() || count($treatment->getPiaCriteria())) {
                            if (!$treatment->isInsufficientCriteria()) {
                                $treatmentsPiaStats["total"]++;
                                $treatmentsPiaStats["reco"]++;
                                $isPiaReco = true;
                            }
                        }
                    }
                }
            }

            if ($hasFilters) {
                if ($filter1 == "status") {
                    if ($filter2 == "inProgress" && $isTreatmentInProgress) {
                        $filteredTreatments[] = $treatment;
                    } elseif ($filter2 == "toAudit" && $isTreatmentToAudit) {
                        $filteredTreatments[] = $treatment;
                    } elseif ($filter2 == "valid" && $isTreatmentValid) {
                        $filteredTreatments[] = $treatment;
                    }
                } elseif ($filter1 == "pia") {
                    if ($filter2 == "inProgress" && $isPiaInProgress) {
                        $filteredTreatments[] = $treatment;
                    } elseif ($filter2 == "reco" && $isPiaReco) {
                        $filteredTreatments[] = $treatment;
                    } elseif ($filter2 == "valid" && $isPiaValid) {
                        $filteredTreatments[] = $treatment;
                    }
                }
            } else {
                $filteredTreatments[] = $treatment;
            }
        }

        return $this->render('user/treatments.html.twig', [
            "treatments" => $filteredTreatments,
            "treatmentsStats" => $treatmentsStats,
            "treatmentsPiaStats" => $treatmentsPiaStats,
            "filter1" => $filter1,
            "filter2" => $filter2,
        ]);
    }

    /**
     * @Route("/treatments/standardize/{id}", name="treatments_standardize")
     */
    public function treatmentsStandardizeAction(Request $request, Security $security, Treatment $treatment)
    {
        $form = $this->createFormBuilder()
            ->add('category', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Catégorie',
                ),
                'label' => 'Catégorie',
                'placeholder' => 'Catégorie',
                'required' => true,
                'class' => TreatmentStdCategory::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    return $qb
                        ->addSelect('(CASE WHEN c.id = 17 THEN 1 ELSE 0 END) AS HIDDEN ordCol')
                        ->addOrderBy('ordCol', 'ASC')
                        ->addOrderBy('c.libelle', 'ASC');
                },
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $treatmentStd = new TreatmentStd();

            $treatmentStd->setName($treatment->getName());
            $treatmentStd->setCreationDate($treatment->getCreationDate());
            $treatmentStd->setEditDate($treatment->getEditDate());
            $treatmentStd->setMainPurpose($treatment->getMainPurpose());
            $treatmentStd->setPurpose1($treatment->getPurpose1());
            $treatmentStd->setPurpose2($treatment->getPurpose2());
            $treatmentStd->setPurpose3($treatment->getPurpose3());
            $treatmentStd->setPurpose4($treatment->getPurpose4());
            $treatmentStd->setPurpose5($treatment->getPurpose5());
            $treatmentStd->setOthersPurpose($treatment->getOthersPurpose());
            $treatmentStd->setDescription($treatment->getDescription());
            $treatmentStd->setPersonalData($treatment->getPersonalData());
            $treatmentStd->setPeopleData($treatment->getPeopleData());
            $treatmentStd->setTransferOutsideUeCountries($treatment->getTransferOutsideUeCountries());
            $treatmentStd->setSensitiveData($treatment->isSensitiveData());
            $treatmentStd->setConsentAsked($treatment->isConsentAsked());
            $treatmentStd->setConsentHow($treatment->getConsentHow());
            $treatmentStd->setPiaCriteria($treatment->getPiaCriteria());
            $treatmentStd->setPiaNeeded($treatment->isPiaNeeded());
            $treatmentStd->setPiaExoneration($treatment->isPiaExoneration());
            $treatmentStd->setLegalBasis($treatment->getLegalBasis());
            $treatmentStd->setDataSource($treatment->getDataSource());
            $treatmentStd->setAutomatedDecision($treatment->isAutomatedDecision());
            $treatmentStd->setInsufficientCriteria($treatment->isInsufficientCriteria());
            $treatmentStd->setDataRetentionPeriod($treatment->getDataRetentionPeriod());

            $treatmentStd->setCategory($form["category"]->getData());

            $token = $security->getToken();

            if ($token instanceof SwitchUserToken) {
                $impersonatorUser = $token->getOriginalToken()->getUser();
                if ($impersonatorUser) {
                    if ($impersonatorUser->getManager()) {
                        $originalManager = $em->getRepository(Manager::class)->find($impersonatorUser->getManager()->getId());
                        if ($originalManager) {
                            $treatmentStd->setManager($originalManager);
                        }
                    }
                }
            }

            $em->persist($treatmentStd);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Nouveau traitement standard généré');
            return $this->redirectToRoute("user_treatments");
        }

        return $this->render('user/treatments_standardize.html.twig', [
            "form" => $form->createView(),
        ]);

    }

    /**
     * @Route("/treatments/group", name="treatments_group")
     */
    public function treatmentsGroupAction(Request $request)
    {
        if (!$this->getUser()->getUser()->getParentUser()) {
            throw new NotFoundHttpException();
        }

        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()->getParentUser(), "group" => true]);

        $form = $this->createFormBuilder();

        foreach ($treatments as $treatment) {
            $form->add("t_".$treatment->getId(), CheckboxType::class, [
                "label" => " ",
                "data" => $this->getUser()->getUser()->getGroupTreatments()->contains($treatment),
                "required" => false
            ]);
        }

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            foreach ($treatments as $treatment) {
                if ($form["t_".$treatment->getId()]->getData()) {
                    if (!$this->getUser()->getUser()->getGroupTreatments()->contains($treatment)) {
                        $this->getUser()->getUser()->getGroupTreatments()->add($treatment);
                    }
                } else {
                    if ($this->getUser()->getUser()->getGroupTreatments()->contains($treatment)) {
                        $this->getUser()->getUser()->getGroupTreatments()->removeElement($treatment);
                    }
                }
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Vos préférences de traitements de groupes ont été enregistrées');
            return $this->redirectToRoute("user_treatments_group");
        }

        return $this->render('user/treatments_group.html.twig', [
            "treatments" => $treatments,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/treatments/export", name="treatments_export")
     */
    public function treatmentsExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            //$treatments = new ArrayCollection(array_merge($treatments, $this->getUser()->getUser()->getGroupTreatments()->toArray()));

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Registre des traitements");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/treatments.html.twig', [
                "treatments" => $treatments
            ]);

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

            if (count($this->getUser()->getUser()->getGroupTreatments())) {
                $pdf->AddPage('L', 'A4');

                $html = $this->renderView('user/pdf/treatments.html.twig', [
                    "treatments" => $this->getUser()->getUser()->getGroupTreatments(),
                    "group" => true
                ]);

                $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            }

            $filename = 'Registre_des_traitements';

            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_treatments");
        }
    }

    /**
     * @Route("/treatments/exportxlsx", name="treatments_export_xlsx")
     */
    public function treatmentsExportXlsxAction(Request $request, EntityManagerInterface $entityManager)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $spreadsheet = new Spreadsheet();

            $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            $newWorkSheet = new Worksheet($spreadsheet, "Traitements");
            $spreadsheet->addSheet($newWorkSheet, 1);

            $spreadsheet->setActiveSheetIndex(1);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Nom du traitement');
            $sheet->setCellValue('B1', 'N° du traitement');
            $sheet->setCellValue('C1', 'Date création');
            $sheet->setCellValue('D1', 'Date mise à jour');
            $sheet->setCellValue('E1', 'Finalité');
            $sheet->setCellValue('F1', 'PIA');

            $i = 1;
            foreach ($treatments as $treatment) {
                $i++;

                $treatmentNumber = null;

                $str = strval($treatment->getNumber());
                $strLen = strlen($str);
                $maxLen = 3;
                if ($strLen < $maxLen) {
                    for ($k = $strLen; $k < $maxLen; $k++) {
                        $str = "0".$str;
                    }
                }

                $treatmentNumber = "T".$str;

                $pia = null;

                if ($treatment->getPiaFile()) {
                    $pia = "Réalisé";
                } else {
                    if ($treatment->isInsufficientCriteria()) {
                        $pia = "Non concerné";
                    } else {
                        if ($treatment->isPiaNeeded()) {
                            if ($treatment->isPiaExoneration()) {
                                $pia = "Exonéré";
                            } else {
                                if ($treatment->getPiaFile()) {
                                    $pia = "Réalisé";
                                } else {
                                    $pia = "À réaliser";
                                }
                            }
                        } else {
                            if ($treatment->isPiaExoneration()) {
                                $pia = "Exonéré";
                            } else {
                                if ($treatment->isSensitiveData()) {
                                    $pia = "Recommandé";
                                } else {
                                    $pia = "Non concerné";
                                }
                            }
                        }
                    }
                }

                $sheet->setCellValue('A' . $i, $treatment->getName());
                $sheet->setCellValue('B' . $i, $treatmentNumber);
                $sheet->setCellValue('C' . $i, $treatment->getCreationDate()->format("d/m/Y"));
                $sheet->setCellValue('D' . $i, $treatment->getEditDate()->format("d/m/Y"));
                $sheet->setCellValue('E' . $i, $treatment->getMainPurpose());
                $sheet->setCellValue('F' . $i, $pia);
            }

            $spreadsheet->removeSheetByIndex(0);

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse();
            $response->setCallback(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="export_registre_des_traitements.xlsx"');
            $response->headers->set('Cache-Control','max-age=0');
            return $response;
        } else {
            return $this->redirectToRoute("user_treatments");
        }
    }

    /**
     * @Route("/treatments/exportfull", name="treatments_export_full")
     */
    public function treatmentsExportFullAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Registre complet des traitements");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('P', 'A4');

            $html = $this->renderView('user/pdf/treatments.html.twig', [
                "treatments" => $treatments
            ]);

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

            if (count($this->getUser()->getUser()->getGroupTreatments())) {
                $pdf->AddPage('P', 'A4');

                $html = $this->renderView('user/pdf/treatments.html.twig', [
                    "treatments" => $this->getUser()->getUser()->getGroupTreatments(),
                    "group" => true
                ]);

                $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            }

            $treatments = new ArrayCollection(array_merge($treatments, $this->getUser()->getUser()->getGroupTreatments()->toArray()));

            foreach ($treatments as $treatment) {
                $pdf->AddPage('P', 'A4');

                $html = $this->renderView('user/pdf/treatment.html.twig', [
                    "treatment" => $treatment
                ]);

                $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            }

            $filename = 'Registre_des_traitements_complet';

            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_treatments");
        }
    }

    /**
     * @Route("/treatments/{id}/export", name="treatments_export_one")
     */
    public function treatmentsExportOneAction(Request $request, Treatment $treatment)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
                if (!$this->getUser()->getUser()->getGroupTreatments()->contains($treatment)) {
                    if (!$this->getUser()->getUser()->getParentUser() || ($treatment->getUser()->getId() != $this->getUser()->getUser()->getParentUser()->getId())) {
                        throw new NotFoundHttpException();
                    }
                }
            }

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Registre des traitements");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('P', 'A4');

            $html = $this->renderView('user/pdf/treatment.html.twig', [
                "treatment" => $treatment
            ]);

            $filename = 'traitement_'.$treatment->getId();

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_treatments");
        }
    }

    /**
     * @Route("/treatments/add", name="treatments_add")
     */
    public function treatmentsAddAction(Request $request, SendEmailService $sendEmailService, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $treatment = new Treatment();

        $user = $this->getUser()->getUser();

        $autoAddSystems = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser(), "autoApplyToTreatments" => true]);
        foreach ($autoAddSystems as $autoAddSystem) {
            $treatment->getSystems()->add($autoAddSystem);
        }

        $companyName = "la société";
        if ($this->getUser()->getUser()->getCompanyName()) {
            $companyName = $this->getUser()->getUser()->getCompanyName();
        }

        /*if (!$treatment->getCompanySubcontractorType()) {
            $defaultCompanySubcontractorType = $em->getRepository(\App\Entity\SubcontractorType::class)->findOneBy(["code" => "RESP_TRAITEMENT"]);
            if ($defaultCompanySubcontractorType) {
                $treatment->setCompanySubcontractorType($defaultCompanySubcontractorType);
            }
        }*/

        $form = $this->createForm(TreatmentType::class, $treatment)
            ->add('companySubcontractorType', EntityType::class, [
                'attr' => array(
                    'placeholder' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                ),
                'label' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                'required' => true,
                'class' => \App\Entity\SubcontractorType::class,
                'expanded' => true,
            ])
            ->add('subcontractors', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'sous_traitants',
                ),
                'choice_attr' => function(Subcontractor $subcontractor, $key, $value) {
                    return ['data-st' => $subcontractor->getSubcontractorType()->getId()];
                },
                'placeholder' => 'sous_traitants',
                'label' => 'sous_traitants',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Subcontractor::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                $qb->expr()->orX(
                                    "s.user = :user",
                                    $qb->expr()->andX(
                                        "s.user = :parentUser",
                                        "s.group = true"
                                    )
                                )
                            )
                        )
                            ->setParameter("parentUser", $user->getParentUser());
                    } else {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                "s.user = :user"
                            )
                        );
                    }

                    $qb->setParameter("user", $user)
                        ->addOrderBy("s.subcontractorType", "ASC")
                        ->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(Subcontractor $subcontractor) {
                    return $subcontractor->getName();
                },
            ])
            ->add('systems', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'mesures_techniques',
                ),
                'placeholder' => 'mesures_techniques',
                'label' => 'mesures_techniques',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => System::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where($qb->expr()->orX(
                            "s.user = :user"
                            ,
                            $qb->expr()->andX(
                                "s.user = :parentUser",
                                "s.group = true"
                            )
                        ));
                    } else {
                        $qb->where("s.user = :user");
                    }

                    $qb->setParameter("user", $user);

                    if ($user->getParentUser()) {
                        $qb->setParameter("parentUser", $user->getParentUser());
                    }

                    $qb->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(System $system) {
                    return $system->getName();
                },
            ])
        ;

        $form->add('piaExoneration', CheckboxType::class, [
            'label' => "cas_dxonration_de_ralisation_de_pia",
            'required' => false,
            'translation_domain' => 'messages',
        ]);
        $form->add('insufficientCriteria', CheckboxType::class, [
            'label' => "abscence_de_critere_suffisant",
            'required' => false,
            'translation_domain' => 'messages',
        ]);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "ce_traitement_concerne_le_groupe",
                'required' => false,
            ]);
        }

        $form["state"]->setData($this->getDoctrine()->getRepository(TreatmentState::class)->find(1));

        $fromStd = false;

        if (isset($_GET['std'])) {
            $treatmentStd = $this->getDoctrine()->getRepository(TreatmentStd::class)->find($_GET['std']);
            if ($treatmentStd) {
                if ($treatmentStd->getUser()) {
                    if ($treatmentStd->getUser()->getId() != $this->getUser()->getUser()->getId()) {
                        if ($this->getUser()->getUser()->getParentUser()) {
                            if ($treatmentStd->getUser()->getId() != $this->getUser()->getUser()->getParentUser()->getId()) {
                                throw new NotFoundHttpException();
                            }
                        } else {
                            throw new NotFoundHttpException();
                        }
                    }
                }

                $fromStd = true;

                $form['name']->setData($treatmentStd->getName());
                $form['mainPurpose']->setData($treatmentStd->getMainPurpose());
                $form['purpose1']->setData($treatmentStd->getPurpose1());
                $form['purpose2']->setData($treatmentStd->getPurpose2());
                $form['purpose3']->setData($treatmentStd->getPurpose3());
                $form['purpose4']->setData($treatmentStd->getPurpose4());
                $form['purpose5']->setData($treatmentStd->getPurpose5());
                $form['othersPurpose']->setData($treatmentStd->getOthersPurpose());
                $form['description']->setData($treatmentStd->getDescription());
                $form['peopleData']->setData($treatmentStd->getPeopleData());
                $form['transferOutsideUeCountries']->setData($treatmentStd->getTransferOutsideUeCountries());
                $form['consentAsked']->setData($treatmentStd->isConsentAsked());
                $form['consentHow']->setData($treatmentStd->getConsentHow());
                $form['legalBasis']->setData($treatmentStd->getLegalBasis());
                $form['dataSource']->setData($treatmentStd->getDataSource());
                //$form['automatedDecision']->setData($treatmentStd->isAutomatedDecision());
                $form['dataRetentionPeriod']->setData($treatmentStd->getDataRetentionPeriod());

                $personalDataFields = $treatmentStd->getPersonalData();

                foreach ($treatmentStd->getPersonalData() as $key => $field) {
                    $form->add("field_text_".$key, TextType::class, [
                        'attr' => [
                            'placeholder' => 'zone_de_saisie'
                        ],
                        'label' => 'zone_de_saisie',
                        'data' => $field['text'],
                        'required' => false,
                        'translation_domain' => 'messages',
                        'mapped' => false
                    ]);
                    /*    ->add("field_duration_".$key, TextType::class, [
                        'attr' => [
                            'placeholder' => 'Durée de conservation'
                        ],
                        'label' => 'Durée de conservation',
                        'data' => $field['duration'],
                        'required' => false,
                        'mapped' => false
                    ]);*/
                }
            }
        }

        if (!$fromStd) {
            $personalDataFields = [
                [
                    "title" => $translator->trans('tat_civil_identit_donnes_didentification_images', [], 'messages'),
                    "level" => 1,
                ],
                [
                    "title" => $translator->trans('vie_personnelle_habitudes_de_vie_situation_familia', [], 'messages'),
                    "level" => 1,
                ],
                [
                    "title" => $translator->trans('infos_dordre_conomique_et_financier_revenus_situat', [], 'messages'),
                    "level" => 1,
                ],
                [
                    "title" => $translator->trans('donnes_de_connexion_adress_ip_logs_etc', [], 'messages'),
                    "level" => 1,
                ],
                [
                    "title" => $translator->trans('donnes_de_localisation_dplacements_donnes_gps_gsm', [], 'messages'),
                    "level" => 1,
                ],
                [
                    "title" => $translator->trans('donnes_bancaires_donnes_courantes_non_sensible_mai', [], 'messages'),
                    "level" => 2,
                ],
                [
                    "title" => $translator->trans('numro_de_scurit_sociale_ou_nir', [], 'messages'),
                    "level" => 2,
                ],
                [
                    "title" => $translator->trans('donnes_rvlant_lorigine_raciale_ou_ethnique', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_rvlant_les_opinions_politiques', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_rvlant_les_convictions_religieuses_ou_philo', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_rvlant_lappartenance_syndicale', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_gntiques', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_biomtriques_aux_fins_didentifier_une_person', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_concernant_la_sant', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_concernant_la_vie_sexuelle_ou_lorientation', [], 'messages'),
                    "level" => 3,
                ],
                [
                    "title" => $translator->trans('donnes_relatives_des_condamnations_pnales_ou_infra', [], 'messages'),
                    "level" => 3,
                ],
            ];

            foreach ($personalDataFields as $key => $field) {
                $form->add("field_text_".$key, TextType::class, [
                    'attr' => [
                        'placeholder' => 'zone_de_saisie'
                    ],
                    'label' => 'zone_de_saisie',
                    'required' => false,
                    'mapped' => false,
                    'translation_domain' => 'messages',
                ]);
                /*    ->add("field_duration_".$key, TextType::class, [
                    'attr' => [
                        'placeholder' => 'Durée de conservation'
                    ],
                    'label' => 'Durée de conservation',
                    'required' => false,
                    'mapped' => false
                ]);*/
            }
        }

        if ($this->getUser()->getUser()->getCompanyName()) {
            $form['treatmentAccountant']->setData($this->getUser()->getUser()->getCompanyName());
        } elseif ($this->getUser()->getUser()->getAccountantFirstName() || $this->getUser()->getUser()->getAccountantLastName()) {
            $form['treatmentAccountant']->setData($this->getUser()->getUser()->getAccountantFirstName().' '.$this->getUser()->getUser()->getAccountantLastName());
        }
        if ($this->getUser()->getUser()->getManager() && ($this->getUser()->getUser()->getManager()->getFirstName() || $this->getUser()->getUser()->getManager()->getLastName())) {
            $form['dpo']->setData($this->getUser()->getUser()->getManager()->getFirstName().' '.$this->getUser()->getUser()->getManager()->getLastName());
        }
        if ($this->getUser()->getUser()->getContactFirstName() || $this->getUser()->getUser()->getContactLastName()) {
            $form['editor']->setData($this->getUser()->getUser()->getContactFirstName().' '.$this->getUser()->getUser()->getContactLastName());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $treatmentsQuery = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()], ['number' => "DESC"], 1);

            $now = new \DateTime("now");

            $personalData = [];

            $sensitiveData = false;

            /*if ($treatment->getTransferOutsideUeCountries()) {
                $sensitiveData = true;
            }*/
            if ($treatment->isAutomatedDecision()) {
                $sensitiveData = true;
            }
            foreach ($personalDataFields as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                    //"duration" => $form["field_duration_".$key]->getData(),
                ];

                if (($field['level'] == 2 || $field['level'] == 3) && ($form["field_text_".$key]->getData()/* || $form["field_duration_".$key]->getData()*/)) {
                    $sensitiveData = true;
                }
            }

            $treatment->setPersonalData($personalData);
            $treatment->setSensitiveData($sensitiveData);

            $treatment->setCreationDate($now);
            $treatment->setEditDate($now);
            $treatment->setUser($this->getUser()->getUser());

            if (count($treatmentsQuery)) {
                $number = $treatmentsQuery[0]->getNumber() + 1;
            } else {
                $number = 1;
            }

            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            }/* else {
                $treatment->setInsufficientCriteria(true);
            }*/

            $treatment->setNumber($number);

            if (count($treatment->getPiaCriteria()) >= 2) {
                $treatment->setPiaNeeded(true);
            } else {
                $treatment->setPiaNeeded(false);
            }

            $em->persist($treatment);
            $em->flush();

            if ($treatment->isGroup()) {
                foreach ($this->getUser()->getUser()->getChildrenUsers() as $childUser) {
                    if (!$childUser->getGroupTreatments()->contains($treatment)) {
                        $childUser->getGroupTreatments()->add($treatment);
                    }
                }
                $em->flush();
            }

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('piaFileFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $treatment->setPiaFile($fileName);
                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a créé un nouveau traitement: ".$treatment->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau traitement client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau traitement client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Nouveau traitement ajouté');
            return $this->redirectToRoute("user_treatments");
        }

        $treatmentsStdGroups = [];

        $treatmentsStdFromGroup = $this->getDoctrine()->getRepository(TreatmentStd::class)->findAllForGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
        if (count($treatmentsStdFromGroup)) {
            $treatmentsStdGroups["group"] = [
                "category" => ["id" => 0, "libelle" => "Standards du groupe"],
                "treatmentsStd" => $treatmentsStdFromGroup
            ];
        }

        $treatmentsStd = $this->getDoctrine()->getRepository(TreatmentStd::class)->findAllOrdered();
        foreach ($treatmentsStd as $treatmentStd) {
            if (!isset($treatmentsStdGroups[$treatmentStd->getCategory()->getId()])) {
                $treatmentsStdGroups[$treatmentStd->getCategory()->getId()] = [
                    "category" => $treatmentStd->getCategory(),
                    "treatmentsStd" => []
                ];
            }
            $treatmentsStdGroups[$treatmentStd->getCategory()->getId()]["treatmentsStd"][] = $treatmentStd;
        }

        $subcontractors = [];
        $systems = [];
        $subcontractorsStr = [];
        $systemsStr = [];

        foreach ($treatment->getSubcontractors() as $subcontractor) {
            $subcontractors[] = $subcontractor->getId();
            $subcontractorsStr[] = $subcontractor->getName();
        }

        foreach ($treatment->getSystems() as $system) {
            $systems[] = $system->getId();
            $systemsStr[] = $system->getName();
        }

        sort($subcontractorsStr);
        sort($systemsStr);

        $subcontractorsTypes = [];

        $stRequest = $this->getDoctrine()->getRepository(\App\Entity\SubcontractorType::class)->findAll();
        foreach ($stRequest as $item) {
            $subcontractorsTypes[$item->getId()] = $item->getLibelle();
        }

        return $this->render('user/treatments_add.html.twig', [
            "form" => $form->createView(),
            "treatmentsStdGroups" => $treatmentsStdGroups,
            "personalDataFields" => $personalDataFields,
            "subcontractors" => $subcontractors,
            "systems" => $systems,
            "subcontractorsStr" => $subcontractorsStr,
            "systemsStr" => $systemsStr,
            "subcontractorsTypes" => $subcontractorsTypes
        ]);
    }

    /**
     * @Route("/treatments/{id}/edit", name="treatments_edit")
     */
    public function treatmentsEditAction(Request $request, SendEmailService $sendEmailService, Treatment $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        if (count($treatment->getPiaCriteria()) == 0) {
            $changesv2 = false;
            $em = $this->getDoctrine()->getManager();
            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            }
            if ($treatment->isAutomatedDecision()) {
                $treatment->addPiaCriteria(4);
                $treatment->setAutomatedDecision(false);
            }
            if ($changesv2) {
                $em->flush();
                return $this->redirectToRoute("user_treatments_edit", ["id" => $treatment->getId()]);
            }
        }

        $user = $this->getUser()->getUser();

        $currentGroupTreatment = ($treatment->isGroup() == true);

        $companyName = "la société";
        if ($this->getUser()->getUser()->getCompanyName()) {
            $companyName = $this->getUser()->getUser()->getCompanyName();
        }

        /*if (!$treatment->getCompanySubcontractorType()) {
            $defaultCompanySubcontractorType = $em->getRepository(\App\Entity\SubcontractorType::class)->findOneBy(["code" => "RESP_TRAITEMENT"]);
            if ($defaultCompanySubcontractorType) {
                $treatment->setCompanySubcontractorType($defaultCompanySubcontractorType);
            }
        }*/

        $form = $this->createForm(TreatmentType::class, $treatment)
            ->add('companySubcontractorType', EntityType::class, [
                'attr' => array(
                    'placeholder' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                ),
                'label' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                'required' => true,
                'class' => \App\Entity\SubcontractorType::class,
                'expanded' => true,
            ])
            ->add('subcontractors', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'sous_traitants',
                ),
                'choice_attr' => function(Subcontractor $subcontractor, $key, $value) {
                    return ['data-st' => $subcontractor->getSubcontractorType()->getId()];
                },
                'placeholder' => 'sous_traitants',
                'label' => 'sous_traitants',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Subcontractor::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                $qb->expr()->orX(
                                    "s.user = :user",
                                    $qb->expr()->andX(
                                        "s.user = :parentUser",
                                        "s.group = true"
                                    )
                                )
                            )
                        )
                            ->setParameter("parentUser", $user->getParentUser());
                    } else {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                "s.user = :user"
                            )
                        );
                    }

                    $qb->setParameter("user", $user)
                        ->addOrderBy("s.subcontractorType", "ASC")
                        ->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(Subcontractor $subcontractor) {
                    return $subcontractor->getName();
                },
            ])
            ->add('systems', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'mesures_techniques',
                ),
                'placeholder' => 'mesures_techniques',
                'label' => 'mesures_techniques',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => System::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where($qb->expr()->orX(
                            "s.user = :user"
                            ,
                            $qb->expr()->andX(
                                "s.user = :parentUser",
                                "s.group = true"
                            )
                        ));
                    } else {
                        $qb->where("s.user = :user");
                    }

                    $qb->setParameter("user", $user);

                    if ($user->getParentUser()) {
                        $qb->setParameter("parentUser", $user->getParentUser());
                    }

                    $qb->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(System $system) {
                    return $system->getName();
                },
            ])
        ;

        $form->add('piaExoneration', CheckboxType::class, [
            'label' => "cas_dxonration_de_ralisation_de_pia",
            'required' => false,
            'translation_domain' => 'messages',
        ]);
        $form->add('insufficientCriteria', CheckboxType::class, [
            'label' => "abscence_de_critere_suffisant",
            'required' => false,
            'translation_domain' => 'messages',
        ]);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "ce_traitement_concerne_le_groupe",
                'required' => false,
            ]);
        }

        if ($treatment->getPiaFile()) {
            $form->add('deletePiaFile', CheckboxType::class, [
                'label' => "supprimer_le_pia",
                'required' => false,
                'translation_domain' => 'messages',
                "mapped" => false
            ]);
        }

        foreach ($treatment->getPersonalData() as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'zone_de_saisie'
                ],
                'label' => 'zone_de_saisie',
                'data' => $field['text'],
                'required' => false,
                'translation_domain' => 'messages',
                'mapped' => false
            ]);
            /*->add("field_duration_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'Durée de conservation'
                ],
                'label' => 'Durée de conservation',
                'data' => $field['duration'],
                'required' => false,
                'mapped' => false
            ]);*/
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $treatmentsQuery = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

            $now = new \DateTime("now");

            $personalData = [];

            $sensitiveData = false;

            /*if ($treatment->getTransferOutsideUeCountries()) {
                $sensitiveData = true;
            }*/
            if ($treatment->isAutomatedDecision()) {
                $sensitiveData = true;
            }
            foreach ($treatment->getPersonalData() as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                    //"duration" => $form["field_duration_".$key]->getData(),
                ];

                if (($field['level'] == 2 || $field['level'] == 3) && ($form["field_text_".$key]->getData()/* || $form["field_duration_".$key]->getData()*/)) {
                    $sensitiveData = true;
                }
            }

            $treatment->setPersonalData($personalData);
            $treatment->setSensitiveData($sensitiveData);

            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            }/* else {
                $treatment->setInsufficientCriteria(true);
            }*/

            $treatment->setEditDate($now);

            if (count($treatment->getPiaCriteria()) >= 2) {
                $treatment->setPiaNeeded(true);
            } else {
                $treatment->setPiaNeeded(false);
            }

            if ($treatment->getPiaFile()) {
                if ($form["deletePiaFile"]->getData()) {
                    $treatment->setPiaFile(null);
                }
            }

            $em->flush();

            if (!$currentGroupTreatment && $treatment->isGroup()) {
                foreach ($this->getUser()->getUser()->getChildrenUsers() as $childUser) {
                    if (!$childUser->getGroupTreatments()->contains($treatment)) {
                        $childUser->getGroupTreatments()->add($treatment);
                    }
                }
                $em->flush();
            } elseif ($currentGroupTreatment && !$treatment->isGroup()) {
                foreach ($this->getUser()->getUser()->getChildrenUsers() as $childUser) {
                    if ($childUser->getGroupTreatments()->contains($treatment)) {
                        $childUser->getGroupTreatments()->removeElement($treatment);
                    }
                }
                $em->flush();
            }

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('piaFileFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $treatment->setPiaFile($fileName);
                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié le traitement ".$treatment->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site Pilot. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Traitement client modifié",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Traitement client modifié",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Traitement mis à jour');
            return $this->redirectToRoute("user_treatments");
        }

        $subcontractors = [];
        $systems = [];
        $subcontractorsStr = [];
        $systemsStr = [];

        foreach ($treatment->getSubcontractors() as $subcontractor) {
            $subcontractors[] = $subcontractor->getId();
            $subcontractorsStr[] = $subcontractor->getName();
        }

        foreach ($treatment->getSystems() as $system) {
            $systems[] = $system->getId();
            $systemsStr[] = $system->getName();
        }

        sort($subcontractorsStr);
        sort($systemsStr);

        $subcontractorsTypes = [];

        $stRequest = $this->getDoctrine()->getRepository(\App\Entity\SubcontractorType::class)->findAll();
        foreach ($stRequest as $item) {
            $subcontractorsTypes[$item->getId()] = $item->getLibelle();
        }

        return $this->render('user/treatments_edit.html.twig', [
            "form" => $form->createView(),
            "treatment" => $treatment,
            "personalDataFields" => $treatment->getPersonalData(),
            "subcontractors" => $subcontractors,
            "systems" => $systems,
            "subcontractorsStr" => $subcontractorsStr,
            "systemsStr" => $systemsStr,
            "subcontractorsTypes" => $subcontractorsTypes
        ]);
    }

    /**
     * @Route("/treatments/{id}/copy", name="treatments_copy")
     */
    public function treatmentsCopyAction(Request $request, SendEmailService $sendEmailService, Treatment $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        if (count($treatment->getPiaCriteria()) == 0) {
            $changesv2 = false;
            $em = $this->getDoctrine()->getManager();
            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            }
            if ($treatment->isAutomatedDecision()) {
                $treatment->addPiaCriteria(4);
                $treatment->setAutomatedDecision(false);
            }
            if ($changesv2) {
                $em->flush();
                return $this->redirectToRoute("user_treatments_copy", ["id" => $treatment->getId()]);
            }
        }

        $newTreatment = clone $treatment;
        $newTreatment->setId(null);
      

        $user = $this->getUser()->getUser();

        $companyName = "la société";
        if ($this->getUser()->getUser()->getCompanyName()) {
            $companyName = $this->getUser()->getUser()->getCompanyName();
        }

        /*if (!$newTreatment->getCompanySubcontractorType()) {
            $defaultCompanySubcontractorType = $em->getRepository(\App\Entity\SubcontractorType::class)->findOneBy(["code" => "RESP_TRAITEMENT"]);
            if ($defaultCompanySubcontractorType) {
                $newTreatment->setCompanySubcontractorType($defaultCompanySubcontractorType);
            }
        }*/

        $form = $this->createForm(TreatmentType::class, $newTreatment)
            ->add('companySubcontractorType', EntityType::class, [
                'attr' => array(
                    'placeholder' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                ),
                'label' => ($request->getLocale() == "fr")?"Responsabilité de ".$companyName." liée à ce traitement":"Responsability of ".$companyName." related to this processing",
                'required' => true,
                'class' => \App\Entity\SubcontractorType::class,
                'expanded' => true,
            ])
            ->add('subcontractors', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'sous_traitants',
                ),
                'choice_attr' => function(Subcontractor $subcontractor, $key, $value) {
                    return ['data-st' => $subcontractor->getSubcontractorType()->getId()];
                },
                'placeholder' => 'sous_traitants',
                'label' => 'sous_traitants',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Subcontractor::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                $qb->expr()->orX(
                                    "s.user = :user",
                                    $qb->expr()->andX(
                                        "s.user = :parentUser",
                                        "s.group = true"
                                    )
                                )
                            )
                        )
                            ->setParameter("parentUser", $user->getParentUser());
                    } else {
                        $qb->where(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull("s.user"),
                                "s.user = :user"
                            )
                        );
                    }

                    $qb->setParameter("user", $user)
                        ->addOrderBy("s.subcontractorType", "ASC")
                        ->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(Subcontractor $subcontractor) {
                    return $subcontractor->getName();
                },
            ])
            ->add('systems', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'mesures_techniques',
                ),
                'placeholder' => 'mesures_techniques',
                'label' => 'mesures_techniques',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => System::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    $qb = $er->createQueryBuilder('s');

                    if ($user->getParentUser()) {
                        $qb->where($qb->expr()->orX(
                            "s.user = :user"
                            ,
                            $qb->expr()->andX(
                                "s.user = :parentUser",
                                "s.group = true"
                            )
                        ));
                    } else {
                        $qb->where("s.user = :user");
                    }

                    $qb->setParameter("user", $user);

                    if ($user->getParentUser()) {
                        $qb->setParameter("parentUser", $user->getParentUser());
                    }

                    $qb->addOrderBy("s.name", "ASC");

                    return $qb;
                },
                'choice_label' => function(System $system) {
                    return $system->getName();
                },
            ])
        ;

        $form->add('piaExoneration', CheckboxType::class, [
            'label' => "cas_dxonration_de_ralisation_de_pia",
            'required' => false,
            'translation_domain' => 'messages',
        ]);
        $form->add('insufficientCriteria', CheckboxType::class, [
            'label' => "abscence_de_critere_suffisant",
            'required' => false,
            'translation_domain' => 'messages',
        ]);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "ce_traitement_concerne_le_groupe",
                'required' => false,
            ]);
        }

        foreach ($newTreatment->getPersonalData() as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'zone_de_saisie'
                ],
                'label' => 'zone_de_saisie',
                'data' => $field['text'],
                'required' => false,
                'translation_domain' => 'messages',
                'mapped' => false
            ]);
            /*->add("field_duration_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'Durée de conservation'
                ],
                'label' => 'Durée de conservation',
                'data' => $field['duration'],
                'required' => false,
                'mapped' => false
            ]);*/
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        
            $treatmentsQuery = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()], ['number' => "DESC"], 1);

            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $personalData = [];

            $sensitiveData = false;

            if ($newTreatment->getTransferOutsideUeCountries()) {
                $sensitiveData = true;
            }
            if ($newTreatment->isAutomatedDecision()) {
                $sensitiveData = true;
            }
            foreach ($newTreatment->getPersonalData() as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                    //"duration" => $form["field_duration_".$key]->getData(),
                ];

                if (($field['level'] == 2 || $field['level'] == 3) && ($form["field_text_".$key]->getData()/* || $form["field_duration_".$key]->getData()*/)) {
                    $sensitiveData = true;
                }
            }

            $newTreatment->setPersonalData($personalData);
            $newTreatment->setSensitiveData($sensitiveData);

            if ($newTreatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            } else {
                $treatment->setInsufficientCriteria(true);
            }

            $newTreatment->setCreationDate($now);
            $newTreatment->setEditDate($now);
            $newTreatment->setUser($this->getUser()->getUser());

            if (count($treatmentsQuery)) {
                $number = $treatmentsQuery[0]->getNumber() + 1;
            } else {
                $number = 1;
            }

            $newTreatment->setNumber($number);

            if (count($newTreatment->getPiaCriteria()) >= 2) {
                $newTreatment->setPiaNeeded(true);
            } else {
                $newTreatment->setPiaNeeded(false);
            }

            if (!$newTreatment->isGroup()) {
                $newTreatment->getGroupUsers()->clear();
            }

            $em->persist($newTreatment);
            $em->flush();

            if ($newTreatment->isGroup()) {
                foreach ($this->getUser()->getUser()->getChildrenUsers() as $childUser) {
                    if (!$childUser->getGroupTreatments()->contains($newTreatment)) {
                        $childUser->getGroupTreatments()->add($newTreatment);
                    }
                }
                $em->flush();
            }

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('piaFileFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $newTreatment->setPiaFile($fileName);
                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a créé un nouveau traitement: ".$newTreatment->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau traitement client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau traitement client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Traitement dupliqué');
            return $this->redirectToRoute("user_treatments");
        }

        $subcontractors = [];
        $systems = [];
        $subcontractorsStr = [];
        $systemsStr = [];

        foreach ($treatment->getSubcontractors() as $subcontractor) {
            $subcontractors[] = $subcontractor->getId();
            $subcontractorsStr[] = $subcontractor->getName();
        }

        foreach ($treatment->getSystems() as $system) {
            $systems[] = $system->getId();
            $systemsStr[] = $system->getName();
        }

        sort($subcontractorsStr);
        sort($systemsStr);

        $subcontractorsTypes = [];

        $stRequest = $this->getDoctrine()->getRepository(\App\Entity\SubcontractorType::class)->findAll();
        foreach ($stRequest as $item) {
            $subcontractorsTypes[$item->getId()] = $item->getLibelle();
        }

        return $this->render('user/treatments_copy.html.twig', [
            "form" => $form->createView(),
            "treatment" => $treatment,
            "personalDataFields" => $treatment->getPersonalData(),
            "subcontractors" => $subcontractors,
            "systems" => $systems,
            "subcontractorsStr" => $subcontractorsStr,
            "systemsStr" => $systemsStr,
            "subcontractorsTypes" => $subcontractorsTypes
        ]);
    }

    /**
     * @Route("/treatments/{id}/show", name="treatments_show")
     */
    public function treatmentsShowAction(Request $request, Treatment $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            if (!$this->getUser()->getUser()->getGroupTreatments()->contains($treatment)) {
                if (!$this->getUser()->getUser()->getParentUser() || ($treatment->getUser()->getId() != $this->getUser()->getUser()->getParentUser()->getId())) {
                    throw new NotFoundHttpException();
                }
            }
        }

        return $this->render('user/treatments_show.html.twig', [
            "treatment" => $treatment,
        ]);
    }

    /**
     * @Route("/treatments/{id}/delete", name="treatments_delete")
     */
    public function treatmentsDeleteAction(Request $request, Treatment $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($treatment);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Traitement supprimé');
        return $this->redirectToRoute("user_treatments");
    }

    /**
     * @Route("/treatmentsstd", name="treatments_std")
     */
    public function treatmentsStdAction(Request $request)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }

        $treatments = $this->getDoctrine()->getRepository(TreatmentStd::class)->findBy(["user" => $this->getUser()->getUser()]);

        return $this->render('user/treatments_std.html.twig', [
            "treatments" => $treatments
        ]);
    }

    /**
     * @Route("/treatmentsstd/add", name="treatments_std_add")
     */
    public function treatmentsStdAddAction(Request $request)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }

        $treatment = new TreatmentStd();

        $form = $this->createForm(TreatmentStdType::class, $treatment);

        $personalDataFields = [
            [
                "title" => "État civil, identité, données d'identification, images...",
                "level" => 1,
            ],
            [
                "title" => "Vie personnelle (habitudes de vie, situation familiale, etc.)",
                "level" => 1,
            ],
            [
                "title" => "Infos d'ordre économique et financier (revenus, situation financière, situation fiscale, etc.)",
                "level" => 1,
            ],
            [
                "title" => "Données de connexion (adress IP, logs, etc.)",
                "level" => 1,
            ],
            [
                "title" => "Données de localisation (déplacements, données GPS, GSM, etc.)",
                "level" => 1,
            ],
            [
                "title" => "Données Bancaires (données courantes « non sensible » mais classifié comme tel au vu des risques financiers)",
                "level" => 2,
            ],
            [
                "title" => "Numéro de Sécurité Sociale (ou NIR)",
                "level" => 2,
            ],
            [
                "title" => "Données révélant l'origine raciale ou ethnique",
                "level" => 3,
            ],
            [
                "title" => "Données révélant les opinions politiques",
                "level" => 3,
            ],
            [
                "title" => "Données révélant les convictions religieuses ou philosophiques",
                "level" => 3,
            ],
            [
                "title" => "Données révélant l'appartenance syndicale",
                "level" => 3,
            ],
            [
                "title" => "Données génétiques",
                "level" => 3,
            ],
            [
                "title" => "Données biométriques aux fins d'identifier une personne physique de manière unique",
                "level" => 3,
            ],
            [
                "title" => "Données concernant la santé",
                "level" => 3,
            ],
            [
                "title" => "Données concernant la vie sexuelle ou l'orientation sexuelle",
                "level" => 3,
            ],
            [
                "title" => "Données relatives à des condamnations pénales ou infractions",
                "level" => 3,
            ],
        ];

        foreach ($personalDataFields as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'zone_de_saisie'
                ],
                'label' => 'zone_de_saisie',
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'messages',
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $personalData = [];

            $sensitiveData = false;

            foreach ($personalDataFields as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                    //"duration" => $form["field_duration_".$key]->getData(),
                ];

                if (($field['level'] == 2 || $field['level'] == 3) && ($form["field_text_".$key]->getData()/* || $form["field_duration_".$key]->getData()*/)) {
                    $sensitiveData = true;
                }
            }

            $treatment->setPersonalData($personalData);
            $treatment->setSensitiveData($sensitiveData);

            $treatment->setCreationDate($now);
            $treatment->setEditDate($now);
            $treatment->setUser($this->getUser()->getUser());

            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            } else {
                $treatment->setInsufficientCriteria(true);
            }

            if (count($treatment->getPiaCriteria()) >= 2) {
                $treatment->setPiaNeeded(true);
            } else {
                $treatment->setPiaNeeded(false);
            }

            $em->persist($treatment);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Nouveau traitement standard ajouté');
            return $this->redirectToRoute("user_treatments_std");
        }

        return $this->render('user/treatments_std_add.html.twig', [
            "form" => $form->createView(),
            "personalDataFields" => $personalDataFields
        ]);
    }

    /**
     * @Route("/treatmentsstd/{id}/edit", name="treatments_std_edit")
     */
    public function treatmentsStdEditAction(Request $request, TreatmentStd $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        if (count($treatment->getPiaCriteria()) == 0) {
            if ($treatment->isAutomatedDecision()) {
                $em = $this->getDoctrine()->getManager();

                $treatment->setPiaCriteria([4]);
                $treatment->setAutomatedDecision(false);

                $em->flush();
                return $this->redirectToRoute("user_treatments_std_edit", ["id" => $treatment->getId()]);
            }
        }

        $form = $this->createForm(TreatmentStdType::class, $treatment);

        foreach ($treatment->getPersonalData() as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'zone_de_saisie'
                ],
                'label' => 'zone_de_saisie',
                'data' => $field['text'],
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'messages',
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $personalData = [];

            $sensitiveData = false;

            foreach ($treatment->getPersonalData() as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                    //"duration" => $form["field_duration_".$key]->getData(),
                ];

                if (($field['level'] == 2 || $field['level'] == 3) && ($form["field_text_".$key]->getData()/* || $form["field_duration_".$key]->getData()*/)) {
                    $sensitiveData = true;
                }
            }

            $treatment->setPersonalData($personalData);
            $treatment->setSensitiveData($sensitiveData);

            if ($treatment->isSensitiveData()) {
                if (!in_array(1, $treatment->getPiaCriteria())) {
                    $treatment->addPiaCriteria(1);
                }
            } else {
                $treatment->setInsufficientCriteria(true);
            }

            $treatment->setEditDate($now);

            if (count($treatment->getPiaCriteria()) >= 2) {
                $treatment->setPiaNeeded(true);
            } else {
                $treatment->setPiaNeeded(false);
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traitement standard mis à jour');
            return $this->redirectToRoute("user_treatments_std");
        }

        return $this->render('user/treatments_std_edit.html.twig', [
            "form" => $form->createView(),
            "treatment" => $treatment,
            "personalDataFields" => $treatment->getPersonalData()
        ]);
    }

    /**
     * @Route("/treatmentsstd/{id}/delete", name="treatments_std_delete")
     */
    public function treatmentsStdDeleteAction(Request $request, TreatmentStd $treatment)
    {
        if ($treatment->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($treatment);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Traitement standard supprimé');
        return $this->redirectToRoute("user_treatments_std");
    }

    /**
     * @Route("/dataprocessing", name="data_processing")
     */
    public function dataProcessingAction(Request $request)
    {
        return $this->redirectToRoute("user_subcontractors", ["type" => 1]);
        /*return $this->render('user/data_processing.html.twig', [
        ]);*/
    }

    /**
     * @Route("/subcontractors/t/{type}", name="subcontractors")
     */
    public function subcontractorsAction(Request $request, \App\Entity\SubcontractorType $type)
    {
        $hasFilters = false;
        $filter = null;

        if ($request->get("filter")) {
            $filter = $request->get("filter");
            $hasFilters = true;
        }

        $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser(), "group" => false, "subcontractorType" => $type], ["name" => "ASC"]);
        $subcontractors_grp = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser(), $type);

        $subcontractorsStats = [
            "total" => 0,
            "inProgress" => 0,
            "invalid" => 0,
            "valid" => 0,
        ];

        $filteredSubcontractors = [];
        $filteredSubcontractorsGrp = [];

        if (!$hasFilters) {
            $filteredSubcontractors = $subcontractors;
            $filteredSubcontractorsGrp = $subcontractors_grp;
        }

        foreach ($subcontractors as $subcontractor) {
            if ($subcontractor->getConformity()) {
                $subcontractorsStats["total"]++;
                switch ($subcontractor->getConformity()->getId()) {
                    case 1:
                        $subcontractorsStats["invalid"]++;
                        if ($filter && $filter == "invalid") {
                            $filteredSubcontractors[] = $subcontractor;
                        }
                        break;
                    case 2:
                        $subcontractorsStats["inProgress"]++;
                        if ($filter && $filter == "inprogress") {
                            $filteredSubcontractors[] = $subcontractor;
                        }
                        break;
                    case 3:
                        $subcontractorsStats["valid"]++;
                        if ($filter && $filter == "valid") {
                            $filteredSubcontractors[] = $subcontractor;
                        }
                        break;
                }
            }
        }

        foreach ($subcontractors_grp as $subcontractor) {
            if ($subcontractor->getConformity()) {
                $subcontractorsStats["total"]++;
                switch ($subcontractor->getConformity()->getId()) {
                    case 1:
                        $subcontractorsStats["invalid"]++;
                        if ($filter && $filter == "invalid") {
                            $filteredSubcontractorsGrp[] = $subcontractor;
                        }
                        break;
                    case 2:
                        $subcontractorsStats["inProgress"]++;
                        if ($filter && $filter == "inprogress") {
                            $filteredSubcontractorsGrp[] = $subcontractor;
                        }
                        break;
                    case 3:
                        $subcontractorsStats["valid"]++;
                        if ($filter && $filter == "valid") {
                            $filteredSubcontractorsGrp[] = $subcontractor;
                        }
                        break;
                }
            }
        }

        return $this->render('user/subcontractors.html.twig', [
            "subcontractors" => $filteredSubcontractors,
            "subcontractorsGrp" => $filteredSubcontractorsGrp,
            "subcontractorsStats" => $subcontractorsStats,
            "filter" => $filter,
            "type" => $type->getId()
        ]);
    }

    /**
     * @Route("/subcontractors/standardize/{id}", name="subcontractors_standardize")
     */
    public function subcontractorsStandardizeAction(Request $request, Security $security, Subcontractor $subcontractor)
    {
        $em = $this->getDoctrine()->getManager();

        $subcontractorStd = new SubcontractorStd();

        $subcontractorStd->setName($subcontractor->getName());
        $subcontractorStd->setType($subcontractor->getType());
        $subcontractorStd->setContactFirstName($subcontractor->getContactFirstName());
        $subcontractorStd->setContactLastName($subcontractor->getContactLastName());
        $subcontractorStd->setContactPhone($subcontractor->getContactPhone());
        $subcontractorStd->setContactEmail($subcontractor->getContactEmail());
        $subcontractorStd->setPrivacyPolicyLink($subcontractor->getPrivacyPolicyLink());
        $subcontractorStd->setDate($subcontractor->getDate());
        $subcontractorStd->setEditDate($subcontractor->getEditDate());
        $subcontractorStd->setConformity($subcontractor->getConformity());
        $subcontractorStd->setDocuments($subcontractor->getDocuments());

        $token = $security->getToken();

        if ($token instanceof SwitchUserToken) {
            $impersonatorUser = $token->getOriginalToken()->getUser();
            if ($impersonatorUser) {
                if ($impersonatorUser->getManager()) {
                    $originalManager = $em->getRepository(Manager::class)->find($impersonatorUser->getManager()->getId());
                    if ($originalManager) {
                        $subcontractorStd->setManager($originalManager);
                    }
                }
            }
        }

        $em->persist($subcontractorStd);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Nouveau sous-traitant standard généré');
        return $this->redirectToRoute("user_data_processing");
    }

    /**
     * @Route("/subcontractors/export", name="subcontractors_export")
     */
    public function subcontractorsExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser(), "group" => false], ["name" => "ASC"]);

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Registre des sous-traitants");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/subcontractors.html.twig', [
                "subcontractors" => $subcontractors
            ]);

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

            $subcontractorsGrp = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());

            if (count($subcontractorsGrp)) {
                $pdf->AddPage('L', 'A4');

                $html = $this->renderView('user/pdf/subcontractors_grp.html.twig', [
                    "subcontractors" => $subcontractorsGrp
                ]);

                $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            }

            $filename = 'Registre_des_sous_traitants';

            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_subcontractors");
        }
    }

    /**
     * @Route("/subcontractors/exportxlsx", name="subcontractors_export_xlsx")
     */
    public function subcontractorsExportXlsxAction(Request $request, EntityManagerInterface $em)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $spreadsheet = new Spreadsheet();

            $subcontractorsTypes = $em->getRepository(\App\Entity\SubcontractorType::class)->findAll();

            $i = 0;
            foreach ($subcontractorsTypes as $subcontractorsType) {
                $i++;

                $newWorkSheet = new Worksheet($spreadsheet, $subcontractorsType->getLibelle());
                $spreadsheet->addSheet($newWorkSheet, $i);

                $spreadsheet->setActiveSheetIndex($i);
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue('A1', 'Type');
                $sheet->setCellValue('B1', 'Société');
                $sheet->setCellValue('C1', 'Typologie');
                $sheet->setCellValue('D1', 'Traitements');
                $sheet->setCellValue('E1', 'Contact');
                $sheet->setCellValue('F1', 'Tél');
                $sheet->setCellValue('G1', 'Mail');
                $sheet->setCellValue('H1', 'conformité');
                $sheet->setCellValue('I1', 'Doc');
                $sheet->setCellValue('J1', 'Date');

                $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser(), "group" => false, "subcontractorType" => $subcontractorsType], ["name" => "ASC"]);
                $j = 1;
                foreach ($subcontractors as $subcontractor) {
                    $j++;

                    $treatmentsArray = [];
                    foreach ($subcontractor->getTreatments() as $treatment) {
                        if ($treatment->getUser()->getId() == $this->getUser()->getUser()->getId()) {
                            $str = strval($treatment->getNumber());
                            $strLen = strlen($str);
                            $maxLen = 3;
                            if ($strLen < $maxLen) {
                                for ($k = $strLen; $k < $maxLen; $k++) {
                                    $str = "0".$str;
                                }
                            }

                            $treatmentsArray[] = "T".$str;
                        }
                    }

                    $documentsArray = [];
                    foreach ($subcontractor->getDocuments() as $document) {
                        $documentsArray[] = $document->getName();
                    }

                    $sheet->setCellValue('A' . $j, $subcontractor->getSubcontractorType()->getLibelle());
                    $sheet->setCellValue('B' . $j, $subcontractor->getName());
                    $sheet->setCellValue('C' . $j, $subcontractor->getType());
                    $sheet->setCellValue('D' . $j, implode(" | ", $treatmentsArray));
                    $sheet->setCellValue('E' . $j, $subcontractor->getContactLastName()." ".$subcontractor->getContactFirstName());
                    $sheet->setCellValue('F' . $j, $subcontractor->getContactPhone());
                    $sheet->setCellValue('G' . $j, $subcontractor->getContactEmail());
                    $sheet->setCellValue('H' . $j, $subcontractor->getConformity()->getLibelle());
                    $sheet->setCellValue('I' . $j, implode(" | ", $documentsArray));
                    $sheet->setCellValue('J' . $j, $subcontractor->getEditDate()->format("d/m/Y"));
                }
            }

            $i++;

            $newWorkSheet = new Worksheet($spreadsheet, "Groupe");
            $spreadsheet->addSheet($newWorkSheet, $i);

            $spreadsheet->setActiveSheetIndex($i);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Type');
            $sheet->setCellValue('B1', 'Société');
            $sheet->setCellValue('C1', 'Typologie');
            $sheet->setCellValue('D1', 'Traitements');
            $sheet->setCellValue('E1', 'Contact');
            $sheet->setCellValue('F1', 'Tél');
            $sheet->setCellValue('G1', 'Mail');
            $sheet->setCellValue('H1', 'conformité');
            $sheet->setCellValue('I1', 'Doc');
            $sheet->setCellValue('J1', 'Date');

            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());
            $j = 1;
            foreach ($subcontractors as $subcontractor) {
                $j++;

                $treatmentsArray = [];
                foreach ($subcontractor->getTreatments() as $treatment) {
                    if ($treatment->getUser()->getId() == $this->getUser()->getUser()->getId()) {
                        $str = strval($treatment->getNumber());
                        $strLen = strlen($str);
                        $maxLen = 3;
                        if ($strLen < $maxLen) {
                            for ($i = $strLen; $i < $maxLen; $i++) {
                                $str = "0".$str;
                            }
                        }

                        $treatmentsArray[] = "T".$str;
                    }
                }

                $documentsArray = [];
                foreach ($subcontractor->getDocuments() as $document) {
                    $documentsArray[] = $document->getName();
                }

                $sheet->setCellValue('A' . $j, $subcontractor->getSubcontractorType()->getLibelle());
                $sheet->setCellValue('B' . $j, $subcontractor->getName());
                $sheet->setCellValue('C' . $j, $subcontractor->getType());
                $sheet->setCellValue('D' . $j, implode(" | ", $treatmentsArray));
                $sheet->setCellValue('E' . $j, $subcontractor->getContactLastName()." ".$subcontractor->getContactFirstName());
                $sheet->setCellValue('F' . $j, $subcontractor->getContactPhone());
                $sheet->setCellValue('G' . $j, $subcontractor->getContactEmail());
                $sheet->setCellValue('H' . $j, $subcontractor->getConformity()->getLibelle());
                $sheet->setCellValue('I' . $j, implode(" | ", $documentsArray));
                $sheet->setCellValue('J' . $j, $subcontractor->getEditDate()->format("d/m/Y"));
            }

            $spreadsheet->removeSheetByIndex(0);

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse();
            $response->setCallback(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="export_sous_traitance.xlsx"');
            $response->headers->set('Cache-Control','max-age=0');
            return $response;
        } else {
            return $this->redirectToRoute("user_subcontractors", ["type" => 1]);
        }
    }

    /**
     * @Route("/subcontractors/add", name="subcontractors_add")
     */
    public function subcontractorsAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $subcontractor = new Subcontractor();

        $defaultSubcontractorType = $this->getDoctrine()->getRepository(\App\Entity\SubcontractorType::class)->findOneBy(["code" => "SOUS_TRAITANT"]);
        $subcontractor->setSubcontractorType($defaultSubcontractorType);

        $form = $this->createForm(SubcontractorType::class, $subcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $fromStd = false;
        $subcontractorStd = null;

        if (isset($_GET['std'])) {
            $subcontractorStd = $this->getDoctrine()->getRepository(SubcontractorStd::class)->find($_GET['std']);
            if ($subcontractorStd) {
                $fromStd = true;

                $form['name']->setData($subcontractorStd->getName());
                $form['type']->setData($subcontractorStd->getType());
                $form['contactFirstName']->setData($subcontractorStd->getContactFirstName());
                $form['contactLastName']->setData($subcontractorStd->getContactLastName());
                $form['contactPhone']->setData($subcontractorStd->getContactPhone());
                $form['contactEmail']->setData($subcontractorStd->getContactEmail());
                $form['privacyPolicyLink']->setData($subcontractorStd->getPrivacyPolicyLink());
                $form['conformity']->setData($subcontractorStd->getConformity());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $subcontractor->setDate($now);
            $subcontractor->setEditDate($now);
            $subcontractor->setUser($this->getUser()->getUser());

            /*if ($this->getUser()->getUser()->isMainGroupAgency()) {
                $subcontractor->setGroup($form["group"]->getData());
            }*/

            $em->persist($subcontractor);
            $em->flush();

            if ($fromStd) {
                if ($subcontractorStd) {
                    $filesystem = new Filesystem();
                    foreach ($subcontractorStd->getDocuments() as $document) {
                        if (isset($_POST["appbundle_subcontractor_documents_".$document->getId()]) && !empty($_POST["appbundle_subcontractor_documents_".$document->getId()])) {
                            $fileName = $document->getFilename();
                            $childFileName = $this->getUser()->getUser()->getId()."_".$fileName;

                            $filesystem->copy($this->getParameter('documents_directory').$fileName, $this->getParameter('documents_directory').$childFileName);

                            $newDocument = new UserDocument();
                            $newDocument->setName($document->getName());
                            $newDocument->setFilename($childFileName);
                            $newDocument->setUserFilename($document->getUserFilename());
                            $newDocument->setUser($this->getUser()->getUser());
                            $newDocument->setSubcontractor($subcontractor);

                            $em->persist($newDocument);
                            $em->flush();
                        }
                    }
                }
            }

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $subcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouveau sous-traitant: ".$subcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau sous-traitant client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau sous-traitant client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Nouveau sous-traitant ajouté');
            return $this->redirectToRoute("user_subcontractors", ["type" => $subcontractor->getSubcontractorType()->getId()]);
        }

        $subcontractorsStd = $this->getDoctrine()->getRepository(SubcontractorStd::class)->findForUser($this->getUser()->getUser());

        return $this->render('user/subcontractors_add.html.twig', [
            "form" => $form->createView(),
            "subcontractorsStd" => $subcontractorsStd,
            "fromStd" => $fromStd,
            "subcontractorStd" => $subcontractorStd
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/edit", name="subcontractors_edit")
     */
    public function subcontractorsEditAction(Request $request, SendEmailService $sendEmailService, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(SubcontractorType::class, $subcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $subcontractor->setEditDate(new \DateTime("now"));

            $em->flush();

            foreach ($subcontractor->getDocuments() as $document) {
                if (isset($_POST["appbundle_subcontractor_documents_".$document->getId()]) && !empty($_POST["appbundle_subcontractor_documents_".$document->getId()])) {
                    if ($document->getName() != $_POST["appbundle_subcontractor_documents_".$document->getId()]) {
                        $document->setTitle($_POST["appbundle_subcontractor_documents_".$document->getId()]);
                        $em->flush();
                    }
                }
            }

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $subcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié le sous-traitant ".$subcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Sous-traitant client modifié",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Sous-traitant client modifié",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Sous-traitant mis à jour');
            return $this->redirectToRoute("user_subcontractors", ["type" => $subcontractor->getSubcontractorType()->getId()]);
        }

        return $this->render('user/subcontractors_edit.html.twig', [
            "subcontractor" => $subcontractor,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/copy", name="subcontractors_copy")
     */
    public function subcontractorsCopyAction(Request $request, SendEmailService $sendEmailService, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $newSubcontractor = clone $subcontractor;
        $newSubcontractor->setId(null);
        $newSubcontractor->getDocuments()->clear();

        $form = $this->createForm(SubcontractorType::class, $newSubcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $newSubcontractor->setDate($now);
            $newSubcontractor->setEditDate($now);
            $newSubcontractor->setUser($this->getUser()->getUser());

            $em->persist($newSubcontractor);
            $em->flush();

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $newSubcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($newSubcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouveau sous-traitant: ".$newSubcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau sous-traitant client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau sous-traitant client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Sous-traitant dupliqué');
            return $this->redirectToRoute("user_subcontractors", ["type" => $newSubcontractor->getSubcontractorType()->getId()]);
        }

        return $this->render('user/subcontractors_copy.html.twig', [
            "subcontractor" => $subcontractor,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/delete", name="subcontractors_delete")
     */
    public function subcontractorsDeleteAction(Request $request, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $typeId = $subcontractor->getSubcontractorType()->getId();

        $em = $this->getDoctrine()->getManager();

        $em->remove($subcontractor);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Sous-traitant supprimé');
        return $this->redirectToRoute("user_subcontractors", ["type" => $typeId]);
    }

    /**
     * @Route("/subcontractors/deletedoc/{subcontractor}/{document}", name="subcontractor_deletedoc")
     */
    public function subcontractorsDeleteDocAction(Request $request, Subcontractor $subcontractor, UserDocument $userDocument)
    {
        $em = $this->getDoctrine()->getManager();

        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }
        if ($userDocument->getSubcontractor() == null || $userDocument->getSubcontractor()->getId() != $subcontractor->getId()) {
            throw new NotFoundHttpException();
        }

        $em->remove($userDocument);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute('user_subcontractors_edit', ['id' => $subcontractor->getId()]);
    }

    /**
     * @Route("/systems", name="systems")
     */

  
    
        // Initialize system categories

        public function systemsAction(Request $request)
        {
            // Query systems for the current user
            if ($this->getUser()->getUser()->getParentUser()) {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)
                    ->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)
                    ->findBy(["user" => $this->getUser()->getUser()]);
            }
        
            // Initialize system categories
            $systems = [
                "computing" => [
                    "network" => [],
                    "security" => [],
                    "administration" => [],
                    "device" => [],
                    "software" => [],
                    "server" => [],
                ],
                "physical" => [
                    "partitioning" => [],
                    "information" => [],
                ],
                "action" => [
                    "minimization" => [],
                    "anonymization" => [],
                    "pseudonymization" => [],
                    "sensitization" => [],
                    "supervision" => [],
                    "destruction" => [],
                ],
                "supplier" => [
                    "supplier" => []
                ]
            ];
        
            // Serialize systems for frontend
            $systemsJs = [];
            $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
            foreach ($systemsQuery as $system) {
                $systems[$system->getType()][$system->getSubtype()][] = $system;
                $systemsJs[$system->getId()] = json_decode($serializer->serialize($system, 'json', [
                    "attributes" => ['id', 'name', 'data', 'type', 'subtype'],
                    "circular_reference_handler" => fn($object) => $object->getId()
                ]), true);
            }
        
            // Standard systems structure
            $systemsStdQuery = $this->getDoctrine()->getRepository(SystemStd::class)->findAll();
            $systemsStd = [
                "computing" => [
                    "network" => ["label" => "Réseau", "items" => [], "icon" => "fa-print"],
                    "security" => ["label" => "Sécurité", "items" => [], "icon" => "fa-shield"],
                    "administration" => ["label" => "Administration", "items" => [], "icon" => "fa-users"],
                    "device" => ["label" => "Périphérique", "items" => [], "icon" => "fa-desktop"],
                    "software" => ["label" => "Logiciel", "items" => [], "icon" => "fa-window-maximize"],
                    "server" => ["label" => "Serveur", "items" => [], "icon" => "fa-server"],
                ],
                "physical" => [
                    "partitioning" => ["label" => "Cloisonnement", "items" => [], "icon" => "fa-home"],
                    "information" => ["label" => "Information", "items" => [], "icon" => "fa-lightbulb-o"],
                ],
                "action" => [
                    "minimization" => ["label" => "Minimisation", "items" => [], "icon" => "fa-user"],
                    "anonymization" => ["label" => "Anonymisation", "items" => [], "icon" => "fa-user-secret"],
                    "pseudonymization" => ["label" => "Pseudonymisation", "items" => [], "icon" => "fa-question-circle-o"],
                    "sensitization" => ["label" => "Sensibilisation", "items" => [], "icon" => "fa-exclamation-triangle"],
                    "supervision" => ["label" => "Contrôle", "items" => [], "icon" => "fa-search"],
                    "destruction" => ["label" => "Destruction", "items" => [], "icon" => "fa-trash-o"],
                ],
                "supplier" => [
                    "supplier" => ["label" => "Prestataires", "items" => [], "icon" => "fa-calendar-check-o"],
                ]
            ];
            foreach ($systemsStdQuery as $system) {
                $systemsStd[$system->getType()][$system->getSubtype()]['items'][] = $system;
            }
        
            // Build the mind map
            $mindMapHeight = 0;
            $addedType = $request->query->get('addedType');
            $addedSubtype = $request->query->get('addedSubtype');
            $mindMap = [
                "meta" => [
                    "name" => "Cartographie SI",
                    "author" => "myDigitplace",
                    "version" => "1.0"
                ],
                "format" => "node_tree",
                "data" => [
                    "id" => "root",
                    "topic" => "
                        <div class='border border1'><div class='circle'></div></div>
                        <div class='border border2'><div class='circle'></div></div>
                        <div class='border border3'><div class='circle'></div></div>
                        <div class='border border4'><div class='circle'></div></div>
                        <div class='node-content'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 16 16'><path fill='currentColor' fill-rule='evenodd' d='M6.146 2.153a.5.5 0 0 1 .354-.146h3a.5.5 0 0 1 .5.5V4.51a.5.5 0 0 1-.5.5H8.497V7h4.5a.5.5 0 0 1 .5.5V10H14.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V8h-4v2H9.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V8h-4v2H4.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V7.5a.5.5 0 0 1 .5-.5h4.5V5.01H6.5a.5.5 0 0 1-.5-.5V2.508a.5.5 0 0 1 .146-.354' clip-rule='evenodd'/></svg>
                            <div class='text-wrapper'>Système d'information</div>
                        </div>",
                    "expanded" => true,
                    "children" => []
                ]
            ];
        
            // Add custom French names for specific nodes
            $customNames = [
                "computing" => "Informatique",
                "physical" => "Physique",
                "action" => "Action",
                "supplier" => "Prestataires de SI"
            ];
        
            foreach ($systemsStd as $type => $categories) {
                $node = [
                    "id" => $type,
                    "topic" => "
                        <div class='border border1'><div class='circle'></div></div>
                        <div class='border border2'><div class='circle'></div></div>
                        <div class='border border3'><div class='circle'></div></div>
                        <div class='border border4'><div class='circle'></div></div>
                        <div class='node-content'>
                            <i class='fa " . $categories[array_key_first($categories)]['icon'] . "'></i>
                            <div class='text-wrapper'>" . ($customNames[$type] ?? ucfirst($type)) . "</div>
                        </div> ",
                    "direction" => "right",
                    "expanded" => ($type === $addedType),
                    "children" => []
                ];
        
                foreach ($categories as $subtype => $details) {
                    $subnode = [
                        "id" => "{$type}_{$subtype}",
                        "topic" => "
                            <div class='border border1'><div class='circle'></div></div>
                            <div class='border border2'><div class='circle'></div></div>
                            <div class='border border3'><div class='circle'></div></div>
                            <div class='border border4'><div class='circle'></div></div>
                            <div class='node-content'>
                                <i class='fa " . $details["icon"] . "'></i>
                                <div class='text-wrapper'>" . $details["label"] . "</div>
                            </div> <span class='node-2-actions'><a href=\"".$this->generateUrl("user_systems_add")."?type=".$type."&subtype=".$subtype."\" class=\"btn btn-sm btn-rounded-circle btn-primary\"><i class=\"mdi mdi-plus\"></i></a></span>",
                        "expanded" => ($type === $addedType && $subtype === $addedSubtype),
                        "children" => []
                    ];
        
                    foreach ($systems[$type][$subtype] as $item) {
                        $subnode["children"][] = [
                            "id" => $item->getId(),
                            "topic" => "
                                <div class='border border1'><div class='circle'></div></div>
                                <div class='border border2'><div class='circle'></div></div>
                                <div class='border border3'><div class='circle'></div></div>
                                <div class='border border4'><div class='circle'></div></div>
                                <div class='node-content'>
                                   
                                    <div class='text-wrapper'>" . $item->getName() . "</div>
                                </div> <span class='node-3-actions options'><a href=\"".$this->generateUrl("user_systems_edit", ["id" => $item->getId()])."\" class=\"btn edit my-1 mr-1\"><i class=\"mdi mdi-circle-edit-outline\"></i></a><a href=\"".$this->generateUrl("user_systems_delete", ["id" => $item->getId()])."\" class=\"btn delete my-1\"  onclick=\"return confirm('Confirmer la suppression de cet élément ?');\"><i class=\"mdi mdi-close\"></i></a></span>",
                        ];
                    }
        
                    $node["children"][] = $subnode;
                }
        
                $mindMap["data"]["children"][] = $node;
            }
        
            return $this->render('user/systems.html.twig', [
                "systems" => $systems,
                "systemsStd" => $systemsStd,
                "mindMap" => $mindMap,
                "mindMapHeight" => max(400, $mindMapHeight * 50),
                "systemsJs" => $systemsJs,
            ]);
        }
        
        
        
        
        
    
    

            
    

    /**
     * @Route("/systems/standardize/{id}", name="systems_standardize")
     */
    public function systemsStandardizeAction(Request $request, Security $security, System $system)
    {
        $em = $this->getDoctrine()->getManager();

        $systemStd = new SystemStd();

        $systemStd->setName($system->getName());
        $systemStd->setData($system->getData());
        $systemStd->setType($system->getType());
        $systemStd->setSubtype($system->getSubtype());

        $token = $security->getToken();

        if ($token instanceof SwitchUserToken) {
            $impersonatorUser = $token->getOriginalToken()->getUser();
            if ($impersonatorUser) {
                if ($impersonatorUser->getManager()) {
                    $originalManager = $em->getRepository(Manager::class)->find($impersonatorUser->getManager()->getId());
                    if ($originalManager) {
                        $systemStd->setManager($originalManager);
                    }
                }
            }
        }

        $em->persist($systemStd);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Nouvel élément de cartographie standard généré');
        return $this->redirectToRoute("user_systems");
    }

    /**
     * @Route("/systems/export", name="systems_export")
     */
    public function systemsExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            if ($this->getUser()->getUser()->getParentUser()) {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser()]);
            }

            $systems = [
                "computing" => [
                    "network" => [],
                    "security" => [],
                    "administration" => [],
                    "device" => [],
                    "software" => [],
                    "server" => [],
                ],
                "physical" => [
                    "partitioning" => [],
                    "information" => [],
                ],
                "action" => [
                    "minimization" => [],
                    "anonymization" => [],
                    "pseudonymization" => [],
                    "sensitization" => [],
                    "supervision" => [],
                    "destruction" => [],
                ],
                "supplier" => [
                    "supplier" => []
                ]
            ];

            foreach ($systemsQuery as $system) {
                $systems[$system->getType()][$system->getSubtype()][] = $system;
            }

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Cartographie du SI");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/systems.html.twig', [
                "systems" => $systems
            ]);

            $filename = 'Cartographie_du_SI';

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_systems");
        }
    }

    /**
     * @Route("/systems/export/{type}", name="systems_export_excel")
     */
    public function systemsExportExcelAction(Request $request, $type)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            if ($this->getUser()->getUser()->getParentUser()) {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser()]);
            }

            $systems = [
                "computing" => [
                    "network" => [],
                    "security" => [],
                    "administration" => [],
                    "device" => [],
                    "software" => [],
                    "server" => [],
                ],
                "physical" => [
                    "partitioning" => [],
                    "information" => [],
                ],
                "action" => [
                    "minimization" => [],
                    "anonymization" => [],
                    "pseudonymization" => [],
                    "sensitization" => [],
                    "supervision" => [],
                    "destruction" => [],
                ],
                "supplier" => [
                    "supplier" => []
                ]
            ];

            foreach ($systemsQuery as $system) {
                $systems[$system->getType()][$system->getSubtype()][] = $system;
            }

            $spreadsheet = new Spreadsheet();

            $translateSubtypes = [
                "network" => "Réseau",
                "security" => "Sécurité",
                "administration" => "Administration",
                "device" => "Périphérique",
                "software" => "Logiciel",
                "server" => "Serveur",
                "partitioning" => "Cloisonnement",
                "minimization" => "Minimisation",
                "anonymization" => "Anonymisation",
                "pseudonymization" => "Pseudonymisation",
                "sensitization" => "Sensibilisation et formation",
                "information" => "Information",
                "supervision" => "Contrôle",
                "destruction" => "Destruction et suppression",
                "supplier" => "Prestataires du SI"
            ];

            $i = 0;
            foreach ($systems[$type] as $subtype => $systems) {
                $i++;

                $roomsWorkSheet = new Worksheet($spreadsheet, $translateSubtypes[$subtype]);
                $spreadsheet->addSheet($roomsWorkSheet, $i);

                $spreadsheet->setActiveSheetIndex($i);
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'Nom');

                switch ($subtype) {
                    case "network":
                        $sheet->setCellValue('C1', 'Type');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        $sheet->setCellValue('E1', 'Type de Wifi');
                        $sheet->setCellValue('F1', 'Protocole Wifi');
                        break;
                    case "security":
                    case "administration":
                    case "device":
                    case "software":
                    case "server":
                        $sheet->setCellValue('C1', 'Type');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        $sheet->setCellValue('E1', 'Date d\'achat');
                        $sheet->setCellValue('F1', 'N° d\'identification');
                        break;
                    case "partitioning":
                    case "minimization":
                    case "anonymization":
                    case "pseudonymization":
                    case "sensitization":
                    case "information":
                    case "supervision":
                    case "destruction":
                        $sheet->setCellValue('C1', 'Description');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        break;
                    case "supplier":
                        $sheet->setCellValue('C1', 'Type');
                        break;
                    default:
                        throw new NotFoundHttpException();
                }

                $j = 1;
                foreach ($systems as $system) {
                    $j++;
                    $sheet->setCellValue('A'.$j, $system->getId());
                    $sheet->setCellValue('B'.$j, $system->getName());
                    switch ($subtype) {
                        case "network":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            $sheet->setCellValue('E'.$j, isset($system->getData()[2])?$system->getData()[2]["value"]:null);
                            $sheet->setCellValue('F'.$j, isset($system->getData()[3])?$system->getData()[3]["value"]:null);
                            break;
                        case "security":
                        case "administration":
                        case "device":
                        case "software":
                        case "server":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            $sheet->setCellValue('E'.$j, isset($system->getData()[2])?$system->getData()[2]["value"]:null);
                            $sheet->setCellValue('F'.$j, isset($system->getData()[3])?$system->getData()[3]["value"]:null);
                            break;
                        case "partitioning":
                        case "minimization":
                        case "anonymization":
                        case "pseudonymization":
                        case "sensitization":
                        case "information":
                        case "supervision":
                        case "destruction":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            break;
                        case "supplier":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            break;
                        default:
                            throw new NotFoundHttpException();
                    }
                }
            }

            $spreadsheet->removeSheetByIndex(0);

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse();
            $response->setCallback(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="export_cartographie.xlsx"');
            $response->headers->set('Cache-Control','max-age=0');
            return $response;
        } else {
            return $this->redirectToRoute("user_systems");
        }
    }

    /**
     * @Route("/systems/add", name="systems_add")
     */
    public function systemsAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $types = [
            "computing" => [
                "network" => 4,
                "security" => 4,
                "administration" => 4,
                "device" => 4,
                "software" => 4,
                "server" => 4,
            ],
            "physical" => [
                "partitioning" => 2,
                "information" => 2,
            ],
            "action" => [
                "minimization" => 2,
                "anonymization" => 2,
                "pseudonymization" => 2,
                "sensitization" => 2,
                "supervision" => 2,
                "destruction" => 2,
            ],
            "supplier" => [
                "supplier" => 1
            ]
        ];

        $type = null;
        $subtype = null;
        $fromStd = false;

        $systemsStd = [];

        if (isset($_GET['std'])) {
            $systemStd = $this->getDoctrine()->getRepository(SystemStd::class)->find($_GET['std']);
            if ($systemStd) {
                $type = $systemStd->getType();
                $subtype = $systemStd->getSubtype();
                $fromStd = true;
            }
        } else {
            if (!$_GET['type'] || !$_GET['subtype']) {
                throw new NotFoundHttpException();
            }

            if (!key_exists($_GET['type'], $types)) {
                throw new NotFoundHttpException();
            }

            if (!key_exists($_GET['subtype'], $types[$_GET['type']])) {
                throw new NotFoundHttpException();
            }

            $type = $_GET['type'];
            $subtype = $_GET['subtype'];

            $systemsStd = $this->getDoctrine()->getRepository(SystemStd::class)->findBy(["type" => $type, "subtype" => $subtype], ["name" => "ASC"]);
        }

        $system = new System();

        $form = $this->createForm(SystemType::class, $system);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "Cet élément de cartographie appartient au groupe",
                'required' => false,
            ]);

            if ($request->get("group")) {
                $form["group"]->setData(true);
            }
        }

        $defaultValues = [];
        for ($i=0; $i < $types[$type][$subtype]; $i++) {
            $defaultValues[$i] = null;
        }

        if ($fromStd) {
            $form['name']->setData($systemStd->getName());
            for ($i=0; $i < $types[$type][$subtype]; $i++) {
                $defaultValues[$i] = isset($systemStd->getData()[$i])?$systemStd->getData()[$i]['value']:null;
            }
        }

        switch ($subtype) {
            case "network":
                $form->add('field1', ChoiceType::class, [
                        'attr' => [
                            'placeholder' => 'Type'
                        ],
                        'placeholder' => 'Type',
                        'label' => 'Type',
                        'data' => $defaultValues[0],
                        'choices' => [
                            "Filaire" => "Filaire",
                            "Sans-fil" => "Sans-fil",
                        ],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => $defaultValues[1],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field3', ChoiceType::class, [
                        'attr' => [
                            'placeholder' => 'Type'
                        ],
                        'placeholder' => 'Type',
                        'label' => 'Si Wifi, sélectionnez le type',
                        'data' => $defaultValues[2],
                        'choices' => [
                            "Privé" => "Privé",
                            "Public" => "Public",
                        ],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'Protocole'
                        ],
                        'label' => 'Si Wifi, quel protocole ?',
                        'data' => $defaultValues[3],
                        'required' => false,
                        'mapped' => false
                    ])
                ;
                break;
            case "security":
            case "administration":
            case "device":
            case "software":
            case "server":
                $form->add('field1', TextType::class, [
                        'attr' => [
                            'placeholder' => 'Type'
                        ],
                        'label' => 'Type',
                        'data' => $defaultValues[0],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => $defaultValues[1],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field3', TextType::class, [
                        'attr' => [
                            'placeholder' => '__/__/____',
                            'data-mask' => '00/00/0000',
                            'data-mask-clearifnotmatch' => 'true'
                        ],
                        'label' => 'Date d\'achat',
                        'data' => $defaultValues[2],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'N° d’identification'
                        ],
                        'label' => 'N° d’identification',
                        'data' => $defaultValues[3],
                        'required' => false,
                        'mapped' => false
                    ]);
                break;
            case "partitioning":
            case "minimization":
            case "anonymization":
            case "pseudonymization":
            case "sensitization":
            case "information":
            case "supervision":
            case "destruction":
                $form->add('field1', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Description'
                        ],
                        'label' => 'Description',
                        'data' => $defaultValues[0],
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => $defaultValues[1],
                        'required' => false,
                        'mapped' => false
                    ]);
                break;
            case "supplier":
                $form->add('field1', TextType::class, [
                    'attr' => [
                        'placeholder' => 'Type'
                    ],
                    'label' => 'Type',
                    'data' => $defaultValues[0],
                    'required' => false,
                    'mapped' => false
                ]);
                break;
            default:
                throw new NotFoundHttpException();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $data = [];

            switch ($subtype) {
                case "network":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    $data[] = [
                        "title" => 'Type de Wifi',
                        "value" => $form['field3']->getData()
                    ];
                    $data[] = [
                        "title" => 'Protocole Wifi',
                        "value" => $form['field4']->getData()
                    ];
                    ;
                    break;
                case "security":
                case "administration":
                case "device":
                case "software":
                case "server":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    $data[] = [
                        "title" => 'Date d\'achat',
                        "value" => $form['field3']->getData()
                    ];
                    $data[] = [
                        "title" => 'N° d’identification',
                        "value" => $form['field4']->getData()
                    ];
                    break;
                case "partitioning":
                case "minimization":
                case "anonymization":
                case "pseudonymization":
                case "sensitization":
                case "information":
                case "supervision":
                case "destruction":
                    $data[] = [
                        "title" => 'Description',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    break;
                case "supplier":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    break;
                default:
                    throw new NotFoundHttpException();
            }

            $system->setData($data);
            $system->setType($type);
            $system->setSubtype($subtype);
            $system->setUser($this->getUser()->getUser());

            $em->persist($system);
            $em->flush();

            if ($system->isAutoApplyToTreatments()) {
                $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

                foreach ($treatments as $treatment) {
                    $treatment->getSystems()->add($system);
                    $em->flush();
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouvel élément de cartographie du SI: ".$system->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouvel élément de cartographie du SI client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouvel élément de cartographie du SI client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Mise à jour de la cartographie du système');

            $url = null;
            if ($system->isGroup()) {
                $url = $this->generateUrl("user_systems_group");
            } else {
                $url = $this->generateUrl("user_systems");
            }
            if ($system->getType() && $system->getSubtype()) {
                $url .= "?addedType=".$system->getType()."&addedSubtype=".$system->getSubtype();
            }
            return $this->redirect($url);
        }

        return $this->render('user/systems_add.html.twig', [
            "form" => $form->createView(),
            "fields" => $types[$type][$subtype],
            "systemsStd" => $systemsStd
        ]);
    }

    /**
     * @Route("/systems/{id}/edit", name="systems_edit")
     */
    public function systemsEditAction(Request $request, SendEmailService $sendEmailService, System $system)
    {
        if ($system->getUser()->getId() != $this->getUser()->getUser()->getId()){
            throw new NotFoundHttpException();
        }

        $current_isAutoApplyToTreatments = $system->isAutoApplyToTreatments();

        $types = [
            "computing" => [
                "network" => 4,
                "security" => 4,
                "administration" => 4,
                "device" => 4,
                "software" => 4,
                "server" => 4,
            ],
            "physical" => [
                "partitioning" => 2,
                "information" => 2,
            ],
            "action" => [
                "minimization" => 2,
                "anonymization" => 2,
                "pseudonymization" => 2,
                "sensitization" => 2,
                "supervision" => 2,
                "destruction" => 2,
            ],
            "supplier" => [
                "supplier" => 1
            ]
        ];

        $form = $this->createForm(SystemType::class, $system);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "Cet élément de cartographie appartient au groupe",
                'required' => false,
            ]);
        }

        switch ($system->getSubtype()) {
            case "network":
                $form->add('field1', ChoiceType::class, [
                    'attr' => [
                        'placeholder' => 'Type'
                    ],
                    'placeholder' => 'Type',
                    'label' => 'Type',
                    'choices' => [
                        "Filaire" => "Filaire",
                        "Sans-fil" => "Sans-fil",
                    ],
                    'data' => isset($system->getData()[0])?$system->getData()[0]["value"]:null,
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => isset($system->getData()[1])?$system->getData()[1]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field3', ChoiceType::class, [
                        'attr' => [
                            'placeholder' => 'Type'
                        ],
                        'placeholder' => 'Type',
                        'label' => 'Si Wifi, sélectionnez le type',
                        'choices' => [
                            "Privé" => "Privé",
                            "Public" => "Public",
                        ],
                        'data' => isset($system->getData()[2])?$system->getData()[2]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'Protocole'
                        ],
                        'label' => 'Si Wifi, quel protocole ?',
                        'data' => isset($system->getData()[3])?$system->getData()[3]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ])
                ;
                break;
            case "security":
            case "administration":
            case "device":
            case "software":
            case "server":
                $form->add('field1', TextType::class, [
                    'attr' => [
                        'placeholder' => 'Type'
                    ],
                    'label' => 'Type',
                    'data' => isset($system->getData()[0])?$system->getData()[0]["value"]:null,
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => isset($system->getData()[1])?$system->getData()[1]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field3', TextType::class, [
                        'attr' => [
                            'placeholder' => '__/__/____',
                            'data-mask' => '00/00/0000',
                            'data-mask-clearifnotmatch' => 'true'
                        ],
                        'label' => 'Date d\'achat',
                        'data' => isset($system->getData()[2])?$system->getData()[2]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'N° d’identification'
                        ],
                        'label' => 'N° d’identification',
                        'data' => isset($system->getData()[3])?$system->getData()[3]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ]);
                break;
            case "partitioning":
            case "minimization":
            case "anonymization":
            case "pseudonymization":
            case "sensitization":
            case "information":
            case "supervision":
            case "destruction":
                $form->add('field1', TextareaType::class, [
                    'attr' => [
                        'placeholder' => 'Description'
                    ],
                    'label' => 'Description',
                    'data' => isset($system->getData()[0])?$system->getData()[0]["value"]:null,
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
                        'data' => isset($system->getData()[1])?$system->getData()[1]["value"]:null,
                        'required' => false,
                        'mapped' => false
                    ]);
                break;
            case "supplier":
                $form->add('field1', TextType::class, [
                    'attr' => [
                        'placeholder' => 'Type'
                    ],
                    'label' => 'Type',
                    'data' => isset($system->getData()[0])?$system->getData()[0]["value"]:null,
                    'required' => false,
                    'mapped' => false
                ]);
                break;
            default:
                throw new NotFoundHttpException();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $data = [];

            switch ($system->getSubtype()) {
                case "network":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    $data[] = [
                        "title" => 'Type de Wifi',
                        "value" => $form['field3']->getData()
                    ];
                    $data[] = [
                        "title" => 'Protocole Wifi',
                        "value" => $form['field4']->getData()
                    ];
                    ;
                    break;
                case "security":
                case "administration":
                case "device":
                case "software":
                case "server":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    $data[] = [
                        "title" => 'Date d\'achat',
                        "value" => $form['field3']->getData()
                    ];
                    $data[] = [
                        "title" => 'N° d’identification',
                        "value" => $form['field4']->getData()
                    ];
                    break;
                case "partitioning":
                case "minimization":
                case "anonymization":
                case "pseudonymization":
                case "sensitization":
                case "information":
                case "supervision":
                case "destruction":
                    $data[] = [
                        "title" => 'Description',
                        "value" => $form['field1']->getData()
                    ];
                    $data[] = [
                        "title" => 'Informations complémentaires',
                        "value" => $form['field2']->getData()
                    ];
                    break;
                case "supplier":
                    $data[] = [
                        "title" => 'Type',
                        "value" => $form['field1']->getData()
                    ];
                    break;
                default:
                    throw new NotFoundHttpException();
            }

            $system->setData($data);

            $em->flush();

            if (!$current_isAutoApplyToTreatments && $system->isAutoApplyToTreatments()) {
                $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

                foreach ($treatments as $treatment) {
                    if (!$treatment->getSystems()->contains($system)) {
                        $treatment->getSystems()->add($system);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié l'élément de cartographie du SI ".$system->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Élément de cartographie du SI client modifié",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Élément de cartographie du SI client modifié",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Mise à jour de la cartographie du système');

            $url = null;
            if ($system->isGroup()) {
                $url = $this->generateUrl("user_systems_group");
            } else {
                $url = $this->generateUrl("user_systems");
            }
            if ($system->getType() && $system->getSubtype()) {
                $url .= "?addedType=".$system->getType()."&addedSubtype=".$system->getSubtype();
            }
            return $this->redirect($url);
        }

        return $this->render('user/systems_edit.html.twig', [
            "form" => $form->createView(),
            "fields" => $types[$system->getType()][$system->getSubtype()]
        ]);
    }

    /**
     * @Route("/systems/{id}/delete", name="systems_delete")
     */
    public function systemsDeleteAction(Request $request, System $system)
    {
        if ($system->getUser()->getId() != $this->getUser()->getUser()->getId()){
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($system);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Mise à jour de la cartographie du système');
        return $this->redirectToRoute("user_systems");
    }

    /**
     * @Route("/systemsgroup", name="systems_group")
     */
    public function systemsGroupAction(Request $request)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }
    
        $systemsQuery = $this->getDoctrine()->getRepository(System::class)
            ->findBy(["user" => $this->getUser()->getUser(), "group" => true]);
    
        $systems = [
            "computing" => [
                "network" => [],
                "security" => [],
                "administration" => [],
                "device" => [],
                "software" => [],
                "server" => [],
            ],
            "physical" => [
                "partitioning" => [],
                "information" => [],
            ],
            "action" => [
                "minimization" => [],
                "anonymization" => [],
                "pseudonymization" => [],
                "sensitization" => [],
                "supervision" => [],
                "destruction" => [],
            ],
            "supplier" => [
                "supplier" => []
            ]
        ];
    
        $systemsJs = [];
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    
        foreach ($systemsQuery as $system) {
            $systems[$system->getType()][$system->getSubtype()][] = $system;
            $systemsJs[$system->getId()] = json_decode($serializer->serialize($system, 'json', [
                "attributes" => ['id', 'name', 'data', 'type', 'subtype'],
                "circular_reference_handler" => fn($object) => $object->getId()
            ]), true);
        }
    
        $systemsStdQuery = $this->getDoctrine()->getRepository(SystemStd::class)->findAll();
        $systemsStd = [
            "computing" => [
                "network" => ["label" => "Réseau", "items" => [], "icon" => "fa-print"],
                "security" => ["label" => "Sécurité", "items" => [], "icon" => "fa-shield"],
                "administration" => ["label" => "Administration", "items" => [], "icon" => "fa-users"],
                "device" => ["label" => "Périphérique", "items" => [], "icon" => "fa-desktop"],
                "software" => ["label" => "Logiciel", "items" => [], "icon" => "fa-window-maximize"],
                "server" => ["label" => "Serveur", "items" => [], "icon" => "fa-server"],
            ],
            "physical" => [
                "partitioning" => ["label" => "Cloisonnement", "items" => [], "icon" => "fa-calendar-check-o"],
                "information" => ["label" => "Information", "items" => [], "icon" => "fa-lightbulb-o"],
            ],
            "action" => [
                "minimization" => ["label" => "Minimisation", "items" => [], "icon" => "fa-user"],
                "anonymization" => ["label" => "Anonymisation", "items" => [], "icon" => "fa-user-secret"],
                "pseudonymization" => ["label" => "Pseudonymisation", "items" => [], "icon" => "fa-question-circle-o"],
                "sensitization" => ["label" => "Sensibilisation", "items" => [], "icon" => "fa-exclamation-triangle"],
                "supervision" => ["label" => "Contrôle", "items" => [], "icon" => "fa-search"],
                "destruction" => ["label" => "Destruction", "items" => [], "icon" => "fa-trash-o"],
            ],
            "supplier" => [
                "supplier" => ["label" => "Prestataires", "items" => [], "icon" => "fa-calendar-check-o"],
            ]
        ];
    
        foreach ($systemsStdQuery as $system) {
            $systemsStd[$system->getType()][$system->getSubtype()]['items'][] = $system;
        }
    
        $mindMapHeight = 0;
        $addedType = $request->query->get('addedType');
        $addedSubtype = $request->query->get('addedSubtype');
        $mindMap = [
            "meta" => [
                "name" => "Cartographie SI",
                "author" => "myDigitplace",
                "version" => "1.0"
            ],
            "format" => "node_tree",
            "data" => [
            "id" => "root",
            "topic" => " <div class='border border1'><div class='circle'></div></div>
                <div class='border border2'><div class='circle'></div></div>
                <div class='border border3'><div class='circle'></div></div>
                <div class='border border4'><div class='circle'></div></div>
                <div class='node-content'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 16 16'>
                        <path fill='currentColor' fill-rule='evenodd' d='M6.146 2.153a.5.5 0 0 1 .354-.146h3a.5.5 0 0 1 .5.5V4.51a.5.5 0 0 1-.5.5H8.497V7h4.5a.5.5 0 0 1 .5.5V10H14.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V8h-4v2H9.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V8h-4v2H4.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h.997V7.5a.5.5 0 0 1 .5-.5h4.5V5.01H6.5a.5.5 0 0 1-.5-.5V2.508a.5.5 0 0 1 .146-.354' clip-rule='evenodd'/>
                    </svg>
                    <div class='text-wrapper'>Système d'information</div>
                </div>",
            "expanded" => true,
            "children" => []
            ]
        ];
        $customNames = [
            "computing" => "Informatique",
            "physical" => "Physique",
            "action" => "Action",
            "supplier" => "Prestataires de SI"
        ];
        $key1 = 0;
        foreach ($systemsStd as $type => $categories) {
            $node = [
                "id" => $type,
                "topic" => " <div class='border border1'><div class='circle'></div></div>
                <div class='border border2'><div class='circle'></div></div>
                <div class='border border3'><div class='circle'></div></div>
                <div class='border border4'><div class='circle'></div></div>
                <div class='node-content'>
                    <i class='fa " . $categories[array_key_first($categories)]['icon'] . "'></i>
                    <div class='text-wrapper'>" . ($customNames[$type] ?? ucfirst($type)) . "</div>
                </div>",
                "direction" => "right",
                "expanded" => ($type === $addedType),
                "children" => []
            ];
    
            foreach ($categories as $subtype => $details) {
                $subNode = [
                    "id" => $type . "_" . $subtype,
                    "topic" => " <div class='border border1'><div class='circle'></div></div>
                    <div class='border border2'><div class='circle'></div></div>
                    <div class='border border3'><div class='circle'></div></div>
                    <div class='border border4'><div class='circle'></div></div>
                    <div class='node-content'>
                        <i class='fa " . $details["icon"] . "'></i>
                        <div class='text-wrapper'>" . $details["label"] . "</div>
                    </div> <span class='node-2-actions'><a href=\"".$this->generateUrl("user_systems_add")."?type=".$type."&subtype=".$subtype."\" class=\"btn btn-sm btn-rounded-circle btn-primary\"><i class=\"mdi mdi-plus\"></i></a></span>",
                    "children" => []
                ];
    
                foreach ($systems[$type][$subtype] as $system) {
                    $subNode["children"][] = [
                        "id" => $system->getId(),
                        "topic" => " 
                        <div class='border border1'><div class='circle'></div></div>
                        <div class='border border2'><div class='circle'></div></div>
                        <div class='border border3'><div class='circle'></div></div>
                        <div class='border border4'><div class='circle'></div></div>
                        <div class='node-content'>
                           
                            <div class='text-wrapper'>" . $system->getName() . "</div>
                            <span class='node-3-actions options'><a href=\"".$this->generateUrl("user_systems_edit", ["id" => $system->getId()])."\" class=\"btn btn-light my-1 mr-1\"><i class=\"mdi mdi-circle-edit-outline\"></i></a><a href=\"".$this->generateUrl("user_systems_delete", ["id" => $system->getId()])."\" class=\"btn btn-danger my-1\"  onclick=\"return confirm('Confirmer la suppression de cet élément ?');\"><i class=\"mdi mdi-close\"></i></a></span>
                        </div>"
                    ];
                }
    
                $node["children"][] = $subNode;
            }
    
            $mindMap["data"]["children"][] = $node;
            $key1++;
        }
    
        return $this->render('user/systemsgroup.html.twig', [
            "systems" => $systems,
            "systemsStd" => $systemsStd,
            "mindMap" => $mindMap,
            "mindMapHeight" => 12 * (38 * 1.5),
            "systemsJs" => $systemsJs,
        ]);
    }
    
    /**
     * @Route("/systemsgroup/export", name="systems_group_export")
     */
    public function systemsGroupExportAction(Request $request)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }

        if ($this->isPrintingAllowed($this->getUser())) {
            $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser(), "group" => true]);

            $systems = [
                "computing" => [
                    "network" => [],
                    "security" => [],
                    "administration" => [],
                    "device" => [],
                    "software" => [],
                    "server" => [],
                ],
                "physical" => [
                    "partitioning" => [],
                    "information" => [],
                ],
                "action" => [
                    "minimization" => [],
                    "anonymization" => [],
                    "pseudonymization" => [],
                    "sensitization" => [],
                    "supervision" => [],
                    "destruction" => [],
                ],
                "supplier" => [
                    "supplier" => []
                ]
            ];

            foreach ($systemsQuery as $system) {
                $systems[$system->getType()][$system->getSubtype()][] = $system;
            }

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Cartographie du SI");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/systems.html.twig', [
                "systems" => $systems,
                "group" => true
            ]);

            $filename = 'Cartographie_du_SI';

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_systems");
        }
    }

    /**
     * @Route("/systemsgroup/export/{type}", name="systems_group_export_excel")
     */
    public function systemsGroupExportExcelAction(Request $request, $type)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }

        if ($this->isPrintingAllowed($this->getUser())) {
            $systemsQuery = $this->getDoctrine()->getRepository(System::class)->findBy(["user" => $this->getUser()->getUser(), "group" => true]);

            $systems = [
                "computing" => [
                    "network" => [],
                    "security" => [],
                    "administration" => [],
                    "device" => [],
                    "software" => [],
                    "server" => [],
                ],
                "physical" => [
                    "partitioning" => [],
                    "information" => [],
                ],
                "action" => [
                    "minimization" => [],
                    "anonymization" => [],
                    "pseudonymization" => [],
                    "sensitization" => [],
                    "supervision" => [],
                    "destruction" => [],
                ],
                "supplier" => [
                    "supplier" => []
                ]
            ];

            foreach ($systemsQuery as $system) {
                $systems[$system->getType()][$system->getSubtype()][] = $system;
            }

            $spreadsheet = new Spreadsheet();

            $translateSubtypes = [
                "network" => "Réseau",
                "security" => "Sécurité",
                "administration" => "Administration",
                "device" => "Périphérique",
                "software" => "Logiciel",
                "server" => "Serveur",
                "partitioning" => "Cloisonnement",
                "minimization" => "Minimisation",
                "anonymization" => "Anonymisation",
                "pseudonymization" => "Pseudonymisation",
                "sensitization" => "Sensibilisation et formation",
                "information" => "Information",
                "supervision" => "Contrôle",
                "destruction" => "Destruction et suppression",
                "supplier" => "Prestataires du SI"
            ];

            $i = 0;
            foreach ($systems[$type] as $subtype => $systems) {
                $i++;

                $roomsWorkSheet = new Worksheet($spreadsheet, $translateSubtypes[$subtype]);
                $spreadsheet->addSheet($roomsWorkSheet, $i);

                $spreadsheet->setActiveSheetIndex($i);
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'Nom');

                switch ($subtype) {
                    case "network":
                        $sheet->setCellValue('C1', 'Type');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        $sheet->setCellValue('E1', 'Type de Wifi');
                        $sheet->setCellValue('F1', 'Protocole Wifi');
                        break;
                    case "security":
                    case "administration":
                    case "device":
                    case "software":
                    case "server":
                        $sheet->setCellValue('C1', 'Type');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        $sheet->setCellValue('E1', 'Date d\'achat');
                        $sheet->setCellValue('F1', 'N° d\'identification');
                        break;
                    case "partitioning":
                    case "minimization":
                    case "anonymization":
                    case "pseudonymization":
                    case "sensitization":
                    case "information":
                    case "supervision":
                    case "destruction":
                        $sheet->setCellValue('C1', 'Description');
                        $sheet->setCellValue('D1', 'Informations complémentaires');
                        break;
                    case "supplier":
                        $sheet->setCellValue('C1', 'Type');
                        break;
                    default:
                        throw new NotFoundHttpException();
                }

                $j = 1;
                foreach ($systems as $system) {
                    $j++;
                    $sheet->setCellValue('A'.$j, $system->getId());
                    $sheet->setCellValue('B'.$j, $system->getName());
                    switch ($subtype) {
                        case "network":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            $sheet->setCellValue('E'.$j, isset($system->getData()[2])?$system->getData()[2]["value"]:null);
                            $sheet->setCellValue('F'.$j, isset($system->getData()[3])?$system->getData()[3]["value"]:null);
                            break;
                        case "security":
                        case "administration":
                        case "device":
                        case "software":
                        case "server":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            $sheet->setCellValue('E'.$j, isset($system->getData()[2])?$system->getData()[2]["value"]:null);
                            $sheet->setCellValue('F'.$j, isset($system->getData()[3])?$system->getData()[3]["value"]:null);
                            break;
                        case "partitioning":
                        case "minimization":
                        case "anonymization":
                        case "pseudonymization":
                        case "sensitization":
                        case "information":
                        case "supervision":
                        case "destruction":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            $sheet->setCellValue('D'.$j, isset($system->getData()[1])?$system->getData()[1]["value"]:null);
                            break;
                        case "supplier":
                            $sheet->setCellValue('C'.$j, isset($system->getData()[0])?$system->getData()[0]["value"]:null);
                            break;
                        default:
                            throw new NotFoundHttpException();
                    }
                }
            }

            $spreadsheet->removeSheetByIndex(0);

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse();
            $response->setCallback(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="export_cartographie.xlsx"');
            $response->headers->set('Cache-Control','max-age=0');
            return $response;
        } else {
            return $this->redirectToRoute("user_systems");
        }
    }

    /**
     * @Route("/incidents", name="incidents")
     */
    public function incidentsAction(Request $request)
    {
        if ($this->getUser()->getUser()->getParentUser()) {
            $incidents = $this->getDoctrine()->getRepository(Incident::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
        } else {
            $incidents = $this->getDoctrine()->getRepository(Incident::class)->findBy(["user" => $this->getUser()->getUser()]);
        }

        return $this->render('user/incidents.html.twig', [
            "incidents" => $incidents
        ]);
    }

    /**
     * @Route("/incidents/export", name="incidents_export")
     */
    public function incidentsExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            if ($this->getUser()->getUser()->getParentUser()) {
                $incidents = $this->getDoctrine()->getRepository(Incident::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
            } else {
                $incidents = $this->getDoctrine()->getRepository(Incident::class)->findBy(["user" => $this->getUser()->getUser()]);
            }

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Registre des incidents");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/incidents.html.twig', [
                "incidents" => $incidents
            ]);

            $filename = 'Registre_des_incidents';

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_incidents");
        }
    }

    /**
     * @Route("/incidents/add", name="incidents_add")
     */
    public function incidentsAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $incident = new Incident();

        $form = $this->createForm(IncidentType::class, $incident);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "cet_incident_touche_le_groupe",
                'required' => false,
                'translation_domain' => 'messages',
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $incident->setCreationDate($now);
            $incident->setEditDate($now);
            $incident->setUser($this->getUser()->getUser());

            $em->persist($incident);
            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $incident->setFile($fileName);

                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouvel incident<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouvel incident client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouvel incident client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Nouvel incident ajouté');
            return $this->redirectToRoute("user_incidents");
        }

        return $this->render('user/incidents_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/incidents/{id}/edit", name="incidents_edit")
     */
    public function incidentsEditAction(Request $request, SendEmailService $sendEmailService, Incident $incident)
    {
        if ($incident->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(IncidentType::class, $incident);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "cet_incident_touche_le_groupe",
                'required' => false,
                'translation_domain' => 'messages',
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $incident->setEditDate($now);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $incident->setFile($fileName);

                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié un incident<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Incident client modifié",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Incident client modifié",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Incident mis à jour');
            return $this->redirectToRoute("user_incidents");
        }

        return $this->render('user/incidents_edit.html.twig', [
            "incident" => $incident,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/incidents/{id}/copy", name="incidents_copy")
     */
    public function incidentsCopyAction(Request $request, SendEmailService $sendEmailService, Incident $incident)
    {
        if ($incident->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $newIncident = clone $incident;
        $newIncident->setId(null);

        $form = $this->createForm(IncidentType::class, $newIncident);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => "cet_incident_touche_le_groupe",
                'required' => false,
                'translation_domain' => 'messages',
            ]);
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $newIncident->setCreationDate($now);
            $newIncident->setEditDate($now);
            $newIncident->setUser($this->getUser()->getUser());

            $em->persist($newIncident);
            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $newIncident->setFile($fileName);

                $em->flush();
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouvel incident<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouvel incident client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouvel incident client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Incident dupliqué');
            return $this->redirectToRoute("user_incidents");
        }

        return $this->render('user/incidents_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/incidents/{id}/delete", name="incidents_delete")
     */
    public function incidentsDeleteAction(Request $request, Incident $incident)
    {
        if ($incident->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($incident);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Incident supprimé');
        return $this->redirectToRoute("user_incidents");
    }

    /**
     * @Route("/incidents/{id}/view", name="incidents_view")
     */
    public function incidentsViewAction(Request $request, Incident $incident)
    {
        if ($incident->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            if (!$incident->isGroup() || !$this->getUser()->getUser()->getParentUser() || ($this->getUser()->getUser()->getParentUser() && $incident->getUser()->getId() != $this->getUser()->getUser()->getParentUser()->getId())) {
                throw new NotFoundHttpException();
            }
        }

        $form = $this->createForm(IncidentViewType::class, $incident);

        return $this->render('user/incidents_view.html.twig', [
            "form" => $form->createView(),
            "incident" => $incident
        ]);
    }

    /**
     * @Route("/actions", name="actions")
     */
    public function actionsAction(Request $request)
    {
        $user = $this->getUser()->getUser();
    
        // Get all actions (realized and non-realized)
        $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($user);
    
        // Stats calculation
        $actionsStats = [
            "total" => count($actions),
            "invalid" => 0,
            "valid" => 0,
        ];
    
        foreach ($actions as $action) {
            if ($action->isTerminated()) {
                $actionsStats["valid"]++;
            } else {
                $actionsStats["invalid"]++;
            }
        }
    
        $groupActions = $this->getDoctrine()->getRepository(Action::class)->findGroupsForUser($user);
    
        // Define urgency order
        $urgencyOrder = [
            1 => 1, // Urgent
            2 => 2, // Modéré
            3 => 3, // Faible
        ];
    
        // Step 1: **Sort All Actions Globally**
        usort($actions, function ($a, $b) use ($urgencyOrder) {
            $urgencyA = $urgencyOrder[$a->getPriority()] ?? 4;
            $urgencyB = $urgencyOrder[$b->getPriority()] ?? 4;
    
            // Step 1: Sort by urgency first (Urgent > Modéré > Faible)
            if ($urgencyA !== $urgencyB) {
                return $urgencyA - $urgencyB;
            }
    
            // Step 2: Sort by due date (Past dates first, then future in ascending order)
            $dateA = $a->getSetUpDate();
            $dateB = $b->getSetUpDate();
            $now = new \DateTime();
    
            if ($dateA && $dateB) {
                $isPastA = $dateA < $now;
                $isPastB = $dateB < $now;
    
                // Ensure past dates come first
                if ($isPastA !== $isPastB) {
                    return $isPastA ? -1 : 1;
                }
    
                // If both are past or both are future, sort by date ascending (earliest first)
                return $dateA <=> $dateB;
            }
    
            // Handle cases where one date is null (non-null dates come first)
            return $dateA ? -1 : ($dateB ? 1 : 0);
        });
    
        return $this->render('user/actions.html.twig', [
            "actions" => $actions, // **Now sorted globally**
            "groupActions" => $groupActions,
            "actionsStats" => $actionsStats,
            "filter" => null,
        ]);
    }
    
    
    
    
    
    
    
    

    /**
     * @Route("/actions/terminated", name="actions_terminated")
     */
    public function actionsTerminatedAction(Request $request)
    {
        $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());

        $actionsStats = [
            "total" => 0,
            "invalid" => 0,
            "valid" => 0,
        ];

        foreach ($actions as $action) {
            $actionsStats["total"]++;
            if ($action->isTerminated()) {
                $actionsStats["valid"]++;
            } else {
                $actionsStats["invalid"]++;
            }
        }

        $filteredActions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroupTerminated($this->getUser()->getUser());

        return $this->render('user/actions_terminated.html.twig', [
            "actions" => $filteredActions,
            "actionsStats" => $actionsStats,
            "filter" => null,
        ]);
    }

    /**
     * @Route("/actions/standardize/{id}", name="actions_standardize")
     */
    public function actionsStandardizeAction(Request $request, Security $security, Action $action)
    {
        $em = $this->getDoctrine()->getManager();

        $actionStd = new ActionStd();

        $actionStd->setName($action->getName());
        $actionStd->setBudget($action->getBudget());
        $actionStd->setGoal($action->getGoal());
        $actionStd->setInformation($action->getInformation());
        $actionStd->setUsefulLink($action->getUsefulLink());
        $actionStd->setDate($action->getDate());
        $actionStd->setEditDate($action->getEditDate());
        $actionStd->setDocuments($action->getDocuments());
        $actionStd->setSheets($action->getSheets());

        $token = $security->getToken();

        if ($token instanceof SwitchUserToken) {
            $impersonatorUser = $token->getOriginalToken()->getUser();
            if ($impersonatorUser) {
                if ($impersonatorUser->getManager()) {
                    $originalManager = $em->getRepository(Manager::class)->find($impersonatorUser->getManager()->getId());
                    if ($originalManager) {
                        $actionStd->setManager($originalManager);
                    }
                }
            }
        }

        $em->persist($actionStd);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Nouvelle action standard générée');
        return $this->redirectToRoute("user_actions");
    }

    /**
     * @Route("/actions/export", name="actions_export")
     */
    public function actionsExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Plan d'actions");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/actions.html.twig', [
                "actions" => $actions
            ]);

            $filename = 'plan_d_actions';

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_actions");
        }
    }

    /**
     * @Route("/actions/add", name="actions_add")
     */
    public function actionsAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $action = new Action();

        $user = $this->getUser()->getUser();

        $form = $this->createForm(ActionType::class, $action)
            ->add('treatments', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'traitements_lis',
                ),
                'placeholder' => 'traitements_lis',
                'label' => 'selectionnez_les_traitements_lies',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Treatment::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    return $er->createQueryBuilder('t')
                        ->where('t.user = :user')
                        ->setParameters(["user" => $user]);
                },
                'choice_label' => function(Treatment $treatment) {
                    return $treatment->getName();
                },
            ])
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'annexer_des_fiches_pratiques',
                ),
                'placeholder' => 'annexer_des_fiches_pratiques',
                'label' => 'annexer_des_fiches_pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Document::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin("d.type", "t")
                        ->leftJoin("t.parent", "pt")
                        ->where('t.id = 2')
                        ->orWhere('pt.id = 2')
                        ->addOrderBy("d.name", "ASC")
                        ->addOrderBy("d.filename", "ASC");
                }
            ]);

        if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            $form->add('forDpo', CheckboxType::class, [
                'label' => "action_a_realiser_par_mdp",
                'required' => false,
                'translation_domain' => 'messages',
            ])
                ->add('estimationTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_facture_en_heures'
                    ],
                    'label' => 'temps_de_realisation_facture_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        } else {
            $form->add('estimationTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_estime_en_heures'
                    ],
                    'label' => 'temps_de_realisation_estime_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        }

        $fromStd = false;
        $actionStd = null;

        if (isset($_GET['std'])) {
            $actionStd = $this->getDoctrine()->getRepository(ActionStd::class)->find($_GET['std']);
            if ($actionStd) {
                $fromStd = true;

                $form['name']->setData($actionStd->getName());
                $form['budget']->setData($actionStd->getBudget());
                $form['goal']->setData($actionStd->getGoal());
                $form['information']->setData($actionStd->getInformation());
                $form['usefulLink']->setData($actionStd->getUsefulLink());
                $form['sheets']->setData($actionStd->getSheets());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $action->setDate($now);
            $action->setEditDate($now);
            $action->setUser($this->getUser()->getUser());

            if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
                $action->setByManager(true);
            }

            $em->persist($action);
            $em->flush();

            if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
                if ($action->isForDpo()) {
                    if ($action->getEstimationTime()) {
                        $credit = new Credit();

                        $credit->setCreationDate($now);
                        $credit->setUser($user);
                        $credit->setTitle("Action : ".$action->getName());
                        $credit->setStock(-$action->getEstimationTime());

                        $credit->convertToDecimal(true);

                        $em->persist($credit);
                        $em->flush();

                        $user->setCredit($user->getCredit() + $credit->getStock());

                        $em->flush();
                    }
                }
            }

            if ($fromStd) {
                if ($actionStd) {
                    $filesystem = new Filesystem();
                    foreach ($actionStd->getDocuments() as $document) {
                        if (isset($_POST["appbundle_action_documents_".$document->getId()]) && !empty($_POST["appbundle_action_documents_".$document->getId()])) {
                            $fileName = $document->getFilename();
                            $childFileName = $this->getUser()->getUser()->getId()."_".$fileName;

                            $filesystem->copy($this->getParameter('documents_directory').$fileName, $this->getParameter('documents_directory').$childFileName);

                            $newDocument = new UserDocument();
                            $newDocument->setName($document->getName());
                            $newDocument->setFilename($childFileName);
                            $newDocument->setUserFilename($document->getUserFilename());
                            $newDocument->setUser($this->getUser()->getUser());
                            $newDocument->setAction($action);

                            $em->persist($newDocument);
                            $em->flush();
                        }
                    }
                }
            }

            if (isset($_FILES['appbundle_action_documents'])) {
                $files = $_FILES['appbundle_action_documents'];
                $fileNames = $_POST['appbundle_action_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $action->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setAction($action);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté une nouvelle action: ".$action->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouvelle action client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouvelle action client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Nouvelle action ajoutée');

            if ($action->getAccountantEmail()) {
                if (filter_var($action->getAccountantEmail(), FILTER_VALIDATE_EMAIL)) {
                    $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->setUser($this->getUser()->getUser());
                    $pdf->SetAuthor('myDigitplace');
                    $pdf->SetTitle("Fiche action");
                    $pdf->SetMargins(10,22,10, true);
                    $pdf->SetAutoPageBreak(TRUE, 35);
                    $pdf->AddPage('P', 'A4');

                    $filename = 'fiche_action';

                    $html = $this->renderView('user/pdf/action.html.twig', [
                        "action" => $action
                    ]);

                    $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

                    $filePath1 = tempnam(sys_get_temp_dir(), 'FicheAction');
                    $file1 = fopen($filePath1, "w");
                    fwrite($file1, $pdf->Output($filePath1, 'F'));
                    $meta_data1 = stream_get_meta_data($file1);
                    $path1 = $meta_data1['uri'];
                    fclose($file1);

                    if (count($action->getDocuments()) || count($action->getSheets())) {
                        $filesToAdd = [];

                        foreach ($action->getDocuments() as $document) {
                            if ($document->getFilename()) {
                                if (substr($document->getFilename(), -3) == "pdf") {
                                    $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/'.$document->getFilename();
                                    if (file_exists($path2)) {
                                        $filepdf = fopen($path2,"r");
                                        if ($filepdf) {
                                            $line_first = fgets($filepdf);
                                            preg_match_all('!\d+!', $line_first, $matches);
                                            $pdfversion = implode('.', $matches[0]);
                                            if($pdfversion <= "1.4"){
                                                $filesToAdd[] = $path2;
                                            }
                                            fclose($filepdf);
                                        }
                                    }
                                }
                            }
                        }

                        foreach ($action->getSheets() as $sheet) {
                            if ($sheet->getFilename()) {
                                if (substr($sheet->getFilename(), -3) == "pdf") {
                                    $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/'.$sheet->getFilename();
                                    if (file_exists($path2)) {
                                        $filepdf = fopen($path2,"r");
                                        if ($filepdf) {
                                            $line_first = fgets($filepdf);
                                            preg_match_all('!\d+!', $line_first, $matches);
                                            $pdfversion = implode('.', $matches[0]);
                                            if($pdfversion <= "1.4"){
                                                $filesToAdd[] = $path2;
                                            }
                                            fclose($filepdf);
                                        }
                                    }
                                }
                            }
                        }

                        $nbFiles = count($filesToAdd);
                        if ($nbFiles) {
                            $i = 1;
                            foreach ($filesToAdd as $path2) {
                                $file2merge = [$path1, $path2];

                                $pdfConcat = new Pdf_concat();
                                $pdfConcat->setFiles($file2merge);
                                $pdfConcat->concat();

                                $pdfConcat->SetAuthor('myDigitplace');
                                $pdfConcat->SetTitle("Fiche action");

                                $filePath1 = tempnam(sys_get_temp_dir(), 'FicheAction');
                                $file1 = fopen($filePath1, "w");
                                fwrite($file1, $pdfConcat->Output('F', $filePath1));
                                $meta_data1 = stream_get_meta_data($file1);
                                $path1 = $meta_data1['uri'];
                                fclose($file1);

                                $i++;
                            }
                        }
                    }

                    $content = "<p>Bonjour,<br/>
                    <br/>
                    Vous avez été assigné comme responsable d'une nouvelle action.<br/>
                    Pour plus de détails sur cette action, vous pouvez consulter le document en pièce jointe.<br/>
                    <br/>
                    <br/>
                    <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                    </p>";
                    $sendEmailService->send(
                        "Nouvelle action",
                        $action->getAccountantEmail(),
                        'template_emails/left_text.html.twig',
                        [
                            "title" => "Nouvelle action",
                            "content" => $content
                        ],
                        [
                            [
                                'path' => $path1,
                                'fileName' => "fiche_action.pdf",
                            ]
                        ]
                    );
                }
            }

            return $this->redirectToRoute("user_actions");
        }

        $actionsStd = $this->getDoctrine()->getRepository(ActionStd::class)->findBy([], ["name" => "ASC"]);

        return $this->render('user/actions_add.html.twig', [
            "form" => $form->createView(),
            "actionsStd" => $actionsStd,
            "fromStd" => $fromStd,
            "actionStd" => $actionStd,
            "isAdmin" => $this->isGranted("ROLE_PREVIOUS_ADMIN")
        ]);
    }


    /**
     * @Route("/actions/{id}/edit", name="actions_edit")
     */
    public function actionsEditAction(Request $request, SendEmailService $sendEmailService, Action $action)
    {
        if ($action->getUser()->getId() != $this->getUser()->getUser()->getId() && !$action->getGroupUser() && $action->getGroupUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $currentEstimatedTime = $action->getEstimationTime();

        $user = $this->getUser()->getUser();

        if ($action->isByGroup()) {
            $form = $this->createForm(ActionEditGrpType::class, $action);
        } else {
            $form = $this->createForm(ActionType::class, $action);
        }

        $form->add('treatments', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'traitements_lis',
                ),
                'placeholder' => 'traitements_lis',
                'label' => 'selectionnez_les_traitements_lies',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Treatment::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    return $er->createQueryBuilder('t')
                        ->where('t.user = :user')
                        ->setParameters(["user" => $user]);
                },
                'choice_label' => function(Treatment $treatment) {
                    return $treatment->getName();
                },
            'disabled' => $action->isByGroup()?true:false,
            ])
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'annexer_des_fiches_pratiques',
                ),
                'placeholder' => 'annexer_des_fiches_pratiques',
                'label' => 'annexer_des_fiches_pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Document::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin("d.type", "t")
                        ->leftJoin("t.parent", "pt")
                        ->where('t.id = 2')
                        ->orWhere('pt.id = 2')
                        ->addOrderBy("d.name", "ASC")
                        ->addOrderBy("d.filename", "ASC");
                },
                'disabled' => $action->isByGroup()?true:false,
            ]);

        if (!$action->isByGroup() && $this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            $form->add('forDpo', CheckboxType::class, [
                'label' => "action_a_realiser_par_mdp",
                'required' => false,
                'translation_domain' => 'messages',
            ])
                ->add('estimationTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_facture_en_heures'
                    ],
                    'label' => 'temps_de_realisation_facture_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        } else {
            $form->add('estimationTime', NumberType::class, [
                'attr' => [
                    'placeholder' => 'temps_de_realisation_estime_en_heures'
                ],
                'label' => 'temps_de_realisation_estime_en_heures',
                'required' => false,
                'translation_domain' => 'messages',
            ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $action->setEditDate($now);

            $em->flush();

            if (!$action->isByGroup() && $this->isGranted("ROLE_PREVIOUS_ADMIN")) {
                if ($action->isForDpo()) {
                    if ($currentEstimatedTime == 0 && $action->getEstimationTime()) {
                        $credit = new Credit();

                        $credit->setCreationDate($now);
                        $credit->setUser($user);
                        $credit->setTitle("Action : ".$action->getName());
                        $credit->setStock(-$action->getEstimationTime());

                        $credit->convertToDecimal(true);

                        $em->persist($credit);
                        $em->flush();

                        $user->setCredit($user->getCredit() + $credit->getStock());

                        $em->flush();
                    }
                }
            }

            foreach ($action->getDocuments() as $document) {
                if (isset($_POST["appbundle_action_documents_".$document->getId()]) && !empty($_POST["appbundle_action_documents_".$document->getId()])) {
                    if ($document->getName() != $_POST["appbundle_action_documents_".$document->getId()]) {
                        $document->setTitle($_POST["appbundle_action_documents_".$document->getId()]);
                        $em->flush();
                    }
                }
            }

            if (isset($_FILES['appbundle_action_documents'])) {
                $files = $_FILES['appbundle_action_documents'];
                $fileNames = $_POST['appbundle_action_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $action->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setAction($action);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié l'action ".$action->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Action client modifiée",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Action client modifiée",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Action mise à jour');
            return $this->redirectToRoute("user_actions");
        }

        return $this->render($action->isByGroup() ? 'user/actions_grp_edit.html.twig' : 'user/actions_edit.html.twig', [
            "action" => $action,
            "form" => $form->createView(),
            "isAdmin" => $this->isGranted("ROLE_PREVIOUS_ADMIN")
        ]);
    }

    /**
     * @Route("/actions/{id}/copy", name="actions_copy")
     */
    public function actionsCopyAction(Request $request, SendEmailService $sendEmailService, Action $action)
    {
        if ($action->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $newAction = clone $action;
        $newAction->setId(null);
        $newAction->getDocuments()->clear();
        $newAction->getTreatments()->clear();

        $user = $this->getUser()->getUser();

        $form = $this->createForm(ActionType::class, $newAction)
            ->add('treatments', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'traitements_lis',
                ),
                'placeholder' => 'traitements_lis',
                'label' => 'selectionnez_les_traitements_lies',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Treatment::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    return $er->createQueryBuilder('t')
                        ->where('t.user = :user')
                        ->setParameters(["user" => $user]);
                },
                'choice_label' => function(Treatment $treatment) {
                    return $treatment->getName();
                },
            ])
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'annexer_des_fiches_pratiques',
                ),
                'placeholder' => 'annexer_des_fiches_pratiques',
                'label' => 'annexer_des_fiches_pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Document::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin("d.type", "t")
                        ->leftJoin("t.parent", "pt")
                        ->where('t.id = 2')
                        ->orWhere('pt.id = 2')
                        ->addOrderBy("d.name", "ASC")
                        ->addOrderBy("d.filename", "ASC");
                }
            ]);

        if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            $form->add('forDpo', CheckboxType::class, [
                'label' => "action_a_realiser_par_mdp",
                'required' => false,
                'translation_domain' => 'messages',
            ])
                ->add('estimationTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_facture_en_heures'
                    ],
                    'label' => 'temps_de_realisation_facture_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        } else {
            $form->add('estimationTime', NumberType::class, [
                'attr' => [
                    'placeholder' => 'temps_de_realisation_estime_en_heures'
                ],
                'label' => 'temps_de_realisation_estime_en_heures',
                'required' => false,
                'translation_domain' => 'messages',
            ])
                ->add('realTime', NumberType::class, [
                    'attr' => [
                        'placeholder' => 'temps_de_realisation_en_heures'
                    ],
                    'label' => 'temps_de_realisation_en_heures',
                    'required' => false,
                    'translation_domain' => 'messages',
                ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $newAction->setDate($now);
            $newAction->setEditDate($now);
            $newAction->setUser($this->getUser()->getUser());

            if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
                $newAction->setByManager(true);
            }

            $em->persist($newAction);
            $em->flush();

            if (isset($_FILES['appbundle_action_documents'])) {
                $files = $_FILES['appbundle_action_documents'];
                $fileNames = $_POST['appbundle_action_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $newAction->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setAction($newAction);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté une nouvelle action: ".$newAction->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouvelle action client",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouvelle action client",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Action dupliquée');
            return $this->redirectToRoute("user_actions");
        }

        $actionsStd = $this->getDoctrine()->getRepository(ActionStd::class)->findBy([], ["name" => "ASC"]);

        return $this->render('user/actions_add.html.twig', [
            "action" => $action,
            "fromStd" => false,
            "actionsStd" => $actionsStd,
            "form" => $form->createView(),
            "isAdmin" => $this->isGranted("ROLE_PREVIOUS_ADMIN")
        ]);
    }

    /**
     * @Route("/actions/{id}/delete", name="actions_delete")
     */
    public function actionsDeleteAction(Request $request, Action $action)
    {
        if ($action->getUser()->getId() != $this->getUser()->getUser()->getId() && !$action->getGroupUser() && $action->getGroupUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($action);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Action supprimée');
        return $this->redirectToRoute("user_actions");
    }

    /**
     * @Route("/actions/{id}/print", name="actions_print")
     */
    public function actionsPrintAction(Request $request, Action $action)
    {

        if ($this->isPrintingAllowed($this->getUser())) {
            if ($action->getUser()->getId() != $this->getUser()->getUser()->getId() && !$action->getGroupUser() && $action->getGroupUser()->getId() != $this->getUser()->getUser()->getId()) {
                throw new NotFoundHttpException();
            }

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Fiche action");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('P', 'A4');

            $filename = 'fiche_action';

            $html = $this->renderView('user/pdf/action.html.twig', [
                "action" => $action
            ]);

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

            if (count($action->getDocuments()) || count($action->getSheets())) {
                $filePath1 = tempnam(sys_get_temp_dir(), 'UploadedFile');
                $file1 = fopen($filePath1, "w");
                fwrite($file1, $pdf->Output($filePath1, 'F'));
                $meta_data1 = stream_get_meta_data($file1);
                $path1 = $meta_data1['uri'];
                fclose($file1);

                $filesToAdd = [];

                foreach ($action->getDocuments() as $document) {
                    if ($document->getFilename()) {
                        if (substr($document->getFilename(), -3) == "pdf") {
                            $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/'.$document->getFilename();
                            if (file_exists($path2)) {
                                $filepdf = fopen($path2,"r");
                                if ($filepdf) {
                                    $line_first = fgets($filepdf);
                                    preg_match_all('!\d+!', $line_first, $matches);
                                    $pdfversion = implode('.', $matches[0]);
                                    if($pdfversion <= "1.4"){
                                        $filesToAdd[] = $path2;
                                    }
                                    fclose($filepdf);
                                }
                            }
                        }
                    }
                }

                foreach ($action->getSheets() as $sheet) {
                    if ($sheet->getFilename()) {
                        if (substr($sheet->getFilename(), -3) == "pdf") {
                            $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/'.$sheet->getFilename();
                            if (file_exists($path2)) {
                                $filepdf = fopen($path2,"r");
                                if ($filepdf) {
                                    $line_first = fgets($filepdf);
                                    preg_match_all('!\d+!', $line_first, $matches);
                                    $pdfversion = implode('.', $matches[0]);
                                    if($pdfversion <= "1.4"){
                                        $filesToAdd[] = $path2;
                                    }
                                    fclose($filepdf);
                                }
                            }
                        }
                    }
                }

                $nbFiles = count($filesToAdd);
                if ($nbFiles) {
                    $i = 1;
                    foreach ($filesToAdd as $path2) {
                        $file2merge = [$path1, $path2];

                        $pdfConcat = new Pdf_concat();
                        $pdfConcat->setFiles($file2merge);
                        $pdfConcat->concat();

                        if ($i == $nbFiles) {
                            $pdfConcat->SetAuthor('myDigitplace');
                            $pdfConcat->SetTitle("Fiche action");

                            return $pdfConcat->Output('I', $filename.".pdf");
                        }

                        $filePath1 = tempnam(sys_get_temp_dir(), 'UploadedFile');
                        $file1 = fopen($filePath1, "w");
                        fwrite($file1, $pdfConcat->Output('F', $filePath1));
                        $meta_data1 = stream_get_meta_data($file1);
                        $path1 = $meta_data1['uri'];
                        fclose($file1);

                        $i++;
                    }
                }
            }

            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_actions");
        }
    }

    /**
     * @Route("/actions/deletedoc/{action}/{document}", name="actions_deletedoc")
     */
    public function actionsDeleteDocAction(Request $request, Action $action, UserDocument $userDocument)
    {
        $em = $this->getDoctrine()->getManager();

        if ($action->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }
        if ($userDocument->getSubcontractor() == null || $userDocument->getAction()->getId() != $action->getId()) {
            throw new NotFoundHttpException();
        }

        $em->remove($userDocument);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute('user_actions_edit', ['id' => $action->getId()]);
    }

    /**
     * @Route("/actionsgrp/add", name="actions_grp_add")
     */
    public function actionsGrpAddAction(Request $request, SendEmailService $sendEmailService)
    {
        if (!$this->getUser()->getUser()->isMainGroupAgency()) {
            throw new NotFoundHttpException();
        }

        $action = new Action();

        $user = $this->getUser()->getUser();

        $form = $this->createForm(ActionGrpType::class, $action)
            ->add('treatments', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'traitements_lis',
                ),
                'placeholder' => 'traitements_lis',
                'label' => 'selectionnez_les_traitements_lies',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Treatment::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    return $er->createQueryBuilder('t')
                        ->where('t.user = :user')
                        ->andWhere("t.group = true")
                        ->setParameters(["user" => $user]);
                },
                'choice_label' => function(Treatment $treatment) {
                    return $treatment->getName();
                },
            ])
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'annexer_des_fiches_pratiques',
                ),
                'placeholder' => 'annexer_des_fiches_pratiques',
                'label' => 'annexer_des_fiches_pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'translation_domain' => 'messages',
                'class' => Document::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->leftJoin("d.type", "t")
                        ->leftJoin("t.parent", "pt")
                        ->where('t.id = 2')
                        ->orWhere('pt.id = 2')
                        ->addOrderBy("d.name", "ASC")
                        ->addOrderBy("d.filename", "ASC");
                }
            ])
            ->add('estimationTime', NumberType::class, [
            'attr' => [
                'placeholder' => 'temps_de_realisation_estime_en_heures'
            ],
            'label' => 'temps_de_realisation_estime_en_heures',
            'required' => false,
            'translation_domain' => 'messages',
        ])
            ->add('realTime', NumberType::class, [
                'attr' => [
                    'placeholder' => 'temps_de_realisation_en_heures'
                ],
                'label' => 'temps_de_realisation_en_heures',
                'required' => false,
                'translation_domain' => 'messages',
            ])
            ->add('users', EntityType::class, [
                'label' => 'Entités concernées',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                //'data' => $user->getChildrenUsers(),
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ( $user ) {
                    return $er->createQueryBuilder('u')
                        ->where('u.parentUser = :user')
                        ->orWhere("u.id = :userId")
                        ->setParameters(["user" => $user, "userId" => $user->getId()]);
                },
                'choice_label' => function(User $user) {
                    return $user->getCompanyName();
                },
            ]);

        $fromStd = false;
        $actionStd = null;

        if (isset($_GET['std'])) {
            $actionStd = $this->getDoctrine()->getRepository(ActionStd::class)->find($_GET['std']);
            if ($actionStd) {
                $fromStd = true;

                $form['name']->setData($actionStd->getName());
                $form['budget']->setData($actionStd->getBudget());
                $form['goal']->setData($actionStd->getGoal());
                $form['information']->setData($actionStd->getInformation());
                $form['usefulLink']->setData($actionStd->getUsefulLink());
                $form['sheets']->setData($actionStd->getSheets());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form["users"]->getData() && count($form["users"]->getData())) {
                $actions = [];

                $em = $this->getDoctrine()->getManager();

                $now = new \DateTime("now");

                $group_user_concerned = false;
                $usersToAdd = [];
                foreach ($form["users"]->getData() as $userToAdd) {
                    if ($userToAdd->getId() == $this->getUser()->getUser()->getId()) {
                        $group_user_concerned = true;
                    } else {
                        $usersToAdd[] = $userToAdd;
                    }
                }
                array_unshift($usersToAdd, $this->getUser()->getUser());

                foreach ($usersToAdd as $userToAdd) {
                    $newAction = new Action();

                    $newAction->setUser($userToAdd);
                    $newAction->setByGroup(true);
                    $newAction->setGroupUser($this->getUser()->getUser());

                    if ($userToAdd->getId() == $this->getUser()->getUser()->getId()) {
                        $newAction->setGroupUserConcerned($group_user_concerned);
                    } else {
                        $newAction->setGroupUserConcerned(false);
                    }

                    $newAction->setDate($now);
                    $newAction->setEditDate($now);

                    $newAction->setName($action->getName());
                    $newAction->setBudget($action->getBudget());
                    $newAction->setGoal($action->getGoal());
                    $newAction->setInformation($action->getInformation());
                    $newAction->setUsefulLink($action->getUsefulLink());
                    $newAction->setSetUpDate($action->getSetUpDate());
                    $newAction->setTerminated($action->isTerminated());
                    $newAction->setEstimationTime($action->getEstimationTime());
                    $newAction->setRealTime($action->getRealTime());
                    $newAction->setPriority($action->getPriority());
                    $newAction->setTreatments($action->getTreatments());
                    $newAction->setSheets($action->getSheets());

                    $newAction->setAccountantLastName($userToAdd->getContactLastName());
                    $newAction->setAccountantFirstName($userToAdd->getContactFirstName());
                    $newAction->setAccountantEmail($userToAdd->getContactEmail());
                    $newAction->setAccountantPhone($userToAdd->getContactPhone());

                    if (count($actions)) {
                        $newAction->setGroupAction($actions[0]);
                    }

                    $em->persist($newAction);
                    $em->flush();

                    $actions[] = $newAction;
                }


                if ($fromStd) {
                    if ($actionStd) {
                        $filesystem = new Filesystem();
                        foreach ($actionStd->getDocuments() as $document) {
                            if (isset($_POST["appbundle_action_documents_".$document->getId()]) && !empty($_POST["appbundle_action_documents_".$document->getId()])) {
                                $fileName = $document->getFilename();
                                $childFileName = $this->getUser()->getUser()->getId()."_".$fileName;

                                $filesystem->copy($this->getParameter('documents_directory').$fileName, $this->getParameter('documents_directory').$childFileName);

                                foreach ($actions as $actionToAdd) {
                                    $newDocument = new UserDocument();
                                    $newDocument->setName($document->getName());
                                    $newDocument->setFilename($childFileName);
                                    $newDocument->setUserFilename($document->getUserFilename());
                                    $newDocument->setUser($actionToAdd->getUser());
                                    $newDocument->setAction($actionToAdd);

                                    $em->persist($newDocument);
                                    $em->flush();
                                }
                            }
                        }
                    }
                }

                if (isset($_FILES['appbundle_action_documents'])) {
                    $files = $_FILES['appbundle_action_documents'];
                    $fileNames = $_POST['appbundle_action_documents_names'];
                    for ($i=0;$i<count($files['name']);$i++) {
                        if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                            $name = $files["name"][$i];
                            $parts = explode(".", $name);
                            $extension = end($parts);

                            $fileName = $action->getId()."_".md5(uniqid()) . '.' . $extension;

                            move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                            $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                            foreach ($actions as $actionToAdd) {
                                $document = new UserDocument();
                                $document->setName($docTitle);
                                $document->setFilename($fileName);
                                $document->setUserFilename($files["name"][$i]);
                                $document->setUser($actionToAdd->getUser());
                                $document->setAction($actionToAdd);
                                $em->persist($document);
                                $em->flush();
                            }
                        }
                    }
                }

                $this->get('session')->getFlashBag()->add('success', 'Nouvelle action ajoutée');

                foreach ($actions as $actionToAdd) {
                    if ($actionToAdd->getAccountantEmail()) {
                        if (filter_var($actionToAdd->getAccountantEmail(), FILTER_VALIDATE_EMAIL)) {
                            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->setUser($actionToAdd->getUser());
                            $pdf->SetAuthor('myDigitplace');
                            $pdf->SetTitle("Fiche action");
                            $pdf->SetMargins(10, 22, 10, true);
                            $pdf->SetAutoPageBreak(TRUE, 35);
                            $pdf->AddPage('P', 'A4');

                            $filename = 'fiche_action';

                            $html = $this->renderView('user/pdf/action.html.twig', [
                                "action" => $actionToAdd
                            ]);

                            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

                            $filePath1 = tempnam(sys_get_temp_dir(), 'FicheAction');
                            $file1 = fopen($filePath1, "w");
                            fwrite($file1, $pdf->Output($filePath1, 'F'));
                            $meta_data1 = stream_get_meta_data($file1);
                            $path1 = $meta_data1['uri'];
                            fclose($file1);

                            if (count($actionToAdd->getDocuments()) || count($actionToAdd->getSheets())) {
                                $filesToAdd = [];

                                foreach ($actionToAdd->getDocuments() as $document) {
                                    if ($document->getFilename()) {
                                        if (substr($document->getFilename(), -3) == "pdf") {
                                            $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/' . $document->getFilename();
                                            if (file_exists($path2)) {
                                                $filepdf = fopen($path2, "r");
                                                if ($filepdf) {
                                                    $line_first = fgets($filepdf);
                                                    preg_match_all('!\d+!', $line_first, $matches);
                                                    $pdfversion = implode('.', $matches[0]);
                                                    if ($pdfversion <= "1.4") {
                                                        $filesToAdd[] = $path2;
                                                    }
                                                    fclose($filepdf);
                                                }
                                            }
                                        }
                                    }
                                }

                                foreach ($actionToAdd->getSheets() as $sheet) {
                                    if ($sheet->getFilename()) {
                                        if (substr($sheet->getFilename(), -3) == "pdf") {
                                            $path2 = $this->kernel->getProjectDir() . '/../web/uploads/documents/' . $sheet->getFilename();
                                            if (file_exists($path2)) {
                                                $filepdf = fopen($path2, "r");
                                                if ($filepdf) {
                                                    $line_first = fgets($filepdf);
                                                    preg_match_all('!\d+!', $line_first, $matches);
                                                    $pdfversion = implode('.', $matches[0]);
                                                    if ($pdfversion <= "1.4") {
                                                        $filesToAdd[] = $path2;
                                                    }
                                                    fclose($filepdf);
                                                }
                                            }
                                        }
                                    }
                                }

                                $nbFiles = count($filesToAdd);
                                if ($nbFiles) {
                                    $i = 1;
                                    foreach ($filesToAdd as $path2) {
                                        $file2merge = [$path1, $path2];

                                        $pdfConcat = new Pdf_concat();
                                        $pdfConcat->setFiles($file2merge);
                                        $pdfConcat->concat();

                                        $pdfConcat->SetAuthor('myDigitplace');
                                        $pdfConcat->SetTitle("Fiche action");

                                        $filePath1 = tempnam(sys_get_temp_dir(), 'FicheAction');
                                        $file1 = fopen($filePath1, "w");
                                        fwrite($file1, $pdfConcat->Output('F', $filePath1));
                                        $meta_data1 = stream_get_meta_data($file1);
                                        $path1 = $meta_data1['uri'];
                                        fclose($file1);

                                        $i++;
                                    }
                                }
                            }

                            $content = "<p>Bonjour,<br/>
                        <br/>
                        Vous avez été assigné comme responsable d'une nouvelle action.<br/>
                        Pour plus de détails sur cette action, vous pouvez consulter le document en pièce jointe.<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
                            $sendEmailService->send(
                                "Nouvelle action",
                                $actionToAdd->getAccountantEmail(),
                                'template_emails/left_text.html.twig',
                                [
                                    "title" => "Nouvelle action",
                                    "content" => $content
                                ],
                                [
                                    [
                                        'path' => $path1,
                                        'fileName' => "fiche_action.pdf",
                                    ]
                                ]
                            );
                        }
                    }
                }

                return $this->redirectToRoute("user_actions");
            } else {
                $this->get('session')->getFlashBag()->add('danger', 'Veuillez sélectionner au minimum 1 entité en cliquant sur le bouton [ Sélectionner les entités concernées ] en bas de page');
            }
        }

        $actionsStd = $this->getDoctrine()->getRepository(ActionStd::class)->findBy([], ["name" => "ASC"]);

        $users = [];
        $usersStr = [];
        $usersStrAssoc = [];

        foreach ($form["users"]->getData()??[] as $userToFetch) {
            $users[] = $userToFetch->getId();
            $usersStr[] = $userToFetch->getCompanyName();
        }

        $usersStrAssoc[$user->getId()] = $user->getCompanyName();
        foreach ($user->getChildrenUsers()??[] as $userToFetch) {
            $usersStrAssoc[$userToFetch->getId()] = $userToFetch->getCompanyName();
        }

        sort($usersStr);

        return $this->render('user/actions_grp_add.html.twig', [
            "form" => $form->createView(),
            "actionsStd" => $actionsStd,
            "fromStd" => $fromStd,
            "actionStd" => $actionStd,
            "isAdmin" => $this->isGranted("ROLE_PREVIOUS_ADMIN"),
            "users" => $users,
            "usersStr" => $usersStr,
            "usersStrAssoc" => $usersStrAssoc,
        ]);
    }

    /**
     * @Route("/actionsgrp/{id}/list", name="actions_grp_list")
     */
    public function actionsGrpListAction(Request $request, Action $action)
    {
        if (!$action->isByGroup() || $action->getGroupUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $actions = $this->getDoctrine()->getRepository(Action::class)->findGroupsForAction($action);

        return $this->render('user/actions_grp_list.html.twig', [
            "mainAction" => $action,
            "actions" => $actions,
        ]);
    }

    /**
     * @Route("/actionsgrp/{id}/deleteall", name="actions_grp_delete_all")
     */
    public function actionsGrpDeleteAllAction(Request $request, EntityManagerInterface $em, Action $action)
    {
        if (!$action->isByGroup() || $action->getGroupUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $actions = $this->getDoctrine()->getRepository(Action::class)->findGroupsForAction($action);

        foreach ($actions as $actionToDelete) {
            $em->remove($actionToDelete);
            $em->flush();
        }

        $em->remove($action);
        $em->flush();

        return $this->redirectToRoute("user_actions");
    }

    /**
     * @Route("/exercisingclaims", name="exercisingclaims")
     */
    public function exercisingclaimsAction(Request $request)
    {
        $exercisingclaims = $this->getDoctrine()->getRepository(ExercisingClaimRequest::class)->findBy(["user" => $this->getUser()->getUser()]);

        return $this->render('user/exercisingclaims.html.twig', [
            "exercisingclaims" => $exercisingclaims
        ]);
    }

    /**
     * @Route("/exercisingclaims/add", name="exercisingclaims_add")
     */
    public function exercisingclaimsAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $exercisingclaim = new ExercisingClaimRequest();

        $form = $this->createForm(ExercisingClaimRequestType::class, $exercisingclaim);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $exercisingclaim->setUser($this->getUser()->getUser());

            $requestDate = \DateTime::createFromFormat("d/m/Y H:i:s", $form['requestDate']->getData()." 00:00:00");
            if ($requestDate) {
                $exercisingclaim->setRequestDate($requestDate);
            }

            $answerDate = \DateTime::createFromFormat("d/m/Y", $form['answerDate']->getData());
            if ($answerDate) {
                $exercisingclaim->setAnswerDate($answerDate);
            }

            $em->persist($exercisingclaim);
            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $exercisingclaim->setFile($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouvelle demande d’exercice des droits ajoutée');

            if ($exercisingclaim->getRequestDate() && !$exercisingclaim->getAnswerDate()) {
                if ($exercisingclaim->getAccountantEmail()) {
                    if (filter_var($exercisingclaim->getAccountantEmail(), FILTER_VALIDATE_EMAIL)) {
                        $content = "<p>Bonjour,<br/>
                        <br/>
                        Vous avez été assigné comme responsable d'une nouvelle demande d’exercice de droits<br/>
                        <br/>
                        Personne concernée: ".$exercisingclaim->getCustomer()."<br/>
                        Date de la demande: ".$exercisingclaim->getRequestDate()->format("d/m/Y")."<br/>
                        Droit: ".$exercisingclaim->getRights()."<br/>
                        Précisions sur la demande: ".nl2br($exercisingclaim->getPrecisions())."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
                        $sendEmailService->send(
                            "Nouvelle demande d’exercice de droits",
                            $exercisingclaim->getAccountantEmail(),
                            'template_emails/left_text.html.twig',
                            [
                                "title" => "Nouvelle demande d’exercice de droits",
                                "content" => $content
                            ]
                        );
                    }
                }
            }

            return $this->redirectToRoute("user_exercisingclaims");
        }

        return $this->render('user/exercisingclaims_add.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/exercisingclaims/{id}/edit", name="exercisingclaims_edit")
     */
    public function exercisingclaimsEditAction(Request $request, ExercisingClaimRequest $exercisingclaim)
      {
        if ($exercisingclaim->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(ExercisingClaimRequestType::class, $exercisingclaim);

        if ($exercisingclaim->getRequestDate()) {
            $form['requestDate']->setData($exercisingclaim->getRequestDate()->format("d/m/Y"));
        }
        if ($exercisingclaim->getAnswerDate()) {
            $form['answerDate']->setData($exercisingclaim->getAnswerDate()->format("d/m/Y"));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $exercisingclaim->setUser($this->getUser()->getUser());

            $requestDateStr = $form['requestDate']->getData();
            if (!empty($requestDateStr)) {
                $requestDate = \DateTime::createFromFormat("d/m/Y H:i:s", $requestDateStr." 00:00:00");
                $exercisingclaim->setRequestDate($requestDate ?: $exercisingclaim->getRequestDate());
            }
            $answerDateStr = $form['answerDate']->getData();
            if (!empty($answerDateStr)) {
                $answerDate = \DateTime::createFromFormat("d/m/Y", $answerDateStr);
                $exercisingclaim->setAnswerDate($answerDate ?: $exercisingclaim->getAnswerDate());
            } else {
                // Si le champ est vide, on supprime la date de réponse
                $exercisingclaim->setAnswerDate(null);
            }
            
            $em->persist($exercisingclaim);
            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $exercisingclaim->setFile($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Demande d’exercice des droits mise à jour');
            return $this->redirectToRoute("user_exercisingclaims");
        }

        return $this->render('user/exercisingclaims_edit.html.twig', [
            "exercisingclaim" => $exercisingclaim,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/exercisingclaims/{id}/delete", name="exercisingclaims_delete")
     */
    public function exercisingclaimsDeleteAction(Request $request, ExercisingClaimRequest $exercisingclaim)
    {
        if ($exercisingclaim->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($exercisingclaim);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Demande d’exercice des droits supprimée');
        return $this->redirectToRoute("user_exercisingclaims");
    }

    /**
     * @Route("/documents", name="documents")
     */
    public function documentsAction(Request $request, TranslatorInterface $translator)
    {

        
        $documentsTypes = $this->getDoctrine()->getRepository(DocumentType::class)->findBy(["parent" => null]);
        $userDocuments = $this->getDoctrine()->getRepository(UserDocument::class)->findBy(["user" => $this->getUser()->getUser(), "subcontractor" => null, "action" => null], ['name' => "ASC", "filename" => "ASC"]);
        $actions = $this->getDoctrine()->getRepository(Action::class)->findForUserWithGroup($this->getUser()->getUser());
        $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findBy(["user" => $this->getUser()->getUser(), "group" => false], ["name" => "ASC"]);
        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()]);

        $piaFiles = [];

        foreach ($treatments as $treatment) {
            if ($treatment->getPiaFile()) {
                $piaFiles[] = $treatment;
            }
        }

        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'attr' => [
                    'placeholder' => 'Fichier'
                ],
                'label' => 'Fichier',
                'required' => true,
                'mapped' => false
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom'
                ],
                'label' => 'Nom',
                'required' => false,
                'mapped' => false
            ])
            ->add('children', CheckboxType::class, [
                'label' => "Fichier disponible pour les comptes rattachés",
                'required' => false,
                'mapped' => false
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('file')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();
                $fileExtension = $file->guessExtension();
                $clientOriginalName = $file->getClientOriginalName();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $docTitle = $form['name']->getData()??$clientOriginalName;
                $document = new UserDocument();
                $document->setName($docTitle);
                $document->setFilename($fileName);
                $document->setUserFilename($clientOriginalName);
                $document->setUser($this->getUser()->getUser());

                $em->persist($document);
                $em->flush();

                if ($form['children']->getData()) {
                    $filesystem = new Filesystem();

                    foreach ($this->getUser()->getUser()->getChildrenUsers() as $childUser) {
                        $childFileName = $childUser->getId()."_".$this->getUser()->getUser()->getId()."_".md5(uniqid()) . '.' . $fileExtension;

                        $filesystem->copy($this->getParameter('documents_directory').$fileName, $this->getParameter('documents_directory').$childFileName);

                        /*$file->move(
                            $this->getParameter('documents_directory'), $fileName
                        );*/

                        $childUserDocument = new UserDocument();
                        $childUserDocument->setName($docTitle);
                        $childUserDocument->setFilename($childFileName);
                        $childUserDocument->setUserFilename($clientOriginalName);
                        $childUserDocument->setUser($childUser);

                        $em->persist($childUserDocument);
                        $em->flush();
                    }
                }

                $this->get('session')->getFlashBag()->add('success', 'Nouveau document téléversé');
            }


            return $this->redirectToRoute("user_documents");
        }

        $isMdpReadDocAllowed = $this->isMdpReadDocAllowed($this->getUser());

        $conformityJson = [
            [
                "id" => 0,
                "label" => $translator->trans("ma_conformit"),
                "type" => "d",
                "children" => [
                    [
                        "id" => 0,
                        "label" => $translator->trans("mon_registre"),
                        "type" => "d",
                        "children" => [],
                    ],
                    [
                        "id" => 1,
                        "label" => $translator->trans("mes_pia"),
                        "type" => "d",
                        "children" => [],
                    ],
                    [
                        "id" => 2,
                        "label" => $translator->trans("documentation_rgpd"),
                        "type" => "d",
                        "children" => [],
                    ],
                ],
            ],
            [
                "id" => 1,
                "label" => $translator->trans("mes_sous_traitants"),
                "type" => "d",
                "children" => [],
            ],
            [
                "id" => 2,
                "label" => $translator->trans("mes_cotraitant"),
                "type" => "d",
                "children" => [],
            ],
            [
                "id" => 3,
                "label" => $translator->trans("mes_responsable_de_traitement"),
                "type" => "d",
                "children" => [],
            ],
            [
                "id" => 4,
                "label" => $translator->trans("mes_actions"),
                "type" => "d",
                "children" => [],
            ]
        ];

        $conformityJson[0]["children"][0]["children"][] = [
            "id" => 0,
            "label" => $translator->trans("registre_complet"),
            "type" => "f",
            "url" => $this->generateUrl("user_treatments_export_full"),
            "deleteUrl" => null,
            "children" => [],
        ];

        foreach ($treatments as $treatment) {
            $conformityJson[0]["children"][0]["children"][] = [
                "id" => $treatment->getId(),
                "label" => $treatment->getName()." ".$this->formatTreatmentNumber($treatment->getNumber()),
                "type" => "f",
                "url" => $this->generateUrl("user_treatments_export_one", ["id" => $treatment->getId()]),
                "deleteUrl" => null,
                "children" => [],
            ];
        }
        foreach ($piaFiles as $treatment) {
            $conformityJson[0]["children"][1]["children"][] = [
                "id" => $treatment->getId(),
                "label" => $treatment->getName()." ".$this->formatTreatmentNumber($treatment->getNumber()),
                "type" => "f",
                "url" => "/uploads/documents/".$treatment->getPiaFile(),
                "deleteUrl" => null,
                "children" => [],
            ];
        }
        foreach ($userDocuments as $userDocument) {
            $conformityJson[0]["children"][2]["children"][] = [
                "id" => $userDocument->getId(),
                "label" => $userDocument->getName(),
                "type" => "f",
                "url" => $this->generateUrl("user_read_user_documents", ["id" => $userDocument->getId()]),
                "deleteUrl" => $this->generateUrl("user_user_documents_delete", ["id" => $userDocument->getId()]),
                "children" => [],
            ];
        }

        foreach ($subcontractors as $subcontractor) {
            $subData = [
                "id" => $subcontractor->getId(),
                "label" => $subcontractor->getName(),
                "type" => "d",
                "children" => [],
            ];

            foreach ($subcontractor->getDocuments() as $userDocument) {
                $subData["children"][] = [
                    "id" => $userDocument->getId(),
                    "label" => $userDocument->getName(),
                    "type" => "f",
                    "url" => $this->generateUrl("user_read_user_documents", ["id" => $userDocument->getId()]),
                    "deleteUrl" => null,
                    "children" => [],
                ];
            }
            if ($subcontractor->getSubcontractorType()) {
                switch ($subcontractor->getSubcontractorType()->getCode()) {
                    case "SOUS_TRAITANT":
                        $conformityJson[1]["children"][] = $subData;
                        break;
                    case "CO_TRAITANT":
                        $conformityJson[2]["children"][] = $subData;
                        break;
                    case "RESP_TRAITEMENT":
                        $conformityJson[3]["children"][] = $subData;
                        break;
                    default:
                        $conformityJson[1]["children"][] = $subData;
                        break;
                }
            } else {
                $conformityJson[1]["children"][] = $subData;
            }
        }

        foreach ($actions as $action) {
            $subData = [
                "id" => $action->getId(),
                "label" => $action->getName(),
                "type" => "d",
                "children" => [],
            ];
            foreach ($action->getDocuments() as $userDocument) {
                $subData["children"][] = [
                    "id" => $userDocument->getId(),
                    "label" => $userDocument->getName(),
                    "type" => "f",
                    "url" => $this->generateUrl("user_read_user_documents", ["id" => $userDocument->getId()]),
                    "deleteUrl" => null,
                    "children" => [],
                ];
            }

            $conformityJson[4]["children"][] = $subData;
        }

        $documentsTypesJson = [];

        $i = 0;
        foreach ($documentsTypes as $type) {
            $documentsTypesJson[$i] = [
                "id" => $type->getId(),
                "label" => $type->getLibelle(),
                "type" => "d",
                "children" => [],
            ];

            $j = 0;
            foreach ($type->getChildren() as $childType) {
                $documentsTypesJson[$i]["children"][] = [
                    "id" => $childType->getId(),
                    "label" => $childType->getLibelle(),
                    "type" => "d",
                    "children" => [],
                ];

                $k = 0;
                foreach ($childType->getDocuments() as $document) {
                    $documentsTypesJson[$i]["children"][$j]["children"][] = [
                        "id" => $isMdpReadDocAllowed ? $document->getId() : null,
                        "label" => $document->getName(),
                        "type" => "f",
                        "root" => $childType->getId(),
                        "read" => $isMdpReadDocAllowed,
                        "children" => [],
                    ];

                    $k++;
                }

                $j++;
            }
            foreach ($type->getDocuments() as $document) {
                $documentsTypesJson[$i]["children"][] = [
                    "id" => $isMdpReadDocAllowed ? $document->getId() : null,
                    "label" => $document->getName(),
                    "type" => "f",
                    "root" => $type->getId(),
                    "read" => $isMdpReadDocAllowed,
                    "children" => [],
                ];

                $j++;
            }

            $i++;
        }

        return $this->render('user/documents.html.twig', [
            "documentsTypes" => $documentsTypes,
            "documentsTypesJson" => $documentsTypesJson,
            "userDocuments" => $userDocuments,
            "actions" => $actions,
            "subcontractors" => $subcontractors,
            "treatments" => $treatments,
            "piaFiles" => $piaFiles,
            "conformityJson" => $conformityJson,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/users", name="users")
     */
    public function usersAction(Request $request)
    {
        return $this->render('user/users.html.twig', [
            "users" => $this->getUser()->getUser()->getChildrenUsers()
        ]);
    }

    /**
     * @Route("/subcontractorsgrp", name="subcontractors_grp")
     */
    public function subcontractorsGrpAction(Request $request)
    {
        $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());

        return $this->render('user/subcontractors_grp.html.twig', [
            "subcontractors" => $subcontractors
        ]);
    }

    /**
     * @Route("/subcontractorsgrp/export", name="subcontractors_grp_export")
     */
    public function subcontractorsGrpExportAction(Request $request)
    {
        if ($this->isPrintingAllowed($this->getUser())) {
            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findGroupForUser($this->getUser()->getUser());

            $pdf = new MyPdf('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->setUser($this->getUser()->getUser());
            $pdf->SetAuthor('myDigitplace');
            $pdf->SetTitle("Sous-traitants groupe");
            $pdf->SetMargins(10,22,10, true);
            $pdf->SetAutoPageBreak(TRUE, 35);
            $pdf->AddPage('L', 'A4');

            $html = $this->renderView('user/pdf/subcontractors.html.twig', [
                "subcontractors" => $subcontractors
            ]);

            $filename = 'Sous_traitants_groupe';

            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            return $pdf->Output($filename.".pdf",'I');
        } else {
            return $this->redirectToRoute("user_subcontractors_grp");
        }
    }

    /**
     * @Route("/subcontractorsgrp/add", name="subcontractors_grp_add")
     */
    public function subcontractorsGrpAddAction(Request $request, SendEmailService $sendEmailService)
    {
        $subcontractor = new Subcontractor();

        $defaultSubcontractorType = $this->getDoctrine()->getRepository(\App\Entity\SubcontractorType::class)->findOneBy(["code" => "SOUS_TRAITANT"]);
        $subcontractor->setSubcontractorType($defaultSubcontractorType);

        $subcontractor->setGroup(true);

        $form = $this->createForm(SubcontractorType::class, $subcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $fromStd = false;
        $subcontractorStd = null;

        if (isset($_GET['std'])) {
            $subcontractorStd = $this->getDoctrine()->getRepository(SubcontractorStd::class)->find($_GET['std']);
            if ($subcontractorStd) {
                $fromStd = true;

                $form['name']->setData($subcontractorStd->getName());
                $form['type']->setData($subcontractorStd->getType());
                $form['contactFirstName']->setData($subcontractorStd->getContactFirstName());
                $form['contactLastName']->setData($subcontractorStd->getContactLastName());
                $form['contactPhone']->setData($subcontractorStd->getContactPhone());
                $form['contactEmail']->setData($subcontractorStd->getContactEmail());
                $form['privacyPolicyLink']->setData($subcontractorStd->getPrivacyPolicyLink());
                $form['conformity']->setData($subcontractorStd->getConformity());
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $subcontractor->setDate($now);
            $subcontractor->setEditDate($now);
            $subcontractor->setUser($this->getUser()->getUser());

            $em->persist($subcontractor);
            $em->flush();

            if ($fromStd) {
                if ($subcontractorStd) {
                    $filesystem = new Filesystem();
                    foreach ($subcontractorStd->getDocuments() as $document) {
                        if (isset($_POST["appbundle_subcontractor_documents_".$document->getId()]) && !empty($_POST["appbundle_subcontractor_documents_".$document->getId()])) {
                            $fileName = $document->getFilename();
                            $childFileName = $this->getUser()->getUser()->getId()."_".$fileName;

                            $filesystem->copy($this->getParameter('documents_directory').$fileName, $this->getParameter('documents_directory').$childFileName);

                            $newDocument = new UserDocument();
                            $newDocument->setName($document->getName());
                            $newDocument->setFilename($childFileName);
                            $newDocument->setUserFilename($document->getUserFilename());
                            $newDocument->setUser($this->getUser()->getUser());
                            $newDocument->setSubcontractor($subcontractor);

                            $em->persist($newDocument);
                            $em->flush();
                        }
                    }
                }
            }

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $subcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouveau sous-traitant groupe: ".$subcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau sous-traitant groupe",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau sous-traitant groupe",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Nouveau sous-traitant groupe ajouté');
            return $this->redirectToRoute("user_subcontractors_grp");
        }

        $subcontractorsStd = $this->getDoctrine()->getRepository(SubcontractorStd::class)->findForUser($this->getUser()->getUser());

        return $this->render('user/subcontractors_grp_add.html.twig', [
            "form" => $form->createView(),
            "subcontractorsStd" => $subcontractorsStd,
            "fromStd" => $fromStd,
            "subcontractorStd" => $subcontractorStd
        ]);
    }

    /**
     * @Route("/subcontractorsgrp/{id}/edit", name="subcontractors_grp_edit")
     */
    public function subcontractorsGrpEditAction(Request $request, SendEmailService $sendEmailService, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(SubcontractorType::class, $subcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $subcontractor->setEditDate(new \DateTime("now"));

            $em->flush();

            foreach ($subcontractor->getDocuments() as $document) {
                if (isset($_POST["appbundle_subcontractor_documents_".$document->getId()]) && !empty($_POST["appbundle_subcontractor_documents_".$document->getId()])) {
                    if ($document->getName() != $_POST["appbundle_subcontractor_documents_".$document->getId()]) {
                        $document->setTitle($_POST["appbundle_subcontractor_documents_".$document->getId()]);
                        $em->flush();
                    }
                }
            }

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $subcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a modifié le sous-traitant groupe ".$subcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Sous-traitant groupe modifié",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Sous-traitant groupe modifié",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Sous-traitant groupe mis à jour');
            return $this->redirectToRoute("user_subcontractors_grp");
        }

        return $this->render('user/subcontractors_grp_edit.html.twig', [
            "subcontractor" => $subcontractor,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractorsgrp/{id}/copy", name="subcontractors_grp_copy")
     */
    public function subcontractorsGrpCopyAction(Request $request, SendEmailService $sendEmailService, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $newSubcontractor = clone $subcontractor;
        $newSubcontractor->setId(null);
        $newSubcontractor->getDocuments()->clear();

        $form = $this->createForm(SubcontractorType::class, $newSubcontractor);

        if ($this->getUser()->getUser()->isMainGroupAgency()) {
            $form->add('group', CheckboxType::class, [
                'label' => 'est_soustraitant_groupe',
                'translation_domain' => 'messages',
                'required' => false,
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $newSubcontractor->setDate($now);
            $newSubcontractor->setEditDate($now);
            $newSubcontractor->setUser($this->getUser()->getUser());
            $newSubcontractor->setGroup(true);

            $em->persist($newSubcontractor);
            $em->flush();

            if (isset($_FILES['appbundle_subcontractor_documents'])) {
                $files = $_FILES['appbundle_subcontractor_documents'];
                $fileNames = $_POST['appbundle_subcontractor_documents_names'];
                for ($i=0;$i<count($files['name']);$i++) {
                    if ( is_uploaded_file( $files["tmp_name"][$i] ) && file_exists($files["tmp_name"][$i]) && $files["error"][$i] === 0) {
                        $name = $files["name"][$i];
                        $parts = explode(".", $name);
                        $extension = end($parts);

                        $fileName = $newSubcontractor->getId()."_".md5(uniqid()) . '.' . $extension;

                        move_uploaded_file($files["tmp_name"][$i], $this->getParameter('documents_directory') . $fileName);

                        $docTitle = (isset($fileNames[$i]) && !empty($fileNames[$i]))?$fileNames[$i]:$files["name"][$i];
                        $document = new UserDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setUser($this->getUser()->getUser());
                        $document->setSubcontractor($newSubcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $content = "<p>Bonjour,<br/>
                        <br/>
                        Le client ".$this->getUser()->getUser()->getCompanyName()." a ajouté un nouveau sous-traitant groupe: ".$newSubcontractor->getName()."<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
            $sendEmailService->send(
                "Nouveau sous-traitant groupe",
                $this->getUser()->getUser()->getManager()->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => "Nouveau sous-traitant groupe",
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add('success', 'Sous-traitant groupe dupliqué');
            return $this->redirectToRoute("user_subcontractors_grp");
        }

        return $this->render('user/subcontractors_grp_copy.html.twig', [
            "subcontractor" => $subcontractor,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractorsgrp/{id}/delete", name="subcontractors_grp_delete")
     */
    public function subcontractorsGrpDeleteAction(Request $request, Subcontractor $subcontractor)
    {
        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($subcontractor);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Sous-traitant groupe supprimé');
        return $this->redirectToRoute("user_subcontractors_grp");
    }

    /**
     * @Route("/subcontractorsgrp/deletedoc/{subcontractor}/{document}", name="subcontractor_grp_deletedoc")
     */
    public function subcontractorsGrpDeleteDocAction(Request $request, Subcontractor $subcontractor, UserDocument $userDocument)
    {
        $em = $this->getDoctrine()->getManager();

        if ($subcontractor->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }
        if ($userDocument->getSubcontractor() == null || $userDocument->getSubcontractor()->getId() != $subcontractor->getId()) {
            throw new NotFoundHttpException();
        }

        $em->remove($userDocument);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute('user_subcontractors_grp_edit', ['id' => $subcontractor->getId()]);
    }

    /**
     * @Route("/trainings", name="trainings")
     */
    public function trainingsAction(Request $request, SendEmailService $sendEmailService)
    {
        $campains = $this->getDoctrine()->getRepository(TrainingCampain::class)->findBy(["user" => $this->getUser()->getUser()], ["creationDate" => "DESC"]);

        $user = $this->getUser()->getUser();

        $form = $this->createFormBuilder()
            //->add("training", HiddenType::class, [])
            ->add('campain_type', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Type de campagne'
                ],
                'label' => 'Type de campagne',
                'choices' => [
                    "campagne_interne" => "0",
                    "campagne_externe" => "1",
                ],
                'data' => '0',
                'expanded' => true,
                'multiple' => false,
                'translation_domain' => 'messages',
            ])
            ->add("title", TextType::class, [
                "attr" => [
                    "placeholder" => "titre_de_la_campagne"
                ],
                "label" => "titre_de_la_campagne",
                "required" => true,
                'translation_domain' => 'messages',
            ])
            ->add('traineeship', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Formation réalisée au préalable ?'
                ],
                'label' => 'Formation réalisée au préalable ?',
                'choices' => [
                    "Oui" => "1",
                    "Non" => "0",
                ],
                'data' => '0',
                'expanded' => true,
                'multiple' => false,
            ])
            ->add("former", TextType::class, [
                "attr" => [
                    "placeholder" => "nom_et_prenom_du_formateur"
                ],
                "label" => "nom_et_prenom_du_formateur",
                "required" => false,
                'translation_domain' => 'messages',
            ])
            ->add('traineeshipDate', TextType::class, [
                'attr' => [
                    'placeholder' => '__/__/____',
                    'data-mask' => '00/00/0000',
                    'data-mask-clearifnotmatch' => 'true'
                ],
                'label' => "date_de_la_formation",
                'required' => false,
                'mapped' => false,
                'translation_domain' => 'messages',
            ])
            ->add("emails", TextType::class, [
                "label" => "ou_saisissez_les_adresses_mail",
                "required" => false,
                'translation_domain' => 'messages',
            ])
            ->add('teams', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'equipes_sensibilisees_ou_a_interroger',
                ),
                'label' => 'equipes_sensibilisees_ou_a_interroger',
                'translation_domain' => 'messages',
                "required" => false,
                'expanded' => false,
                'multiple' => true,
                'class' => TrainingTeam::class,
                'placeholder' => "Equipes",
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('tt');
                    return $qb->where('tt.user = :user')
                        ->setParameter("user", $user)
                        ->addOrderBy("tt.name", "ASC");
                },
                'choice_label' => function(TrainingTeam $trainingTeam) {
                    return $trainingTeam->getName();
                },
            ])
            ->add('training', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Envoyer un questionnaire aux personnes',
                ),
                'label' => 'Envoyer un questionnaire aux personnes',
                'expanded' => true,
                'multiple' => false,
                'class' => Training::class,
                'placeholder' => "Aucun questionnaire",
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('t');
                    return $qb->where('t.active = true')
                        ->andWhere("t.answered = true")
                        ->leftJoin("t.users", "u")
                        ->andWhere($qb->expr()->orX(
                            "t.availableForAll = true",
                            "u.id = :userId"
                        ))
                        ->setParameter("userId", $user->getId())
                        ->addOrderBy("t.title", "ASC");
                },
                'choice_label' => function(Training $training) {
                    return $training->getTitle();
                },
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emails = json_decode($form['emails']->getData(), true);

            $emailsArray = [];

if ($emails) {
            foreach ($emails as $value) {
                $emailAddress = trim($value['value']);
                if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                    $emailsArray[] = $emailAddress;
                }
            }
}

if ($form['teams']->getData()) {
            foreach ($form['teams']->getData() as $team) {
                $addresses = explode("\n", $team->getEmailAddresses());

                foreach ($addresses as $address) {
                    $emailAddress = trim($address);
                    if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                        $emailsArray[] = $emailAddress;
                    }
                }
            }
}

            $emailsArray = array_unique($emailsArray);

            if (count($emailsArray)) {
                $now = new \DateTime("now");

                $em = $this->getDoctrine()->getManager();

                $campain = new TrainingCampain();
                $campain->setUser($this->getUser()->getUser());
                $campain->setCreationDate($now);

                $traineeshipDate = \DateTime::createFromFormat("d/m/Y", $form['traineeshipDate']->getData());
                if ($traineeshipDate) {
                    $campain->setTraineeshipDate($traineeshipDate);
                }

                $campain->setTitle($form["title"]->getData());
                $campain->setTraineeship($form["traineeship"]->getData());
                $campain->setFormer($form["former"]->getData());
                $campain->setExternal($form["campain_type"]->getData());

                $campain->setEmails($emailsArray);
                $campain->setEmailsCount(count($emailsArray));

                $campain->setTeams($form['teams']->getData());

                $em->persist($campain);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Campagne de sensibilisation créée');

                if ($campain->getTraineeship()) {
                    $system = new System();
                    $system->setData([
                        [
                            "title" => 'Description',
                            "value" => $campain->getTitle()
                        ],
                        [
                            "title" => 'Informations complémentaires',
                            "value" => "Nombre de personnes formées : ".$campain->getEmailsCount()
                        ]
                    ]);
                    $system->setName("Sensibilisation des personnels");
                    $system->setType("physical");
                    $system->setSubtype("sensitization");
                    $system->setUser($this->getUser()->getUser());

                    $em->persist($system);
                    $em->flush();
                }

                $training = $form["training"]->getData();

                if ($training) {
                    $campain->setQuestions($training->getQuestions());
                    $campain->setAnswers($training->getAnswers());
                    $campain->setTraining($training);

                    $em->flush();

                    $count = 0;

                    foreach ($emailsArray as $email) {
                        $trainingRequest = new TrainingRequest();

                        $trainingRequest->setTrainingCampain($campain);
                        $trainingRequest->setEmail($email);
                        $trainingRequest->setToken(hash("sha256", uniqid($email)));

                        $em->persist($trainingRequest);
                        $em->flush();

                        $content = "<p>Bonjour,<br/>
                            <br/>
                            Nous vous invitons à répondre au questionnaire de formation des équipes en cliquant sur le lien suivant: <br/>
                            <br/>
                            <br/>
                            <a href='".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                            ".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."
                            </a>
                            <br/>
                            <br/>
                            Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                            <br/>
                            Bien cordialement,<br/>
                            <br/>
                            <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                            </p>";

                        $sendEmailService->send(
                            "Questionnaire de formation des équipes",
                            $trainingRequest->getEmail(),
                            'template_emails/left_text.html.twig',
                            [
                                "title" => $campain->getTraining()->getTitle(),
                                "content" => $content
                            ]
                        );

                        $count++;
                    }

                    $this->get('session')->getFlashBag()->add('success', $count.' questionnaire(s) envoyé(s)');

                    return $this->redirectToRoute('user_trainings');
                }
            }
        }

        return $this->render('user/trainings.html.twig', [
            "campains" => $campains,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/trainings/teams", name="trainings_teams")
     */
    public function trainingsTeamsAction(Request $request)
    {
        $user = $this->getUser()->getUser();

        $teams = $this->getDoctrine()->getRepository(TrainingTeam::class)->findBy(["user" => $user], ["name" => "ASC"]);

        return $this->render('user/trainings_teams.html.twig', [
            "teams" => $teams,
        ]);
    }

    /**
     * @Route("/trainings/teams/add", name="trainings_teams_add")
     */
    public function trainingsTeamsAddAction(Request $request)
    {
        $user = $this->getUser()->getUser();

        $team = new TrainingTeam();

        $form = $this->createForm(TrainingTeamType::class, $team);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $team->setUser($user);

            $em->persist($team);
            $em->flush();

            return $this->redirectToRoute("user_trainings_teams");
        }

        return $this->render('user/trainings_teams_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/trainings/teams/{id}/edit", name="trainings_teams_edit")
     */
    public function trainingsTeamsEditAction(Request $request, TrainingTeam $team)
    {
        $user = $this->getUser()->getUser();

        if ($team->getUser()->getId() != $user->getId()) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(TrainingTeamType::class, $team);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->flush();

            return $this->redirectToRoute("user_trainings_teams");
        }

        return $this->render('user/trainings_teams_edit.html.twig', [
            "team" => $team,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/trainings/teams/{id}/delete", name="trainings_teams_delete")
     */
    public function trainingsTeamsDeleteAction(Request $request, TrainingTeam $team)
    {
        $user = $this->getUser()->getUser();

        if ($team->getUser()->getId() != $user->getId()) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($team);
        $em->flush();

        return $this->redirectToRoute("user_trainings_teams");
    }

    /**
     * @Route("/trainings/{id}/requests", name="trainings_requests")
     */
    public function trainingsRequestsAction(Request $request)
    {
        $trainingCampain = $this->getDoctrine()->getRepository(TrainingCampain::class)->findOneBy(["id" => $request->get("id"), "user" => $this->getUser()->getUser()]);

        if (!$trainingCampain) {
            throw new NotFoundHttpException();
        }

        $trainingRequests = $this->getDoctrine()->getRepository(TrainingRequest::class)->findBy(["trainingCampain" => $trainingCampain]);

        return $this->render('user/trainings_requests.html.twig', [
            "trainingCampain" => $trainingCampain,
            "trainingRequests" => $trainingRequests,
        ]);
    }

    /**
     * @Route("/trainings/{trainingCampain}/requests/all/revive", name="trainings_requests_revive_all")
     */
    public function trainingsRequestsReviveAllAction(Request $request, SendEmailService $sendEmailService, TrainingCampain $trainingCampain)
    {
        if ($trainingCampain->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $now = new \DateTime("now");

        $em = $this->getDoctrine()->getManager();

        $trainingRequests = $em->getRepository(TrainingRequest::class)->findBy(["trainingCampain" => $trainingCampain]);

        foreach ($trainingRequests as $trainingRequest) {
            if ($trainingRequest->getResendDate() && $now->getTimestamp() - $trainingRequest->getResendDate()->getTimestamp() < 172800) {
            } else {
                $content = "<p>Bonjour,<br/>
                            <br/>
                            Nous vous invitons à répondre au questionnaire de formation des équipes en cliquant sur le lien suivant: <br/>
                            <br/>
                            <br/>
                            <a href='".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                            ".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."
                            </a>
                            <br/>
                            <br/>
                            Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                            <br/>
                            Bien cordialement,<br/>
                            <br/>
                            <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                            </p>";

                $sendEmailService->send(
                    "Questionnaire de formation des équipes",
                    $trainingRequest->getEmail(),
                    'template_emails/left_text.html.twig',
                    [
                        "title" => $trainingRequest->getTrainingCampain()->getTraining()->getTitle(),
                        "content" => $content
                    ]
                );
            }
        }

        $this->get('session')->getFlashBag()->add("success", "Les relances ont été envoyées.");

        return $this->redirectToRoute("user_trainings_requests", ["id" => $trainingCampain->getId()]);
    }

    /**
     * @Route("/trainings/{trainingCampain}/requests/{trainingRequest}/revive", name="trainings_requests_revive")
     */
    public function trainingsRequestsReviveAction(Request $request, SendEmailService $sendEmailService, TrainingCampain $trainingCampain, TrainingRequest $trainingRequest)
    {
        if ($trainingCampain->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }
        if ($trainingCampain->getId() != $trainingRequest->getTrainingCampain()->getId()) {
            throw new NotFoundHttpException();
        }
        $now = new \DateTime("now");

        if ($trainingRequest->getResendDate() && $now->getTimestamp() - $trainingRequest->getResendDate()->getTimestamp() < 172800) {
            $this->get('session')->getFlashBag()->add("danger", "Vous ne pouvez envoyer des relances que toutes les 48h.");
        } else {
            $content = "<p>Bonjour,<br/>
                            <br/>
                            Nous vous invitons à répondre au questionnaire de formation des équipes en cliquant sur le lien suivant: <br/>
                            <br/>
                            <br/>
                            <a href='".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                            ".$this->generateUrl("default_training", ["email" => $trainingRequest->getEmail(), "token" => $trainingRequest->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)."
                            </a>
                            <br/>
                            <br/>
                            Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                            <br/>
                            Bien cordialement,<br/>
                            <br/>
                            <i>Cet e-mail a été envoyé depuis le site myDigitplace. NE PAS répondre à ce message automatique.</i><br/>
                            </p>";

            $sendEmailService->send(
                "Questionnaire de formation des équipes",
                $trainingRequest->getEmail(),
                'template_emails/left_text.html.twig',
                [
                    "title" => $trainingRequest->getTrainingCampain()->getTraining()->getTitle(),
                    "content" => $content
                ]
            );

            $this->get('session')->getFlashBag()->add("success", "La relance a été envoyée.");
        }

        return $this->redirectToRoute("user_trainings_requests", ["id" => $trainingCampain->getId()]);
    }

    /**
     * @Route("/trainings/{training}/answers", name="trainings_requests_answers")
     */
    public function trainingsRequestsAnswersAction(Request $request)
    {
        $trainingCampain = $this->getDoctrine()->getRepository(TrainingCampain::class)->findOneBy(["id" => $request->get("training"), "user" => $this->getUser()->getUser()]);

        if (!$trainingCampain) {
            return new JsonResponse([
                "success" => false
            ]);
        }

        $trainingRequest = $this->getDoctrine()->getRepository(TrainingRequest::class)->findOneBy(["trainingCampain" => $trainingCampain, "id" => $request->get("request")]);

        if (!$trainingRequest) {
            return new JsonResponse([
                "success" => false
            ]);
        }

        return new JsonResponse([
            "success" => true,
            "html" => $this->renderView('user/includes/training_answers.html.twig', [
                "trainingRequest" => $trainingRequest
            ])
        ]);
    }

    /**
     * @Route("/trainings/{training}/stats", name="trainings_requests_stats")
     */
    public function trainingsRequestsStatsAction(Request $request)
    {
        $trainingCampain = $this->getDoctrine()->getRepository(TrainingCampain::class)->findOneBy(["id" => $request->get("training"), "user" => $this->getUser()->getUser()]);

        if (!$trainingCampain) {
            return new JsonResponse([
                "success" => false
            ]);
        }

        $questionsChoices = [];
        $questionsChoicesTotal = [];
        $questionsCount = [];

        foreach ($trainingCampain->getQuestions() as $questionKey => $question) {
            $questionsChoices[$questionKey] = $question["choices"];
            $questionsCount[$questionKey] = 0;
            
            $questionsChoicesTotal[$questionKey] = [];
            foreach ($question["choices"] as $choiceKey => $choice) {
                $questionsChoicesTotal[$questionKey][$choiceKey] = 0;
            }
        }

        $trainingRequests = $this->getDoctrine()->getRepository(TrainingRequest::class)->findBy(["trainingCampain" => $trainingCampain]);

        foreach ($trainingRequests as $trainingRequest) {
            if ($trainingRequest->getAnswerDate()) {
                foreach ($trainingCampain->getQuestions() as $key => $item) {
                    if (isset($questionsChoices[$key])) {
                        if ($item["choices"] == $questionsChoices[$key]) {
                            $questionsCount[$key] = $questionsCount[$key]+1;
                            foreach ($item["choices"] as $choiceKey => $choice) {
                                if ($item["multiple"]) {
                                    if (in_array($choiceKey, $trainingRequest->getUserAnswers()[$key])) {
                                        $questionsChoicesTotal[$key][$choiceKey] = $questionsChoicesTotal[$key][$choiceKey]+1;
                                    }
                                } else {
                                    if ($choiceKey == $trainingRequest->getUserAnswers()[$key]) {
                                        $questionsChoicesTotal[$key][$choiceKey] = $questionsChoicesTotal[$key][$choiceKey]+1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return new JsonResponse([
            "success" => true,
            "html" => $this->renderView('user/includes/training_stats.html.twig', [
                "trainingCampain" => $trainingCampain,
                "questionsChoices" => $questionsChoices,
                "questionsChoicesTotal" => $questionsChoicesTotal,
                "questionsCount" => $questionsCount,
            ])
        ]);
    }

    /**
     * @Route("/userdocuments/{id}/delete", name="user_documents_delete")
     */
    public function userDocumentsDeleteAction(Request $request, UserDocument $userDocument)
    {
        if ($userDocument->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        $filePath = $this->getParameter('documents_directory') . $userDocument->getFilename();

        $em = $this->getDoctrine()->getManager();
        $em->remove($userDocument);
        $em->flush();

        unlink($filePath);

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute("user_documents");
    }

    /**
     * @Route("/userdocuments/{id}", name="read_user_documents")
     */
    public function readUserDocumentsAction(Request $request, UserDocument $userDocument)
    {
        if ($userDocument->getUser()->getId() != $this->getUser()->getUser()->getId()) {
            if ($userDocument->getSubcontractor()) {
                if (!$userDocument->getSubcontractor()->isGroup() || !$this->getUser()->getUser()->getParentUser() || $userDocument->getUser()->getId() != $this->getUser()->getUser()->getParentUser()->getId()) {
                    throw new NotFoundHttpException();
                }
            } else {
                throw new NotFoundHttpException();
            }
        }

        $filePath = $this->getParameter('documents_directory') . $userDocument->getFilename();

        return $this->file($filePath, $userDocument->getUserFilename(), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/mdpdocuments/{type}/{document}", name="read_mdp_documents")
     */
    public function readMdpDocumentsAction(Request $request, DocumentType $type, Document $document)
    {
        /*if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            return true;
        }*/

        $noSubscription = false;

        if (!$this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            if (!$this->getUser()->getUser()->getCurrentSubscription()) {
                $noSubscription = true;
            } else {
                if (!$this->getUser()->getUser()->getCurrentSubscription()->isActive()) {
                    $noSubscription = true;
                } else {
                    if (!$this->getUser()->getUser()->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $now = new \DateTime("now");
                        if ($now >= $this->getUser()->getUser()->getCurrentSubscription()->getEndDate()) {
                            $noSubscription = true;
                        } else {
                            $allowedSubscriptions = ["ABOPLS", "ABOSTD", "ABOLIB", "PARTEN"];
                            if (!$this->getUser()->getUser()->getCurrentSubscription()->getType() || !in_array($this->getUser()->getUser()->getCurrentSubscription()->getType()->getCode(), $allowedSubscriptions)) {
                                $noSubscription = true;
                            }
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            $this->get('session')->getFlashBag()->add('danger', 'Votre abonnement ne vous permet pas d\'accéder à cette fonctionnalité');
            throw new NotFoundHttpException();
        }

        $filePath = $this->getParameter('documents_directory') . $document->getFilename();

        return $this->file($filePath, $document->getFilename(), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/json/treatments", name="json_treatments")
     */
    public function jsonTreatmentsAction(Request $request)
    {
        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser()], ["name" => "ASC"]);

        $returnResponse = [];

        foreach ($treatments as $treatment) {
            $returnResponse[] = [
                "id" => $treatment->getId(),
                "text" => $treatment->getName(),
            ];
        }

        return new JsonResponse($returnResponse);
    }

    /**
     * @Route("/json/treatmentsgrp", name="json_treatments_grp")
     */
    public function jsonTreatmentsGrpAction(Request $request)
    {
        $treatments = $this->getDoctrine()->getRepository(Treatment::class)->findBy(["user" => $this->getUser()->getUser(), "group" => true], ["name" => "ASC"]);

        $returnResponse = [];

        foreach ($treatments as $treatment) {
            $returnResponse[] = [
                "id" => $treatment->getId(),
                "text" => $treatment->getName(),
            ];
        }

        return new JsonResponse($returnResponse);
    }

    /**
     * @Route("/json/subcontractors", name="json_subcontractors")
     */
    public function jsonSubcontractorsAction(Request $request)
    {
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->searchAllForUser($this->getUser()->getUser(), $_POST['name']);
        } else {
            $subcontractors = $this->getDoctrine()->getRepository(Subcontractor::class)->findAllForUser($this->getUser()->getUser());
        }

        $returnResponse = [];

        foreach ($subcontractors as $subcontractor) {
            $returnResponse[] = [
                "id" => $subcontractor->getId(),
                "text" => $subcontractor->getName(),
                "st" => $subcontractor->getSubcontractorType()->getId(),
            ];
        }

        return new JsonResponse($returnResponse);
    }

    /**
     * @Route("/json/systems", name="json_systems")
     */
    public function jsonSystemsAction(Request $request)
    {
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            $systems = $this->getDoctrine()->getRepository(System::class)->searchAllForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser(), $_POST['name']);
        } else {
            $systems = $this->getDoctrine()->getRepository(System::class)->findForUserWithGroup($this->getUser()->getUser(), $this->getUser()->getUser()->getParentUser());
        }

        $returnResponse = [];

        foreach ($systems as $system) {
            $returnResponse[] = [
                "id" => $system->getId(),
                "text" => $system->getName(),
            ];
        }

        return new JsonResponse($returnResponse);
    }

    /**
     * @Route("/update/treatmentdata", name="update_treatmentdata")
     */
    public function updatetreatmentdataAction(Request $request)
    {
        throw new NotFoundHttpException();

        $em = $this->getDoctrine()->getManager();

        $treatments = $em->getRepository(Treatment::class)->findAll();

        // UPDATE 1
        /*foreach ($treatments as $treatment) {
            if ($treatment->getPersonalData()[5]["title"] == "Données Bancaires") {
                $personnalData = $treatment->getPersonalData();

                $personnalData[5]["title"] = "Données Bancaires (données courantes « non sensible » mais classifié comme tel au vu des risques financiers)";

                $treatment->setPersonalData($personnalData);

                $em->flush();
            }
        }*/

        return new JsonResponse("OK");
    }

    /**
     * @Route("/switch/{id}", name="user_switch")
     */
    public function userSwitchAction(Request $request, User $user)
    {
        if (!$user->getParentUser() || $user->getParentUser()->getId() != $this->getUser()->getUser()->getId()) {
            throw new NotFoundHttpException();
        }

        return $this->redirectToRoute("default_homepage", ['_switch_user' =>  $user->getAccount()->getEmail()]);
    }

    /**
     * @Route("/json/toggleaction", name="json_toggle_action")
     */
    public function getJsonToggleActionAction(Request $request)
    {
        if (isset($_POST['id'])) {
            $em = $this->getDoctrine()->getManager();

            $action = $em->getRepository(Action::class)->find($_POST['id']);

            if ($action) {
                if ($action->getUser()->getId() == $this->getUser()->getUser()->getId() || ($action->getGroupUser() && $action->getGroupUser()->getId() == $this->getUser()->getUser()->getId())) {
                   $action->setTerminated(!$action->isTerminated());

                   $em->flush();

                    return new JsonResponse([
                        "success" => true,
                        "state" => $action->isTerminated()
                    ]);
                }
            }
        }

        return new JsonResponse([
            "success" => false
        ]);
    }

    /**
     * @Route("/json/existingsubcontractorgrp", name="json_existing_subcontractor_grp")
     */
    public function getexistingSubcontractorGrpAction(Request $request)
    {
        if (isset($_POST['terms'])) {
            $subcontractorGrp = $this->getDoctrine()->getRepository(Subcontractor::class)->findExistingGroupForUserAndTerms($this->getUser()->getUser(), $_POST['terms']);

            if ($subcontractorGrp) {
                return new JsonResponse([
                    "existing" => true,
                    "data" => $subcontractorGrp->getName()
                ]);
            }
        }

        return new JsonResponse([
            "existing" => false,
            "data" => null
        ]);
    }

    /**
     * @Route("/json/info", name="json_info")
     */
    public function jsonInfoAction(Request $request)
    {
        if (isset($_GET['id'])) {
            $em = $this->getDoctrine()->getManager();

            $info = $em->getRepository(Info::class)->findOneBy(["id" => $_GET['id'], "enabled" => true]);

            if ($info) {
                return new JsonResponse([
                    'title' => $info->getTitle(),
                    'content' => nl2br($info->getContent())
                ]);
            }
        }

        return new JsonResponse([
            'title' => "",
            'content' => ""
        ]);
    }

    private function formatTreatmentNumber($number) {
        $str = strval($number);
        $strLen = strlen($str);
        $maxLen = 3;
        if ($strLen < $maxLen) {
            for ($i = $strLen; $i < $maxLen; $i++) {
                $str = "0".$str;
            }
        }

        return "T".$str;
    }

    private function isPrintingAllowed(Account $account) {
        if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            return true;
        }

        $noSubscription = false;

        if (!$account->getUser()->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if (!$account->getUser()->getCurrentSubscription()->isActive()) {
                $noSubscription = true;
            } else {
                if (!$account->getUser()->getCurrentSubscription()->getEndDate()) {
                    $noSubscription = true;
                } else {
                    $now = new \DateTime("now");
                    if ($now >= $account->getUser()->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $allowedSubscriptions = ["ABOPLS", "ABOSTD", "ABOLIB", "PARTEN", "FREE30D"];
                        if (!$account->getUser()->getCurrentSubscription()->getType() || !in_array($account->getUser()->getCurrentSubscription()->getType()->getCode(), $allowedSubscriptions)) {
                            $noSubscription = true;
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            $this->get('session')->getFlashBag()->add('danger', 'Votre abonnement ne vous permet pas d\'accéder à cette fonctionnalité');
            return false;
        }

        return true;
    }

    private function isMdpReadDocAllowed(Account $account)
    {
        if ($this->isGranted("ROLE_PREVIOUS_ADMIN")) {
            return true;
        }

        $noSubscription = false;

        if (!$account->getUser()->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if (!$account->getUser()->getCurrentSubscription()->isActive()) {
                $noSubscription = true;
            } else {
                if (!$account->getUser()->getCurrentSubscription()->getEndDate()) {
                    $noSubscription = true;
                } else {
                    $now = new \DateTime("now");
                    if ($now >= $account->getUser()->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $allowedSubscriptions = ["ABOPLS", "ABOSTD", "ABOLIB", "PARTEN"];
                        if (!$account->getUser()->getCurrentSubscription()->getType() || !in_array($account->getUser()->getCurrentSubscription()->getType()->getCode(), $allowedSubscriptions)) {
                            $noSubscription = true;
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            $this->get('session')->getFlashBag()->add('danger', 'Votre abonnement ne vous permet pas d\'accéder à cette fonctionnalité');
            return false;
        }

        return true;
    }

}