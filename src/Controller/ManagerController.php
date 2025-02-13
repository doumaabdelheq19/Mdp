<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\ActionStd;
use App\Entity\ActionStdDocument;
use App\Entity\Credit;
use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\Info;
use App\Entity\LoginLog;
use App\Entity\Manager;
use App\Entity\Partner;
use App\Entity\Subcontractor;
use App\Entity\SubcontractorStd;
use App\Entity\SubcontractorStdDocument;
use App\Entity\Subscription;
use App\Entity\System;
use App\Entity\SystemStd;
use App\Entity\Training;
use App\Entity\TrainingRequest;
use App\Entity\TrainingRequestHistory;
use App\Entity\Treatment;
use App\Entity\TreatmentStd;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Form\ActionStdTranslateType;
use App\Form\ActionStdType;
use App\Form\InfoType;
use App\Form\ManagerType;
use App\Form\PartnerType;
use App\Form\SubcontractorStdTranslateType;
use App\Form\SubcontractorStdType;
use App\Form\SubcontractorType;
use App\Form\SubscriptionType;
use App\Form\SubscriptionUserType;
use App\Form\SystemStdTranslateType;
use App\Form\SystemStdType;
use App\Form\SystemType;
use App\Form\TrainingTranslateType;
use App\Form\TrainingType;
use App\Form\TreatmentStdTranslateType;
use App\Form\TreatmentStdType;
use App\Form\TreatmentType;
use App\Form\UserType;
use App\Pdf\MyPdf;
use Doctrine\ORM\EntityRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use App\Security\PasswordEncoder;
use Symfony\Component\Validator\Constraints as Assert;
use function Doctrine\ORM\QueryBuilder;
use Qipsius\TCPDFBundle\Controller\TCPDFController;

/**
 * @Route("/manager", name="manager_")
 */
class ManagerController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->redirectToRoute("manager_users");
    }

    /**
     * @Route("/account", name="account")
     */
    public function accountAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $account = $this->getUser();

        $r_email = $account->getEmail();

        $form = $this->createForm(ManagerType::class, $account->getManager());

        $form['email']->setData($account->getEmail());

        $form2 = $this->createFormBuilder()
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'Les mot de passe ne sont pas identiques',
                'first_options'  => array(
                    'attr' => array(
                        'placeholder' => 'Changer mot de passe'
                    ),
                    'constraints' =>[
                        new Assert\NotBlank([
                            'message' => 'Merci de saisir un mot de passe'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^(?:(?=(\S*?[A-Z]){1})(?=(\S*?[a-z]){1})(?=(\S*?[0-9]){1})(?=\S*?[~!^(){}<>%@#&*+=_\-$`,.\/\\\;:\'"|\[\]]){1}.{12,})$/m',
                            'message' => "Votre mot de passe doit respecter les recommandations de l'ANSSI : au moins 12 caractères de types différents (majuscules, minuscules, chiffres, caractères spéciaux)"
                        ])
                    ],
                    'label' => "Nouveau mot de passe"
                ),
                'second_options' => array(
                    'attr' => array(
                        'placeholder' => 'Confirmation mot de passe'
                    ),
                    'label' => "Confirmez le mot de passe"
                ),
                'mapped' => false
            ))->getForm();

        $form->handleRequest($request);
        $form2->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Profil mis à jour');

            if ($r_email != $form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account && $other_account->getId() != $account->getId()) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $account->setEmail($form['email']->getData());
                        $account->getManager()->setEmail($form['email']->getData());

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

            return $this->redirectToRoute('manager_account');
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

            return $this->redirectToRoute('manager_account');
        }

        return $this->render('manager/account.html.twig', [
            'form' => $form->createView(),
            'form2' => $form2->createView(),
        ]);
    }

    /**
     * @Route("/managers", name="managers")
     */
    public function managersAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $managers = $this->getDoctrine()->getRepository(Manager::class)->findAll();

        return $this->render('manager/managers.html.twig', [
            "managers" => $managers
        ]);
    }

    /**
     * @Route("/managers/add", name="managers_add")
     */
    public function managersAddAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $manager = new Manager();

        $form = $this->createForm(ManagerType::class, $manager)
            ->add('role', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Type de compte'
                ],
                'label' => 'Type de compte',
                'choices' => [
                    "A définir" => "ROLE_MANAGER",
                    "Administrateur" => "ROLE_ADMIN",
                    "DPO" => "ROLE_DPO",
                    "Juriste" => "ROLE_JURISTE",
                    "Commerce" => "ROLE_COMMERCE",
                ],
                'required' => true,
                'mapped' => false
            ])
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $manager->setEmail($form['email']->getData());
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail n\'est pas valide');
                }
            }

            if ($manager->getEmail()) {
                $now = new \DateTime("now");

                $em->persist($manager);

                $account = new Account();
                $account->setEmail($manager->getEmail());
                $account->setEnabled(true);
                $account->setManager($manager);
                $account->setRegistrationDate($now);
                $account->setRoles([$form['role']->getData()]);

                $salt = md5(uniqid());
                $pwd = "";
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                for ($i = 0; $i < 12; $i++) {
                    $pwd .= $characters[rand(0, $charactersLength - 1)];
                }
                $account->setSalt($salt);
                $PasswordEncoder = new PasswordEncoder;
                $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
                $account->setPassword($enc_pwd);

                $em->persist($account);

                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Nouveau délégué ajouté');
                return $this->redirectToRoute("manager_managers");
            }
        }

        return $this->render('manager/managers_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/managers/{id}/edit", name="managers_edit")
     */
    public function managersEditAction(Request $request, Manager $manager)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }
        if ($manager->getAccount()->getId() == $this->getUser()->getId()) {
            return $this->redirectToRoute("manager_account");
        }

        $r_email = $manager->getEmail();

        $form = $this->createForm(ManagerType::class, $manager)
            ->add('role', ChoiceType::class, [
                'attr' => [
                    'placeholder' => 'Type de compte'
                ],
                'label' => 'Type de compte',
                'choices' => [
                    "A définir" => "ROLE_MANAGER",
                    "Administrateur" => "ROLE_ADMIN",
                    "DPO" => "ROLE_DPO",
                    "Juriste" => "ROLE_JURISTE",
                    "Commerce" => "ROLE_COMMERCE",
                ],
                'required' => true,
                'mapped' => false
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => "Compte actif",
                'required' => false,
                'mapped' => false
            ])
        ;

        $form['email']->setData($manager->getEmail());
        $form['enabled']->setData($manager->getAccount()->isEnabled());
        $form['role']->setData($manager->getAccount()->getRoles()[0]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $manager->getAccount()->setEnabled($form['enabled']->getData());
            $manager->getAccount()->setRoles([$form['role']->getData()]);

            $this->get('session')->getFlashBag()->add('success', 'Compte mis à jour');

            $em->flush();

            if ($r_email != $form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account && $other_account->getId() != $manager->getAccount()->getId()) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $manager->getAccount()->setEmail($form['email']->getData());
                        $manager->setEmail($form['email']->getData());

                        $em->flush();

                        $this->get('session')->getFlashBag()->add('success', 'Identifiant de connexion modifié');
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail n\'est pas valide');
                }
            }

            return $this->redirectToRoute("manager_managers");
        }

        return $this->render('manager/managers_edit.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/managers/{id}/delete", name="managers_delete")
     */
    public function managersDeleteAction(Request $request, Manager $manager)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository(User::class)->findBy(['manager' => $manager]);
        $documents = $em->getRepository(Document::class)->findBy(['manager' => $manager]);

        if (count($users) == 0 && count($documents) == 0) {
            $em->remove($manager);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Compte délégué supprimé');
        } else {
            $form = $this->createFormBuilder()
                ->add('changeUsers', ChoiceType::class, [
                    'choices' => [
                        "Supprimer les clients" => "0",
                        "Associer à un autre délégué" => "1",
                    ],
                    'data' => '1',
                    'expanded' => true,
                    'multiple' => false,
                ])
                ->add('managerUsers', EntityType::class, [
                    'attr' => array(
                        'placeholder' => 'Délégué à la Protection des Données',
                    ),
                    'label' => 'Délégué à la Protection des Données',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                    'class' => Manager::class,
                    'query_builder' => function (EntityRepository $er) use ( $manager ) {
                        $qb = $er->createQueryBuilder('m');
                        return $qb->where('m.id != :managerId')
                            ->setParameter("managerId", $manager->getId())
                            ->orderBy('m.lastName', 'ASC');
                    },
                    'choice_label' => function(Manager $manager) {
                        return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                    },
                ])
                ->add('changeDocuments', ChoiceType::class, [
                    'choices' => [
                        "Supprimer les documents" => "0",
                        "Associer à un autre délégué" => "1",
                    ],
                    'data' => '1',
                    'expanded' => true,
                    'multiple' => false,
                ])
                ->add('managerDocuments', EntityType::class, [
                    'attr' => array(
                        'placeholder' => 'Délégué à la Protection des Données',
                    ),
                    'label' => 'Délégué à la Protection des Données',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                    'class' => Manager::class,
                    'query_builder' => function (EntityRepository $er) use ( $manager ) {
                        $qb = $er->createQueryBuilder('m');
                        return $qb->where('m.id != :managerId')
                            ->setParameter("managerId", $manager->getId())
                            ->orderBy('m.lastName', 'ASC');
                    },
                    'choice_label' => function(Manager $manager) {
                        return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                    },
                ])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $error = false;
                if ($form['changeDocuments']->getData() && $form['changeDocuments']->getData() == "1") {
                    if (!$form['managerDocuments']->getData()) {
                        $error = true;
                    }
                }
                if ($form['changeUsers']->getData() && $form['changeUsers']->getData() == "1") {
                    if (!$form['managerUsers']->getData()) {
                        $error = true;
                    }
                }
                if (!$error) {
                    if ($form['changeDocuments']->getData() && $form['changeDocuments']->getData() == "1") {
                        foreach ($documents as $document) {
                            $document->setManager($form['managerDocuments']->getData());
                            $em->flush();
                        }
                    } else {
                        foreach ($documents as $document) {
                            $em->remove($document);
                            $em->flush();
                        }
                    }
                    if ($form['changeUsers']->getData() && $form['changeUsers']->getData() == "1") {
                        foreach ($users as $user) {
                            $user->setManager($form['managerUsers']->getData());
                        }
                    } else {
                        foreach ($users as $user) {
                            $em->remove($user);
                            $em->flush();
                        }
                    }

                    $em->remove($manager);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('success', 'Compte délégué supprimé');

                    return $this->redirectToRoute("manager_managers");
                }
            }

            return $this->render('manager/managers_delete.html.twig', [
                "form" => $form->createView(),
            ]);
        }

        return $this->redirectToRoute("manager_managers");
    }

    /**
     * @Route("/currentsubscriptions", name="current_subscriptions")
     */
    public function currentSubscriptionsAction(Request $request)
    {
        if ($this->isGranted("ROLE_DPO")) {
            $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        } elseif ($this->isGranted("ROLE_JURISTE")) {
            $users = $this->getDoctrine()->getRepository(User::class)->findForManager($this->getUser()->getManager());
        } else {
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(['demo' => true]);
        }

        return $this->render('manager/current_subscriptions.html.twig', [
            "users" => $users,
            "now" => new \DateTime("now"),
        ]);
    }

    /**
     * @Route("/users", name="users")
     */
    public function usersAction(Request $request)
    {
        if ($this->isGranted("ROLE_DPO")) {
            $users = $this->getDoctrine()->getRepository(User::class)->findAll();
        } elseif ($this->isGranted("ROLE_JURISTE")) {
            $users = $this->getDoctrine()->getRepository(User::class)->findForManager($this->getUser()->getManager());
        } else {
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(['demo' => true]);
        }

        return $this->render('manager/users.html.twig', [
            "users" => $users
        ]);
    }

    /**
     * @Route("/users/add", name="users_add")
     */
    public function usersAddAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        if ($this->isGranted("ROLE_DPO")) {
            $isAdmin = true;
        } else {
            $isAdmin = false;
        }
        $manager = $this->getUser()->getManager();

        $form->add('parentUser', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Compte client principal',
                ),
                'label' => 'Compte client principal',
                'placeholder' => 'Compte client principal',
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ( $user, $manager, $isAdmin ) {
                    $qb = $er->createQueryBuilder('u');
                    if (!$isAdmin) {
                        $qb->andWhere("u.manager = :manager")
                        ->setParameter("manager", $manager);
                    }
                    return $qb->orderBy('u.companyName', 'ASC');
                },
                'choice_label' => function(User $queryUser) {
                    return $queryUser->getCompanyName();
                },
            ])
            ->add('childrenUsers', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Comptes clients rattachés',
                ),
                'label' => 'Comptes clients rattachés',
                'placeholder' => 'Comptes clients rattachés',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ( $user, $manager, $isAdmin ) {
                    $qb = $er->createQueryBuilder('u');
                    if (!$isAdmin) {
                        $qb->andWhere("u.manager = :manager")
                            ->setParameter("manager", $manager);
                    }
                    return $qb->orderBy('u.companyName', 'ASC');
                },
                'choice_label' => function(User $queryUser) {
                    return $queryUser->getCompanyName();
                },
            ])
            ;

        if ($this->isGranted("ROLE_DPO")) {
            $form->add('manager', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Délégué à la Protection des Données',
                ),
                'label' => 'Délégué à la Protection des Données',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'data' => $this->getUser()->getManager(),
                'class' => Manager::class,
                'choice_label' => function(Manager $manager) {
                    return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                },
            ])
                ->add('managerDpo', CheckboxType::class, [
                'label' => "Délégué à la Protection des Données",
                'required' => false
            ])
                ->add('lawyer', EntityType::class, [
                    'attr' => array(
                        'placeholder' => 'Juriste',
                    ),
                    'label' => 'Juriste',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'class' => Manager::class,
                    'choice_label' => function(Manager $manager) {
                        return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                    },
                ]);
        } else {
            $form->add('managerDpo', CheckboxType::class, [
                'label' => "Vous êtes DPO pour ce client",
                'required' => false
            ]);
        }

        $form->add('demo', CheckboxType::class, [
            'label' => "Compte de démo pour les commerciaux",
            'required' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $user->setEmail($form['email']->getData());
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail n\'est pas valide');
                }
            }

            if ($user->getEmail()) {
                $now = new \DateTime("now");

                if (!$this->isGranted("ROLE_DPO")) {
                    $user->setManager($this->getUser()->getManager());
                }

                $em->persist($user);

                $account = new Account();
                $account->setEmail($user->getEmail());
                $account->setEnabled(true);
                $account->setUser($user);
                $account->setRegistrationDate($now);
                $account->setRoles(["ROLE_USER"]);

                $salt = md5(uniqid());
                $pwd = "";
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                for ($i = 0; $i < 12; $i++) {
                    $pwd .= $characters[rand(0, $charactersLength - 1)];
                }
                $account->setSalt($salt);
                $PasswordEncoder = new PasswordEncoder;
                $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
                $account->setPassword($enc_pwd);

                $em->persist($account);

                $em->flush();

                if ($form['childrenUsers']->getData()) {
                    foreach ($form['childrenUsers']->getData() as $childUser) {
                        $tmpChildUser = $em->getRepository(User::class)->find($childUser->getId());
                        if ($tmpChildUser) {
                            $tmpChildUser->setParentUser($user);
                            $em->flush();
                        }
                    }
                }

                $beginDate = new \DateTime("now");
                $beginDate->setTime(0, 0, 0);
                $endDate = clone $beginDate;
                $endDate->sub(new \DateInterval("P1D"));
                $endDate->add(new \DateInterval("P30D"));

                $defaultSubscriptionType = $em->getRepository(\App\Entity\SubscriptionType::class)->findOneBy(["code" => "FREE30D"]);
                $defaultSubcription = new Subscription();
                $defaultSubcription->setCreationDate($beginDate);
                $defaultSubcription->setBeginDate($beginDate);
                $defaultSubcription->setEndDate($endDate);
                $defaultSubcription->setPaymentUntil($endDate);
                $defaultSubcription->setInvolvementMonths(0);
                $defaultSubcription->setBillingMonths(0);
                $defaultSubcription->setUnitBillingPrice(0);
                $defaultSubcription->setActive(true);
                $defaultSubcription->setUser($user);
                $defaultSubcription->setType($defaultSubscriptionType);

                $em->persist($defaultSubcription);
                $em->flush();

                $user->setCurrentSubscription($defaultSubcription);

                $em->flush();

                if ($user->getParentUser()) {
                    $parentTreatments = $em->getRepository(Treatment::class)->findBy(["user" => $user->getParentUser(), "group" => true]);
                    foreach ($parentTreatments as $parentTreatment) {
                        $user->getGroupTreatments()->add($parentTreatment);
                    }
                    $em->flush();
                }
                if ($user->getChildrenUsers()) {
                    $parentTreatments = $em->getRepository(Treatment::class)->findBy(["user" => $user, "group" => true]);
                    foreach ($user->getChildrenUsers() as $childUser) {
                        foreach ($parentTreatments as $parentTreatment) {
                            if (!$childUser->getGroupTreatments()->contains($parentTreatment)) {
                                $childUser->getGroupTreatments()->add($parentTreatment);
                            }
                        }
                    }
                    $em->flush();
                }

                $this->get('session')->getFlashBag()->add('success', 'Nouveau client ajouté');
                $this->get('session')->getFlashBag()->add('success', 'Abonnement attribué au nouveau client : '.$defaultSubscriptionType->getLibelle());

                if ($this->isGranted("ROLE_ADMIN")) {
                    return $this->redirectToRoute("manager_subscriptions_user", ["id" => $user->getId()]);
                } else {
                    return $this->redirectToRoute("manager_users");
                }
            }
        }

        return $this->render('manager/users_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/users/{id}/edit", name="users_edit")
     */
    public function usersEditAction(Request $request, User $user)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            if ($user->getManager()->getId() != $this->getUser()->getManager()->getId()) {
                throw new NotFoundHttpException();
            }
        }

        if ($this->isGranted("ROLE_DPO")) {
            $isAdmin = true;
        } else {
            $isAdmin = false;
        }
        $manager = $this->getUser()->getManager();

        $r_email = $user->getEmail();

        $hasParentUser = ($user->getParentUser() != null);

        $childUsersIds = [];
        foreach ($user->getChildrenUsers() as $childUser) {
            $childUsersIds[] = $childUser->getId();
        }

        $form = $this->createForm(UserType::class, $user)
            ->add('enabled', CheckboxType::class, [
                'label' => "Compte actif",
                'required' => false,
                'mapped' => false
            ])
        ;

        $form->add('parentUser', EntityType::class, [
            'attr' => array(
                'placeholder' => 'Compte client principal',
            ),
            'label' => 'Compte client principal',
            'placeholder' => 'Compte client principal',
            'expanded' => false,
            'multiple' => false,
            'required' => false,
            'class' => User::class,
            'query_builder' => function (EntityRepository $er) use ( $user, $manager, $isAdmin ) {
                $qb = $er->createQueryBuilder('u');

                $qb->where('u.id != :userId')
                    ->setParameter("userId", $user->getId());

                if (!$isAdmin) {
                    $qb->andWhere("u.manager = :manager")
                        ->setParameter("manager", $manager);
                }

                return $qb->orderBy('u.companyName', 'ASC');
            },
            'choice_label' => function(User $queryUser) {
                return $queryUser->getCompanyName();
            },
        ])
            ->add('childrenUsers', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Comptes clients rattachés',
                ),
                'label' => 'Comptes clients rattachés',
                'placeholder' => 'Comptes clients rattachés',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) use ( $user, $manager, $isAdmin ) {
                    $qb = $er->createQueryBuilder('u');

                    $qb->where('u.id != :userId')
                        ->setParameter("userId", $user->getId());

                    if (!$isAdmin) {
                        $qb->andWhere("u.manager = :manager")
                            ->setParameter("manager", $manager);
                    }

                    return $qb->orderBy('u.companyName', 'ASC');
                },
                'choice_label' => function(User $queryUser) {
                    return $queryUser->getCompanyName();
                },
            ])
            ;

        if ($this->isGranted("ROLE_DPO")) {
            $form->add('manager', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Délégué à la Protection des Données',
                ),
                'label' => 'Délégué à la Protection des Données',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'class' => Manager::class,
                'choice_label' => function(Manager $manager) {
                    return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                },
            ])
                ->add('managerDpo', CheckboxType::class, [
                'label' => "Délégué à la Protection des Données",
                'required' => false
            ])
                ->add('lawyer', EntityType::class, [
                    'attr' => array(
                        'placeholder' => 'Juriste',
                    ),
                    'label' => 'Juriste',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'class' => Manager::class,
                    'choice_label' => function(Manager $manager) {
                        return $manager->getFirstName().' '.$manager->getLastName().' ('.$manager->getCompanyName().')';
                    },
                ]);
        } else {
            $form->add('managerDpo', CheckboxType::class, [
                'label' => "Vous êtes DPO pour ce client",
                'required' => false
            ]);
        }

        $form['email']->setData($user->getEmail());
        $form['enabled']->setData($user->getAccount()->isEnabled());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $user->getAccount()->setEnabled($form['enabled']->getData());

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = $user->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $user->setPicture($fileName);
            }

            $this->get('session')->getFlashBag()->add('success', 'Compte mis à jour');

            $em->flush();

            if (!$hasParentUser && $user->getParentUser()) {
                $parentTreatments = $em->getRepository(Treatment::class)->findBy(["user" => $user->getParentUser(), "group" => true]);
                foreach ($parentTreatments as $parentTreatment) {
                    $user->getGroupTreatments()->add($parentTreatment);
                }
                $em->flush();
            } elseif ($hasParentUser && !$user->getParentUser()) {
                $user->getGroupTreatments()->clear();
                $em->flush();
            }

            $parentTreatments = $em->getRepository(Treatment::class)->findBy(["user" => $user, "group" => true]);

            if ($form['childrenUsers']->getData()) {
                foreach ($form['childrenUsers']->getData() as $childUser) {
                    $keyExistingChildUser = array_search($childUser->getId(), $childUsersIds);
                    if ($keyExistingChildUser !== FALSE) {
                        unset($childUsersIds[$keyExistingChildUser]);
                    } else {
                        $tmpChildUser = $em->getRepository(User::class)->find($childUser->getId());
                        if ($tmpChildUser) {
                            $tmpChildUser->setParentUser($user);
                            $em->flush();

                            foreach ($parentTreatments as $parentTreatment) {
                                if (!$tmpChildUser->getGroupTreatments()->contains($parentTreatment)) {
                                    $tmpChildUser->getGroupTreatments()->add($parentTreatment);
                                }
                            }
                            $em->flush();
                        }
                    }
                }
            }
            foreach ($childUsersIds as $childUserId) {
                $tmpChildUser = $em->getRepository(User::class)->find($childUserId);
                if ($tmpChildUser) {
                    $tmpChildUser->setParentUser(null);
                    $em->flush();

                    foreach ($parentTreatments as $parentTreatment) {
                        if ($tmpChildUser->getGroupTreatments()->contains($parentTreatment)) {
                            $tmpChildUser->getGroupTreatments()->removeElement($parentTreatment);
                        }
                    }
                    $em->flush();
                }
            }

            if ($r_email != $form['email']->getData()) {
                if (filter_var($form['email']->getData(), FILTER_VALIDATE_EMAIL)) {
                    $other_account = $em->getRepository(Account::class)->findOneBy(['email' => $form['email']->getData()]);
                    if ($other_account && $other_account->getId() != $user->getAccount()->getId()) {
                        $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail est déjà utilisée par un autre utilisateur');
                    } else {
                        $user->getAccount()->setEmail($form['email']->getData());
                        $user->setEmail($form['email']->getData());

                        $em->flush();

                        $this->get('session')->getFlashBag()->add('success', 'Identifiant de connexion modifié');
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', 'Cette adresse mail n\'est pas valide');
                }
            }

            return $this->redirectToRoute("manager_users");
        }

        return $this->render('manager/users_edit.html.twig', [
            "form" => $form->createView(),
            "user" => $user
        ]);
    }

    /**
     * @Route("/users/{id}/delete", name="users_delete")
     */
    public function usersDeleteAction(Request $request, User $user)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            //if ($user->getManager()->getId() != $this->getUser()->getManager()->getId()) {
                throw new NotFoundHttpException();
            //}
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($user);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Compte client supprimé');
        return $this->redirectToRoute("manager_users");
    }

    /**
     * @Route("/users/{id}/print", name="users_print")
     */
    public function usersPrintAction(Request $request, User $user)
    {
        $tcpdf = new TCPDFController('TCPDF');
        $pdf = $tcpdf->create('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetPrintHeader(false);
        $pdf->SetAuthor('myDigitplace');
        $pdf->SetTitle("Fiche société");
        $pdf->AddPage('P', 'A4');

        $html = $this->renderView('manager/pdf/user.html.twig', [
            "user" => $user
        ]);

        $filename = 'fiche_societe';

        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        return $pdf->Output($filename.".pdf",'I');
    }

    /**
     * @Route("/documents", name="documents")
     */
    public function documentsAction(Request $request)
    {
        $documentsTypes = $this->getDoctrine()->getRepository(DocumentType::class)->findBy(["parent" => null]);

        $typesChoices = [];

        $types = $this->getDoctrine()->getRepository(DocumentType::class)->findBy(["parent" => null]);
        foreach ($types as $type) {
            $typesChoices[] = (object) ['value' => $type->getId(), 'label' => $type->getLibelle()];
            foreach ($type->getChildren() as $child) {
                $typesChoices[] = (object) ['value' => $child->getId(), 'label' => "|----".$child->getLibelle()];
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
            ->add('type', ChoiceType::class, [
                'attr' => array(
                    'placeholder' => 'Type',
                ),
                'label' => 'Type',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'mapped' => false,
                'choices' => $typesChoices,
                'choice_label' => function($entry) { return $entry!=null ? $entry->label : ""; },
                'choice_value' => function($entry) { return $entry!=null ? $entry->value : 0; },
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('file')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getManager()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $docTitle = $form['name']->getData()??$file->getClientOriginalName();
                $document = new Document();
                $document->setName($docTitle);
                $document->setFilename($fileName);
                $document->setManager($this->getUser()->getManager());

                if ($form['type']->getData()) {
                    $type = $em->getRepository(DocumentType::class)->find($form['type']->getData()->value);
                    if ($type) {
                        $document->setType($type);
                    }
                }

                $em->persist($document);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Nouveau document téléversé');
            }


            return $this->redirectToRoute("manager_documents");
        }

        return $this->render('manager/documents.html.twig', [
            "documentsTypes" => $documentsTypes,
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/documentstypes", name="documents_types")
     */
    public function documentsTypesAction(Request $request)
    {
        $documentsTypes = $this->getDoctrine()->getRepository(DocumentType::class)->findAll();

        return $this->render('manager/documents_types.html.twig', [
            "documentsTypes" => $documentsTypes,
        ]);
    }

    /**
     * @Route("/documentstypes/add", name="documents_types_add")
     */
    public function documentsTypesAddAction(Request $request)
    {
        $documentType = new DocumentType();

        $form = $this->createFormBuilder($documentType)
            ->add("parent", EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Catégorie principale',
                ),
                'label' => 'Catégorie principale',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'class' => DocumentType::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('dt');
                    return $qb->where($qb->expr()->isNull("dt.parent"))
                        ->orderBy('dt.libelle', 'ASC');
                },
                'choice_label' => function(DocumentType $documentType) {
                    return $documentType->getLibelle();
                },
            ])
            ->add("libelle", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom"
                ],
                "label" => "Nom",
            ])
            ->add("libelle_en", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom EN (traduction)"
                ],
                "label" => "Nom EN (traduction)",
                "mapped" => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($documentType);
            $em->flush();

            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');

            $repository
                ->translate($documentType, 'libelle', 'en', $form["libelle_en"]->getData())
            ;

            $em->flush();

            return $this->redirectToRoute("manager_documents_types");
        }

        return $this->render('manager/documents_types_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/documentstypes/{id}/edit", name="documents_types_edit")
     */
    public function documentsTypesEditAction(Request $request, DocumentType $documentType)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');

        $translations = $repository->findTranslations($documentType);

        $form = $this->createFormBuilder($documentType)
            ->add("parent", EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Catégorie principale',
                ),
                'label' => 'Catégorie principale',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'class' => DocumentType::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('dt');
                    return $qb->where($qb->expr()->isNull("dt.parent"))
                        ->orderBy('dt.libelle', 'ASC');
                },
                'choice_label' => function(DocumentType $documentType) {
                    return $documentType->getLibelle();
                },
            ])
            ->add("libelle", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom"
                ],
                "label" => "Nom",
            ])
            ->add("libelle_en", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom EN (traduction)"
                ],
                "label" => "Nom EN (traduction)",
                "mapped" => false,
                "data" => $translations["en"]["libelle"],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($documentType);
            $em->flush();

            $repository
                ->translate($documentType, 'libelle', 'en', $form["libelle_en"]->getData())
            ;

            $em->flush();

            return $this->redirectToRoute("manager_documents_types");
        }

        return $this->render('manager/documents_types_edit.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/documentstypes/{id}/delete", name="documents_types_delete")
     */
    public function documentsTypesDeleteAction(Request $request, DocumentType $documentType)
    {
        $em = $this->getDoctrine()->getManager();

        $parentId = $documentType->getParent()->getId();

        $parentType = $em->getRepository(DocumentType::class)->find($parentId);

        $documents = $em->getRepository(Document::class)->findBy(["type" => $documentType]);

        foreach ($documents as $document) {
            $document->setType($parentType);

            $em->flush();
        }

        $em->remove($documentType);
        $em->flush();

        return $this->redirectToRoute("manager_documents_types");
    }

    /**
     * @Route("/documents/{id}/edit", name="documents_edit")
     */
    public function documentsEditAction(Request $request, Document $document)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');

        $translations = $repository->findTranslations($document);

        $name = $document->getName();
        $filename = $document->getFilename();

        $name_en = "";
        $filename_en = "";

        if (isset($translations["en"])) {
            $name_en = $translations["en"]["name"];
            $filename_en = $translations["en"]["filename"];
        }

        $typesChoices = [];

        $types = $this->getDoctrine()->getRepository(DocumentType::class)->findBy(["parent" => null]);
        foreach ($types as $type) {
            $typesChoices[] = (object) ['value' => $type->getId(), 'label' => $type->getLibelle()];
            foreach ($type->getChildren() as $child) {
                $typesChoices[] = (object) ['value' => $child->getId(), 'label' => "|----".$child->getLibelle()];
            }
        }

        $form = $this->createFormBuilder()
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom'
                ],
                'label' => 'Nom',
                'required' => false,
                'mapped' => false,
                "data" => $name,
            ])
            ->add('file', FileType::class, [
                'attr' => [
                    'placeholder' => 'Fichier'
                ],
                'label' => 'Remplacer le fichier',
                'required' => false,
                'mapped' => false
            ])
            ->add('type', ChoiceType::class, [
                'attr' => array(
                    'placeholder' => 'Type',
                ),
                'label' => 'Type',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'mapped' => false,
                'choices' => $typesChoices,
                'choice_label' => function($entry) { return $entry!=null ? $entry->label : ""; },
                'choice_value' => function($entry) { return $entry!=null ? $entry->value : 0; },
                "data" => (object) ['value' => $document->getType()->getId()],
            ]);

        if ($document->isTranslatedEn()) {
            $form->add('name_en', TextType::class, [
                'attr' => [
                    'placeholder' => ' EN (traduction)'
                ],
                'label' => ' EN (traduction)',
                'required' => false,
                'mapped' => false,
                "data" => $name_en,
            ])
                ->add('file_en', FileType::class, [
                    'attr' => [
                        'placeholder' => 'Fichier'
                    ],
                    'label' => 'Remplacer le fichier EN (traduction)',
                    'required' => false,
                    'mapped' => false
                ]);
        }

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form['type']->getData()) {
                $type = $em->getRepository(DocumentType::class)->find($form['type']->getData()->value);
                if ($type) {
                    $document->setType($type);
                }
            }

            $docTitle = $form['name']->getData()??$filename;
            $document->setName($docTitle);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('file')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getManager()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $docTitle = $form["name"]->getData()??$file->getClientOriginalName();
                $document->setName($docTitle);
                $document->setFilename($fileName);
                $document->setManager($this->getUser()->getManager());

                $em->flush();
            }

            if ($document->isTranslatedEn()) {
                $repository
                    ->translate($document, 'name', 'en', $form["name_en"]->getData())
                ;

                $em->flush();

                /**
                 * @var UploadedFile $file_en
                 */
                $file_en = $form->get('file_en')->getData();
                if ($file_en != NULL) {
                    $fileName = $this->getUser()->getManager()->getId()."_".md5(uniqid()) . '.' . $file_en->guessExtension();

                    $file_en->move(
                        $this->getParameter('documents_directory'), $fileName
                    );

                    $docTitle = $form["name_en"]->getData()??$file_en->getClientOriginalName();
                    $document->setName($docTitle);
                    $document->setFilename($fileName);

                    $repository
                        ->translate($document, 'name', 'en', $docTitle)
                        ->translate($document, 'filename', 'en', $fileName)
                    ;

                    $document->setManager($this->getUser()->getManager());

                    $em->flush();
                }
            }

            return $this->redirectToRoute("manager_documents");
        }

        return $this->render('manager/documents_edit.html.twig', [
            "document" => $document,
            "form" => $form->createView(),
            "filename" => $filename,
            "filename_en" => $filename_en,
        ]);
    }

    /**
     * @Route("/documents/{id}/translate", name="documents_translate")
     */
    public function documentsTranslateAction(Request $request, Document $document)
    {
        $form = $this->createFormBuilder()
            ->add('documentFile', FileType::class, [
                'attr' => [
                    'placeholder' => 'Document EN (traduction)'
                ],
                'label' => 'Document EN (traduction)',
                'required' => true,
                'mapped' => false
            ])
            ->add("libelle_en", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom EN (traduction)"
                ],
                "label" => "Nom EN (traduction)",
                "mapped" => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var UploadedFile $file
             */
            $file = $form->get('documentFile')->getData();
            if ($file != NULL) {
                $fileName = $this->getUser()->getManager()->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('documents_directory'), $fileName
                );

                $docTitle = $form['libelle_en']->getData()??$file->getClientOriginalName();

                $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');

                $repository
                    ->translate($document, 'name', 'en', $docTitle)
                    ->translate($document, 'filename', 'en', $fileName)
                ;

                $document->setTranslatedEn(true);

                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Nouveau document traduit');
            }

            return $this->redirectToRoute("manager_documents");
        }

        return $this->render('manager/documents_translate.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/documents/{id}/delete", name="documents_delete")
     */
    public function documentsDeleteAction(Request $request, Document $document)
    {
        $filePath = $this->getParameter('documents_directory') . $document->getFilename();

        $em = $this->getDoctrine()->getManager();
        $em->remove($document);
        $em->flush();

        unlink($filePath);

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute("manager_documents");
    }

    /**
     * @Route("/switch/{id}", name="user_switch")
     */
    public function userSwitchAction(Request $request, User $user)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            if ($this->isGranted("ROLE_JURISTE") || $this->isGranted("ROLE_COMMERCE")) {
                if ($this->isGranted("ROLE_JURISTE")) {
                    if ($user->getManager()->getId() != $this->getUser()->getManager()->getId()
                        && ($user->getLawyer() && $user->getLawyer()->getId() != $this->getUser()->getManager()->getId())) {
                        throw new NotFoundHttpException();
                    }
                } else {
                    if (!$user->isDemo()) {
                        throw new NotFoundHttpException();
                    }
                }
            } else {
                throw new NotFoundHttpException();
            }
        }

        return $this->redirectToRoute("default_homepage", ['_switch_user' =>  $user->getAccount()->getEmail()]);
    }

    /**
     * @Route("/partners", name="partners")
     */
    public function partnersAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $partners = $this->getDoctrine()->getRepository(Partner::class)->findAll();

        return $this->render('manager/partners.html.twig', [
            "partners" => $partners
        ]);
    }

    /**
     * @Route("/partners/add", name="partners_add")
     */
    public function partnersAddAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $partner = new Partner();

        $form = $this->createForm(PartnerType::class, $partner);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($partner);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = $partner->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $partner->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouveau partenaire ajouté');
            return $this->redirectToRoute("manager_partners");
        }

        return $this->render('manager/partners_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/partners/{id}/edit", name="partners_edit")
     */
    public function partnersEditAction(Request $request, Partner $partner)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(PartnerType::class, $partner);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = $partner->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $partner->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Partenaire mis à jour');

            return $this->redirectToRoute("manager_partners");
        }

        return $this->render('manager/partners_edit.html.twig', [
            "form" => $form->createView(),
            "partner" => $partner
        ]);
    }

    /**
     * @Route("/partners/{id}/delete", name="partners_delete")
     */
    public function partnersDeleteAction(Request $request, Partner $partner)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($partner);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Partenaire supprimé');
        return $this->redirectToRoute("manager_partners");
    }

    /**
     * @Route("/systems", name="systems")
     */
    public function systemsAction(Request $request)
    {
        // Fetch systems
        $systemsQuery = $this->getDoctrine()->getRepository(SystemStd::class)->findAll();
    
        // Define system categories (unchanged)
        $systems = [
            "computing" => [
                "network" => [], "security" => [], "administration" => [], "device" => [], "software" => [], "server" => []
            ],
            "physical" => [
                "partitioning" => [], "information" => []
            ],
            "action" => [
                "minimization" => [], "anonymization" => [], "pseudonymization" => [], "sensitization" => [], "supervision" => [], "destruction" => []
            ],
            "supplier" => [
                "supplier" => []
            ]
        ];
    
        // Serialize systems for frontend (unchanged)
        $systemsJs = [];
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        foreach ($systemsQuery as $system) {
            $systems[$system->getType()][$system->getSubtype()][] = $system;
            $systemsJs[$system->getId()] = json_decode($serializer->serialize($system, 'json', [
                "attributes" => ['id', 'name', 'data', 'type', 'subtype'],
                "circular_reference_handler" => fn($object) => $object->getId()
            ]), true);
        }
    
        // Define standard system categories (unchanged)
        $systemsStd = [
            "computing" =>  [
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
        $addedType = $request->query->get('addedType');
        $addedSubtype = $request->query->get('addedSubtype');
        // Build the mind map with meta info
        $mindMap = [
            "meta" => [
                "name" => "Cartographie SI",
                "author" => "myDigitplace",
                "version" => "1.0"
            ],
            "format" => "node_tree",
            "data" => [
                "id" => "root",
                "topic" =>"
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
    
        // Custom French names for nodes
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
                "expanded" => ($type === $addedType),
                "children" => []
            ];
    
            foreach ($categories as $subtype => $details) {
                $subnode = [
                    "id" => "{$type}_{$subtype}",
                    "topic" =>"
                            <div class='border border1'><div class='circle'></div></div>
                            <div class='border border2'><div class='circle'></div></div>
                            <div class='border border3'><div class='circle'></div></div>
                            <div class='border border4'><div class='circle'></div></div>
                            <div class='node-content'>
                                <i class='fa " . $details["icon"] . "'></i>
                                <div class='text-wrapper'>" . $details["label"] . "</div>
                            </div> <span class='node-2-actions'><a href=\"".$this->generateUrl("manager_systems_add")."?type=".$type."&subtype=".$subtype."\" class=\"btn btn-sm btn-rounded-circle btn-primary\"><i class=\"mdi mdi-plus\"></i></a></span>",
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
                            </div>
                            <span class='node-3-actions options'>
                                <a href=\"".$this->generateUrl("manager_systems_edit", ["id" => $item->getId()])."\" class=\"btn edit my-1 mr-1\">
                                    <i class=\"mdi mdi-circle-edit-outline\"></i>
                                </a>
                                <a href=\"".$this->generateUrl("manager_systems_delete", ["id" => $item->getId()])."\" class=\"btn delete my-1\"  onclick=\"return confirm('Confirmer la suppression de cet élément ?');\">
                                    <i class=\"mdi mdi-close\"></i>
                                </a>
                            </span>",
                    ];
                }
    
                $node["children"][] = $subnode;
            }
    
            $mindMap["data"]["children"][] = $node;
        }
    
        return $this->render('manager/systems.html.twig', [
            "systems" => $systems,
            "mindMap" => $mindMap,
            "mindMapHeight" => 12 * (38 * 1.5),
            "systemsJs" => $systemsJs
        ]);
    }
    
    

    /**
     * @Route("/systems/{id}/translate/{_locale}", name="systems_translate")
     */
    public function systemsTranslateAction(Request $request, SystemStd $system)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($system);

        if (!isset($translations[$request->get("_locale")])) {
            return $this->redirectToRoute("manager_systems_gen_translate", ["id" => $system->getId(), "_locale" => "fr", "locale" => $request->get("_locale")]);
        }

        $system = $em->find(SystemStd::class, $request->get("id"));

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

        $form = $this->createForm(SystemStdTranslateType::class, $system);

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

            $em->persist($system);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traduction de l\'élément de cartographie mis à jour');
            return $this->redirectToRoute("manager_systems");
        }

        return $this->render('manager/systems_translate.html.twig', [
            "form" => $form->createView(),
            "system" => $system,
            "fields" => $types[$system->getType()][$system->getSubtype()]
        ]);
    }

    /**
     * @Route("/systems/{id}/gentranslate/{locale}_{_locale}", name="systems_gen_translate")
     */
    public function systemsGanTranslateAction(Request $request, SystemStd $system, $locale, $_locale)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($system);

        if (!isset($translations[$locale])) {
            $repository
                ->translate($system, 'name', $locale, $system->getName())
                ->translate($system, 'data', $locale, $system->getData())
            ;

            $em->persist($system);
            $em->flush();

            return $this->redirectToRoute("manager_systems_translate", ["id" => $system->getId(), "_locale" => $locale]);
        }

        return $this->redirectToRoute("manager_systems");
    }

    /**
     * @Route("/systems/export", name="systems_export")
     */
    public function systemsExportAction(Request $request)
    {
        $systemsQuery = $this->getDoctrine()->getRepository(SystemStd::class)->findAll();

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
        $pdf->SetAuthor('myDigitplace');
        $pdf->SetTitle("Cartographie du SI");
        $pdf->SetMargins(10,22,10, true);
        $pdf->SetAutoPageBreak(TRUE, 35);
        $pdf->AddPage('L', 'A4');

        $html = $this->renderView('manager/pdf/systems.html.twig', [
            "systems" => $systems
        ]);

        $filename = 'Cartographie_du_SI';

        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        return $pdf->Output($filename.".pdf",'I');
    }

    /**
     * @Route("/systems/export/{type}", name="systems_export_excel")
     */
    public function systemsExportExcelAction(Request $request, $type)
    {
        $systemsQuery = $this->getDoctrine()->getRepository(SystemStd::class)->findAll();

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
            "supervision" => "Contrôle et qualité",
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
    }

    /**
     * @Route("/systems/add", name="systems_add")
     */
    public function systemsAddAction(Request $request)
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

        if (!$_GET['type'] || !$_GET['subtype']) {
            throw new NotFoundHttpException();
        }

        if (!key_exists($_GET['type'], $types)) {
            throw new NotFoundHttpException();
        }

        if (!key_exists($_GET['subtype'], $types[$_GET['type']])) {
            throw new NotFoundHttpException();
        }

        $system = new SystemStd();

        $form = $this->createForm(SystemStdType::class, $system);

        switch ($_GET['subtype']) {
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
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
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
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'Protocole'
                        ],
                        'label' => 'Si Wifi, quel protocole ?',
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
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
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
                        'required' => false,
                        'mapped' => false
                    ])
                    ->add('field4', TextType::class, [
                        'attr' => [
                            'placeholder' => 'N° d’identification'
                        ],
                        'label' => 'N° d’identification',
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
                    'required' => false,
                    'mapped' => false
                ])
                    ->add('field2', TextareaType::class, [
                        'attr' => [
                            'placeholder' => 'Informations complémentaires'
                        ],
                        'label' => 'Informations complémentaires',
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

            switch ($_GET['subtype']) {
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
            $system->setType($_GET['type']);
            $system->setSubtype($_GET['subtype']);
            $system->setManager($this->getUser()->getManager());

            $em->persist($system);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Nouvelle mesure technique standard créée');
            return $this->redirectToRoute("manager_systems");
        }

        return $this->render('manager/systems_add.html.twig', [
            "form" => $form->createView(),
            "fields" => $types[$_GET['type']][$_GET['subtype']]
        ]);
    }

    /**
     * @Route("/systems/{id}/edit", name="systems_edit")
     */
    public function systemsEditAction(Request $request, SystemStd $system)
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

        $form = $this->createForm(SystemStdType::class, $system);

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

            $this->get('session')->getFlashBag()->add('success', 'Mise à jour de la mesure technique standard');
            return $this->redirectToRoute("manager_systems");
        }

        return $this->render('manager/systems_edit.html.twig', [
            "form" => $form->createView(),
            "fields" => $types[$system->getType()][$system->getSubtype()]
        ]);
    }

    /**
     * @Route("/systems/{id}/delete", name="systems_delete")
     */
    public function systemsDeleteAction(Request $request, SystemStd $system)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($system);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Suppression de la mesure technique standard');
        return $this->redirectToRoute("manager_systems");
    }

    /**
     * @Route("/treatments", name="treatments")
     */
    public function treatmentsAction(Request $request)
    {
        $treatments = $this->getDoctrine()->getRepository(TreatmentStd::class)->findBy(["user" => null]);
        
        return $this->render('manager/treatments.html.twig', [
            "treatments" => $treatments
        ]);
    }

    /**
     * @Route("/treatments/add", name="treatments_add")
     */
    public function treatmentsAddAction(Request $request)
    {
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
                    'placeholder' => 'Zone de saisie'
                ],
                'label' => 'Zone de saisie',
                'required' => false,
                'mapped' => false
            ]);
            /*    ->add("field_duration_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'Durée de conservation'
                ],
                'label' => 'Durée de conservation',
                'required' => false,
                'mapped' => false
            ])*/
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
            $treatment->setManager($this->getUser()->getManager());

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
            return $this->redirectToRoute("manager_treatments");
        }

        return $this->render('manager/treatments_add.html.twig', [
            "form" => $form->createView(),
            "personalDataFields" => $personalDataFields
        ]);
    }

    /**
     * @Route("/treatments/{id}/edit", name="treatments_edit")
     */
    public function treatmentsEditAction(Request $request, TreatmentStd $treatment)
    {
        if (count($treatment->getPiaCriteria()) == 0) {
            if ($treatment->isAutomatedDecision()) {
                $em = $this->getDoctrine()->getManager();

                $treatment->setPiaCriteria([4]);
                $treatment->setAutomatedDecision(false);

                $em->flush();
                return $this->redirectToRoute("manager_treatments_edit", ["id" => $treatment->getId()]);
            }
        }

        $form = $this->createForm(TreatmentStdType::class, $treatment);

        foreach ($treatment->getPersonalData() as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'Zone de saisie'
                ],
                'label' => 'Zone de saisie',
                'data' => $field['text'],
                'required' => false,
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
            return $this->redirectToRoute("manager_treatments");
        }

        return $this->render('manager/treatments_edit.html.twig', [
            "form" => $form->createView(),
            "treatment" => $treatment,
            "personalDataFields" => $treatment->getPersonalData()
        ]);
    }

    /**
     * @Route("/treatments/{id}/translate/{_locale}", name="treatments_translate")
     */
    public function treatmentsTranslateAction(Request $request, TreatmentStd $treatment)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($treatment);

        if (!isset($translations[$request->get("_locale")])) {
            return $this->redirectToRoute("manager_treatments_gen_translate", ["id" => $treatment->getId(), "locale" => 'en']);
        }

        $treatment = $em->find(TreatmentStd::class, $request->get("id"));

        $form = $this->createForm(TreatmentStdTranslateType::class, $treatment);

        foreach ($treatment->getPersonalData() as $key => $field) {
            $form->add("field_text_".$key, TextType::class, [
                'attr' => [
                    'placeholder' => 'Zone de saisie'
                ],
                'label' => 'Zone de saisie',
                'data' => $field['text'],
                'required' => false,
                'mapped' => false
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personalData = [];

            foreach ($treatment->getPersonalData() as $key => $field) {
                $personalData[] = [
                    "title" => $field['title'],
                    "level" => $field['level'],
                    "text" => $form["field_text_".$key]->getData(),
                ];
            }

            $treatment->setPersonalData($personalData);

            $em->persist($treatment);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traduction du traitement standard mise à jour');
            return $this->redirectToRoute("manager_treatments");
        }

        return $this->render('manager/treatments_edit.html.twig', [
            "form" => $form->createView(),
            "treatment" => $treatment,
            "personalDataFields" => $treatment->getPersonalData()
        ]);
    }

    /**
     * @Route("/treatments/{id}/gentranslate/{locale}", name="treatments_gen_translate")
     */
    public function treatmentsGanTranslateAction(Request $request, TreatmentStd $treatment, $locale)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($treatment);

        if (!isset($translations[$locale])) {
            $repository
                ->translate($treatment, 'name', 'en', $treatment->getName())
                ->translate($treatment, 'mainPurpose', 'en', $treatment->getMainPurpose())
                ->translate($treatment, 'purpose1', 'en', $treatment->getPurpose1())
                ->translate($treatment, 'purpose2', 'en', $treatment->getPurpose2())
                ->translate($treatment, 'purpose3', 'en', $treatment->getPurpose3())
                ->translate($treatment, 'purpose4', 'en', $treatment->getPurpose4())
                ->translate($treatment, 'purpose5', 'en', $treatment->getPurpose5())
                ->translate($treatment, 'othersPurpose', 'en', $treatment->getOthersPurpose())
                ->translate($treatment, 'description', 'en', $treatment->getDescription())
                ->translate($treatment, 'personalData', 'en', $treatment->getPersonalData())
                ->translate($treatment, 'peopleData', 'en', $treatment->getPeopleData())
                ->translate($treatment, 'transferOutsideUeCountries', 'en', $treatment->getTransferOutsideUeCountries())
                ->translate($treatment, 'consentHow', 'en', $treatment->getConsentHow())
                ->translate($treatment, 'legalBasis', 'en', $treatment->getLegalBasis())
                ->translate($treatment, 'dataSource', 'en', $treatment->getDataSource())
            ;

            $em->persist($treatment);
            $em->flush();

            return $this->redirectToRoute("manager_treatments_translate", ["id" => $treatment->getId(), "_locale" => 'en']);
        }

        return $this->redirectToRoute("manager_treatments_translate", ["id" => $treatment->getId(), "_locale" => 'en']);
    }

    /**
     * @Route("/treatments/{id}/delete", name="treatments_delete")
     */
    public function treatmentsDeleteAction(Request $request, TreatmentStd $treatment)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($treatment);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Traitement standard supprimé');
        return $this->redirectToRoute("manager_treatments");
    }

    /**
     * @Route("/subcontractors", name="subcontractors")
     */
    public function subcontractorsAction(Request $request)
    {
        $subcontractors = $this->getDoctrine()->getRepository(SubcontractorStd::class)->findBy([], ["name" => "ASC"]);

        return $this->render('manager/subcontractors.html.twig', [
            "subcontractors" => $subcontractors
        ]);
    }

    /**
     * @Route("/subcontractors/add", name="subcontractors_add")
     */
    public function subcontractorsAddAction(Request $request)
    {
        $subcontractor = new SubcontractorStd();

        $form = $this->createForm(SubcontractorStdType::class, $subcontractor);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $subcontractor->setDate($now);
            $subcontractor->setEditDate($now);
            $subcontractor->setManager($this->getUser()->getManager());

            $em->persist($subcontractor);
            $em->flush();

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
                        $document = new SubcontractorStdDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setManager($this->getUser()->getManager());
                        $document->setSubcontractorStd($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouveau sous-traitant standard ajouté');
            return $this->redirectToRoute("manager_subcontractors");
        }

        return $this->render('manager/subcontractors_add.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/edit", name="subcontractors_edit")
     */
    public function subcontractorsEditAction(Request $request, SubcontractorStd $subcontractor)
    {
        $form = $this->createForm(SubcontractorStdType::class, $subcontractor);

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
                        $document = new SubcontractorStdDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setManager($this->getUser()->getManager());
                        $document->setSubcontractorStd($subcontractor);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Sous-traitant standard mis à jour');
            return $this->redirectToRoute("manager_subcontractors");
        }

        return $this->render('manager/subcontractors_edit.html.twig', [
            "subcontractor" => $subcontractor,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/translate/{_locale}", name="subcontractors_translate")
     */
    public function subcontractorsTranslateAction(Request $request, SubcontractorStd $subcontractor)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($subcontractor);

        if (!isset($translations[$request->get("_locale")])) {
            return $this->redirectToRoute("manager_subcontractors_gen_translate", ["id" => $subcontractor->getId(), "_locale" => "fr", "locale" => $request->get("_locale")]);
        }

        $subcontractor = $em->find(SubcontractorStd::class, $request->get("id"));

        $form = $this->createForm(SubcontractorStdTranslateType::class, $subcontractor);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subcontractor);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traduction du sous-traitant standard mis à jour');
            return $this->redirectToRoute("manager_subcontractors");
        }

        return $this->render('manager/subcontractors_translate.html.twig', [
            "form" => $form->createView(),
            "subcontractor" => $subcontractor,
        ]);
    }

    /**
     * @Route("/subcontractors/{id}/gentranslate/{locale}_{_locale}", name="subcontractors_gen_translate")
     */
    public function subcontractorsGanTranslateAction(Request $request, SubcontractorStd $subcontractor, $locale, $_locale)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($subcontractor);

        if (!isset($translations[$locale])) {
            $repository
                ->translate($subcontractor, 'type', $locale, $subcontractor->getType())
            ;

            $em->persist($subcontractor);
            $em->flush();

            return $this->redirectToRoute("manager_subcontractors_translate", ["id" => $subcontractor->getId(), "_locale" => $locale]);
        }

        return $this->redirectToRoute("manager_subcontractors");
    }

    /**
     * @Route("/subcontractors/{id}/delete", name="subcontractors_delete")
     */
    public function subcontractorsDeleteAction(Request $request, SubcontractorStd $subcontractor)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($subcontractor);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Sous-traitant standard supprimé');
        return $this->redirectToRoute("manager_subcontractors");
    }

    /**
     * @Route("/subcontractors/deletedoc/{subcontractor}/{document}", name="subcontractor_deletedoc")
     */
    public function subcontractorsDeleteDocAction(Request $request, SubcontractorStd $subcontractor, SubcontractorStdDocument $subcontractorStdDocument)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($subcontractorStdDocument);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute('manager_subcontractors_edit', ['id' => $subcontractor->getId()]);
    }

    /**
     * @Route("/actions", name="actions")
     */
    public function actionsAction(Request $request)
    {
        $actions = $this->getDoctrine()->getRepository(ActionStd::class)->findBy([], ["name" => "ASC"]);

        return $this->render('manager/actions.html.twig', [
            "actions" => $actions
        ]);
    }

    /**
     * @Route("/actions/add", name="actions_add")
     */
    public function actionsAddAction(Request $request)
    {
        $action = new ActionStd();

        $form = $this->createForm(ActionStdType::class, $action)
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Annexer des fiches pratiques',
                ),
                'placeholder' => 'Annexer des fiches pratiques',
                'label' => 'Annexer des fiches pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
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

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $action->setDate($now);
            $action->setEditDate($now);
            $action->setManager($this->getUser()->getManager());

            $em->persist($action);
            $em->flush();

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
                        $document = new ActionStdDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setManager($this->getUser()->getManager());
                        $document->setActionStd($action);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouvelle action standard ajoutée');
            return $this->redirectToRoute("manager_actions");
        }

        return $this->render('manager/actions_add.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/actions/{id}/edit", name="actions_edit")
     */
    public function actionsEditAction(Request $request, ActionStd $action)
    {
        $form = $this->createForm(ActionStdType::class, $action)
            ->add('sheets', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Annexer des fiches pratiques',
                ),
                'placeholder' => 'Annexer des fiches pratiques',
                'label' => 'Annexer des fiches pratiques',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
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

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $action->setEditDate(new \DateTime("now"));

            $em->flush();

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
                        $document = new ActionStdDocument();
                        $document->setName($docTitle);
                        $document->setFilename($fileName);
                        $document->setUserFilename($files["name"][$i]);
                        $document->setManager($this->getUser()->getManager());
                        $document->setActionStd($action);
                        $em->persist($document);
                        $em->flush();
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Action standard mise à jour');
            return $this->redirectToRoute("manager_actions");
        }

        return $this->render('manager/actions_edit.html.twig', [
            "action" => $action,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/actions/{id}/translate/{_locale}", name="actions_translate")
     */
    public function actionsTranslateAction(Request $request, ActionStd $action)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($action);

        if (!isset($translations[$request->get("_locale")])) {
            return $this->redirectToRoute("manager_actions_gen_translate", ["id" => $action->getId(), "_locale" => "fr", "locale" => $request->get("_locale")]);
        }

        $action = $em->find(ActionStd::class, $request->get("id"));

        $form = $this->createForm(ActionStdTranslateType::class, $action);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($action);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traduction de l\'action standard mise à jour');
            return $this->redirectToRoute("manager_actions");
        }

        return $this->render('manager/actions_translate.html.twig', [
            "form" => $form->createView(),
            "action" => $action,
        ]);
    }

    /**
     * @Route("/actions/{id}/gentranslate/{locale}_{_locale}", name="actions_gen_translate")
     */
    public function actionsGanTranslateAction(Request $request, ActionStd $action, $locale, $_locale)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($action);

        if (!isset($translations[$locale])) {
            $repository
                ->translate($action, 'name', $locale, $action->getName())
                ->translate($action, 'goal', $locale, $action->getGoal())
                ->translate($action, 'information', $locale, $action->getInformation())
            ;

            $em->persist($action);
            $em->flush();

            return $this->redirectToRoute("manager_actions_translate", ["id" => $action->getId(), "_locale" => $locale]);
        }

        return $this->redirectToRoute("manager_actions");
    }

    /**
     * @Route("/actions/{id}/delete", name="actions_delete")
     */
    public function actionsDeleteAction(Request $request, ActionStd $action)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($action);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Action standard supprimée');
        return $this->redirectToRoute("manager_actions");
    }

    /**
     * @Route("/actions/deletedoc/{action}/{document}", name="actions_deletedoc")
     */
    public function actionsDeleteDocAction(Request $request, ActionStd $action, ActionStdDocument $actionStdDocument)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($actionStdDocument);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document supprimé');

        return $this->redirectToRoute('manager_actions_edit', ['id' => $action->getId()]);
    }

    /**
     * @Route("/infos", name="infos")
     */
    public function infosAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $infos = $this->getDoctrine()->getRepository(Info::class)->findAll();

        return $this->render('manager/infos.html.twig', [
            "infos" => $infos
        ]);
    }

    /**
     * @Route("/infos/add", name="infos_add")
     */
    public function infosAddAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $info = new Info();

        $form = $this->createForm(InfoType::class, $info);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($info);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('filePicture')->getData();
            if ($file != NULL) {
                $fileName = $info->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $info->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouvelle info ajoutée');
            return $this->redirectToRoute("manager_infos");
        }

        return $this->render('manager/infos_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/infos/{id}/edit", name="infos_edit")
     */
    public function infosEditAction(Request $request, Info $info)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(InfoType::class, $info);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('filePicture')->getData();
            if ($file != NULL) {
                $fileName = $info->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $info->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Info mise à jour');

            return $this->redirectToRoute("manager_infos");
        }

        return $this->render('manager/infos_edit.html.twig', [
            "form" => $form->createView(),
            "info" => $info
        ]);
    }

    /**
     * @Route("/infos/{id}/delete", name="infos_delete")
     */
    public function infosDeleteAction(Request $request, Info $info)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($info);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Info supprimée');
        return $this->redirectToRoute("manager_infos");
    }

    /**
     * @Route("/loginlogs/{user}", name="login_logs")
     */
    public function loginLogsAction(Request $request, User $user)
    {
        $loginLogs = $this->getDoctrine()->getRepository(LoginLog::class)->findLast3Months($user);

        return $this->render('manager/login_logs.html.twig', [
            "loginLogs" => $loginLogs
        ]);
    }

    /**
     * @Route("/subscriptions", name="subscriptions")
     */
    public function subscriptionsAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $subscriptions = $this->getDoctrine()->getRepository(Subscription::class)->findAll();

        return $this->render('manager/subscriptions.html.twig', [
            "subscriptions" => $subscriptions
        ]);
    }

    /**
     * @Route("/subscriptions/export", name="subscriptions_export")
     */
    public function subscriptionsExportAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $now = new \DateTime("now");

        $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ["companyName" => "ASC"]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Société');
        $sheet->setCellValue('B1', 'Abonnement');
        $sheet->setCellValue('C1', 'Début');
        $sheet->setCellValue('D1', 'Fin');
        $sheet->setCellValue('E1', 'A facturer');
        $sheet->setCellValue('F1', 'Informations abonnement');
        $sheet->setCellValue('G1', 'Informations facturation');
        $sheet->setCellValue('H1', 'Echéance');
        $sheet->setCellValue('I1', 'Tarif');

        $i = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A'.$i, $user->getCompanyName());
            if ($user->getCurrentSubscription()) {
                $subscription = $user->getCurrentSubscription();
                $sheet->setCellValue('B'.$i, $subscription->getType()?$subscription->getType()->getLibelle():"-");
                $sheet->setCellValue('C'.$i, $subscription->getBeginDate()?$subscription->getBeginDate()->format("d/m/Y"):"-");
                $sheet->setCellValue('D'.$i, $subscription->getEndDate()?$subscription->getEndDate()->format("d/m/Y"):"-");
                if ($subscription->isActive() && ($subscription->getPaymentUntil() == null || $subscription->getPaymentUntil() < $now)) {
                    $sheet->setCellValue('E'.$i, 'OUI');
                } else {
                    $sheet->setCellValue('E'.$i, 'NON');
                }
                $sheet->setCellValue('F'.$i, $subscription->getOffer());
                $sheet->setCellValue('G'.$i, $subscription->getBilling());
                if ($subscription->getBillingMonths() == 12) {
                    $sheet->setCellValue('H'.$i, 'Annuelle');
                } elseif ($subscription->getBillingMonths() == 3) {
                    $sheet->setCellValue('H'.$i, 'Trimestrielle');
                } elseif ($subscription->getBillingMonths() == 1) {
                    $sheet->setCellValue('H'.$i, 'Mensuelle');
                }
                $sheet->setCellValue('I'.$i, $subscription->getUnitBillingPrice());
            }

            $i++;
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="export_abonnements.xlsx"');
        $response->headers->set('Cache-Control','max-age=0');
        return $response;
    }

    /**
     * @Route("/subscriptions/{id}", name="subscriptions_user")
     */
    public function subscriptionsUserAction(Request $request, User $user)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $form = $this->createFormBuilder()
            ->add("type", EntityType::class, [
                'class' => \App\Entity\SubscriptionType::class,
                'attr' => array(
                    'placeholder' => 'Type d\'abonnement',
                ),
                'label' => 'Type d\'abonnement',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute("manager_subscriptions_user_add", ["user" => $user->getId(), "subscriptionType" => $form["type"]->getData()->getId()]);
        }

        $subscriptions = $this->getDoctrine()->getRepository(Subscription::class)->findBy(["user" => $user]);

        return $this->render('manager/subscriptions_user.html.twig', [
            "user" => $user,
            "subscriptions" => $subscriptions,
            "form" => $form->createView(),
            "now" => new \DateTime("now")
        ]);
    }

    /**
     * @Route("/subscriptions/{user}/add/{subscriptionType}", name="subscriptions_user_add")
     */
    public function subscriptionsUserAddAction(Request $request, User $user, \App\Entity\SubscriptionType $subscriptionType)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $hasBillingType = false;
        $hasBillingPrice = false;

        $subscription = new Subscription();

        $subscription->setUser($user);
        $subscription->setType($subscriptionType);

        $form = $this->createForm(SubscriptionUserType::class, $subscription);

        $form["subscriptionType"]->setData($subscriptionType->getLibelle());

        switch ($subscriptionType->getCode()) {
            case "ABOPLS":
            case "ABOSTD":
                $form->add('billingType', ChoiceType::class, [
                        'attr' => [
                            'placeholder' => 'Type de facturation'
                        ],
                        'label' => 'Type de facturation',
                        'choices' => [
                            "Au mois" => "m",
                            "Au trimestre" => "t",
                            "A l'année" => "y",
                        ],
                        "data" => "y",
                        'required' => true,
                        'mapped' => false
                    ])
                    ->add('unitBillingPrice', NumberType::class, [
                        'attr' => [
                            'placeholder' => 'Montant HT facturé'
                        ],
                        'label' => 'Montant HT facturé (selon type de facturation)',
                        'data' => 0
                    ]);
                $hasBillingType = true;
                $hasBillingPrice = true;
                break;
            case "ABOLIB":
                $form->add('unitBillingPrice', NumberType::class, [
                        'attr' => [
                            'placeholder' => 'Montant HT facturé'
                        ],
                        'label' => 'Montant HT facturé au mois',
                        'data' => 0
                    ]);
                $hasBillingPrice = true;
                break;
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $subscription->setCreationDate($now);

            if ($form['beginDate']->getData()) {
                $beginDate = \DateTime::createFromFormat("d/m/Y", $form['beginDate']->getData());
                if ($beginDate) {
                    $beginDate->setTime(0, 0, 0);
                    $subscription->setBeginDate($beginDate);
                    $endDate = clone $subscription->getBeginDate();
                    $endDate->sub(new \DateInterval("P1D"));

                    switch ($subscriptionType->getCode()) {
                        case "ABOPLS":
                        case "ABOSTD":
                            $subscription->setInvolvementMonths(12);
                            $endDate->add(new \DateInterval("P1Y"));
                            break;
                        case "PARTEN":
                            $subscription->setInvolvementMonths(12);
                            $endDate->add(new \DateInterval("P1Y"));
                            break;
                        case "ABOLIB":
                            $subscription->setInvolvementMonths(1);
                            $endDate->add(new \DateInterval("P1M"));
                            break;
                        case "FREE30D":
                        case "DEMO":
                            $subscription->setInvolvementMonths(0);
                            $endDate->add(new \DateInterval("P30D"));
                            break;
                    }

                    $subscription->setEndDate($endDate);

                    if ($hasBillingType) {
                        if ($form['billingType']->getData() == "m") {
                            $subscription->setBillingMonths(1);
                        } elseif ($form['billingType']->getData() == "t") {
                            $subscription->setBillingMonths(3);
                        } else {
                            $subscription->setBillingMonths(12);
                        }
                    } else {
                        $subscription->setBillingMonths(0);
                    }

                    if (!$hasBillingPrice) {
                        $subscription->setUnitBillingPrice(0);
                    }

                    $subscription->setActive(true);

                    $em->persist($subscription);
                    $em->flush();

                    if ($user->getCurrentSubscription()) {
                        $user->getCurrentSubscription()->setActive(false);
                        $em->flush();
                    }
                    $user->setCurrentSubscription($subscription);
                    $em->flush();

                    if ($user->getCurrentSubscription()->getUnitBillingPrice() == 0) {
                        switch ($user->getCurrentSubscription()->getType()->getCode()) {
                            case "ABOPLS":
                            case "ABOSTD":
                            case "PARTEN":
                            case "ABOLIB":
                                $paymentUntil = clone $user->getCurrentSubscription()->getBeginDate();
                                $paymentUntil->sub(new \DateInterval("P1D"));

                                if ($user->getCurrentSubscription()->getBillingMonths()) {
                                    $paymentUntil->add(new \DateInterval("P".$user->getCurrentSubscription()->getBillingMonths()."M"));
                                } else {
                                    $paymentUntil->add(new \DateInterval("P".$user->getCurrentSubscription()->getInvolvementMonths()."M"));
                                }

                                $user->getCurrentSubscription()->setPaymentUntil($paymentUntil);
                                $em->flush();

                                break;
                        }
                    }

                    $this->get('session')->getFlashBag()->add('success', 'Nouvel abonnement ajouté');
                    return $this->redirectToRoute("manager_subscriptions_user", ["id" => $user->getId()]);
                }
            }
        }

        return $this->render('manager/subscriptions_user_add.html.twig', [
            "form" => $form->createView(),
            "hasBillingType" => $hasBillingType,
            "hasBillingPrice" => $hasBillingPrice,
        ]);
    }

    /**
     * @Route("/subscriptions/{user}/disable/{subscription}", name="subscriptions_user_disable")
     */
    public function subscriptionsUserDisableAction(Request $request, User $user, Subscription $subscription)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $subscription->setActive(false);
        $subscription->setEndDate(new \DateTime("now"));

        $em->flush();

        return $this->redirectToRoute("manager_subscriptions_user", ["id" => $user->getId()]);
    }

    /**
     * @Route("/subscriptions/{user}/payment/{subscription}", name="subscriptions_user_payment")
     */
    public function subscriptionsUserPaymentAction(Request $request, User $user, Subscription $subscription)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        if ($subscription->getPaymentUntil() == null) {
            $subscription->setPaymentUntil($subscription->getEndDate());
            switch ($subscription->getType()->getCode()) {
                case "ABOPLS":
                case "ABOSTD":
                case "PARTEN":
                    $subscription->getPaymentUntil()->sub(new \DateInterval("P1Y"));
                    break;
                case "ABOLIB":
                    $subscription->getPaymentUntil()->sub(new \DateInterval("P1M"));
                    break;
            }
        }

        $paymentUntil = clone $subscription->getPaymentUntil();

        if ($subscription->getBillingMonths()) {
            $paymentUntil->add(new \DateInterval("P".$subscription->getBillingMonths()."M"));
        } else {
            $paymentUntil->add(new \DateInterval("P".$subscription->getInvolvementMonths()."M"));
        }

        $subscription->setPaymentUntil($paymentUntil);
        //$subscription->setEndDate($paymentUntil);

        $em->flush();

        return $this->redirectToRoute("manager_subscriptions_user", ["id" => $user->getId()]);
    }

    /**
     * @Route("/subscriptions/add", name="subscriptions_add")
     */
    public function subscriptionsAddAction(Request $request)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $subscription = new Subscription();

        $form = $this->createForm(SubscriptionType::class, $subscription);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $now = new \DateTime("now");

            $subscription->setCreationDate($now);
            $endDate = clone $subscription->getBeginDate();
            $endDate->sub(new \DateInterval("P1D"));
            if ($form['billingType']->getData() == "m") {
                $endDate->add(new \DateInterval("P1M"));
            } else {
                $endDate->add(new \DateInterval("P12M"));
            }
            $subscription->setEndDate($endDate);

            $em->persist($subscription);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Nouvel abonnement ajouté');
            return $this->redirectToRoute("manager_subscriptions");
        }

        return $this->render('manager/subscriptions_add.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/subscriptions/{id}/edit", name="subscriptions_edit")
     */
    public function subscriptionsEditAction(Request $request, Subscription $subscription)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(SubscriptionType::class, $subscription);

        if ($subscription->getBeginDate()->format('Y') != $subscription->getEndDate()->format('Y')) {
            $form['billingType']->setData('y');
        } else {
            $form['billingType']->setData('m');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $endDate = clone $subscription->getBeginDate();
            $endDate->sub(new \DateInterval("P1D"));
            if ($form['billingType']->getData() == "m") {
                $endDate->add(new \DateInterval("P1M"));
            } else {
                $endDate->add(new \DateInterval("P12M"));
            }
            $subscription->setEndDate($endDate);

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Abonnement modifié');
            return $this->redirectToRoute("manager_subscriptions");
        }

        return $this->render('manager/subscriptions_edit.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/subscriptions/{id}/delete", name="subscriptions_delete")
     */
    public function subscriptionsDeleteAction(Request $request, Subscription $subscription)
    {
        if (!$this->isGranted("ROLE_DPO")) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($subscription);
        $em->flush();

        return $this->redirectToRoute("manager_subscriptions");
    }

    /**
     * @Route("/credits/{id}", name="credits_user")
     */
    public function creditsUserAction(Request $request, User $user)
    {
        if (!$this->isGranted("ROLE_MANAGER")) {
            throw new NotFoundHttpException();
        }

        $credit = new Credit();

        $form = $this->createFormBuilder($credit)
            ->add("title", TextType::class, [
                'attr' => array(
                    'placeholder' => 'Action',
                ),
                'label' => 'Action',
                'required' => true,
            ])
            ->add("stock", NumberType::class, [
                'attr' => array(
                    'placeholder' => 'Stock',
                ),
                'label' => 'Stock',
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $credit->convertToDecimal(true);

            $credit->setUser($user);
            $credit->setManager($this->getUser()->getManager());
            $credit->setCreationDate(new \DateTime("now"));

            $em->persist($credit);
            $em->flush();

            $user->setCredit($user->getCredit() + $credit->getStock());

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Crédits modifiés');

            return $this->redirectToRoute("manager_credits_user", ["id" => $user->getId()]);
        }

        $credits = $this->getDoctrine()->getRepository(Credit::class)->findBy(["user" => $user]);

        return $this->render('manager/credits_user.html.twig', [
            "user" => $user,
            "credits" => $credits,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/trainings", name="trainings")
     */
    public function trainingsAction(Request $request)
    {
        $trainings = $this->getDoctrine()->getRepository(Training::class)->findAll();

        return $this->render('manager/trainings.html.twig', [
            "trainings" => $trainings
        ]);
    }

    /**
     * @Route("/trainings/stats", name="trainings_requests_stats")
     */
    public function trainingsStatsAction(Request $request)
    {
        $training = $this->getDoctrine()->getRepository(Training::class)->findOneBy(["id" => $request->get("training")]);
    
        if (!$training) {
            return new JsonResponse([
                "success" => false
            ]);
        }
    
        $questionsChoices = [];
        $questionsChoicesTotal = [];
        $questionsCount = [];
    
        foreach ($training->getQuestions() as $questionKey => $question) {
            $questionsChoices[$questionKey] = $question["choices"];
            $questionsCount[$questionKey] = 0;
    
            $questionsChoicesTotal[$questionKey] = [];
            foreach ($question["choices"] as $choiceKey => $choice) {
                $questionsChoicesTotal[$questionKey][$choiceKey] = 0;
            }
        }
    
        $trainingRequests = $this->getDoctrine()->getRepository(TrainingRequest::class)->findForTraining($training);
    
        foreach ($trainingRequests as $trainingRequest) {
            if ($trainingRequest->getAnswerDate()) {
                foreach ($training->getQuestions() as $key => $item) {
                    if (isset($questionsChoices[$key])) {
                        if ($item["choices"] == $questionsChoices[$key]) {
                            if (isset($trainingRequest->getUserAnswers()[$key])) {
                                $questionsCount[$key] += 1;
                                foreach ($item["choices"] as $choiceKey => $choice) {
                                    if ($item["multiple"]) {
                                        if (in_array($choiceKey, $trainingRequest->getUserAnswers()[$key])) {
                                            $questionsChoicesTotal[$key][$choiceKey] += 1;
                                        }
                                    } else {
                                        if ($choiceKey == $trainingRequest->getUserAnswers()[$key]) {
                                            $questionsChoicesTotal[$key][$choiceKey] += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    
        // ✅ Round percentages to 1 decimal place
       // ✅ Round percentages correctly before passing to Twig
foreach ($questionsChoicesTotal as $qKey => &$choices) {
    foreach ($choices as $cKey => &$count) {
        $total = $questionsCount[$qKey] ?? 1; // Avoid division by zero
        $percentage = ($count / $total) * 100;
        $count = number_format($percentage, 1, '.', ''); // Ensures 1 decimal place
    }
}

    
        return new JsonResponse([
            "success" => true,
            "html" => $this->renderView('manager/includes/training_stats.html.twig', [
                "training" => $training,
                "questionsChoices" => $questionsChoices,
                "questionsChoicesTotal" => $questionsChoicesTotal,
                "questionsCount" => $questionsCount,
            ])
        ]);
    }
    
    

    /**
     * @Route("/trainings/add", name="trainings_add")
     */
    public function trainingsAddAction(Request $request)
    {
        $training = new Training();

        $form = $this->createForm(TrainingType::class, $training)
            ->add('users', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Clients',
                ),
                'placeholder' => 'Clients',
                'label' => 'Clients',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->addOrderBy('u.companyName', "ASC");
                },
                'choice_label' => function(User $user) {
                    return $user->getCompanyName();
                },
            ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($training);

            $training->setAnswered(false);

            $content = [];

            $questions = $_POST['form_items']??[];
            $questions = array_values($questions);

            if (count($questions)) {
                foreach ($questions as $question) {
                    $content[] = [
                        'title' => $question[0]??null,
                        'choices' => explode("\n", str_replace("\r", "", $question[1]??null)),
                     'multiple' => isset($question[2]) && $question[2] == 1 ? true : false,

                        'links' => explode("\n", str_replace("\r", "", $question[3]??null)),
                        'explanations' => $question[4]??null,
                    ];
                }
            }

            $training->setQuestions($content);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = "t".$training->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $training->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Nouveau questionnaire créé');
            return $this->redirectToRoute("manager_trainings");
        }

        $users = [];
        $usersStr = [];

        foreach ($training->getUsers() as $user) {
            $users[] = $user->getId();
            $usersStr[] = $user->getCompanyName();
        }

        sort($usersStr);

        return $this->render('manager/trainings_add.html.twig', [
            "form" => $form->createView(),
            "users" => $users,
            "usersStr" => $usersStr,
        ]);
    }

    /**
     * @Route("/trainings/{id}/edit", name="trainings_edit")
     */
    public function trainingsEditAction(Request $request, Training $training)
    {
        $form = $this->createForm(TrainingType::class, $training)
            ->add('users', EntityType::class, [
                'attr' => array(
                    'placeholder' => 'Clients',
                ),
                'placeholder' => 'Clients',
                'label' => 'Clients',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->addOrderBy('u.companyName', "ASC");
                },
                'choice_label' => function(User $user) {
                    return $user->getCompanyName();
                },
            ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $content = $training->getQuestions();

            $questions_edit = $_POST['form_e_items']??[];
            $questions_edit = array_values($questions_edit);

            foreach ($content as $keyItem => $question) {
                $toRemove = true;
                if (count($questions_edit)) {
                    if (isset($questions_edit[$keyItem])) {
                        $content[$keyItem]['title'] = $questions_edit[$keyItem][0]??null;
                        $content[$keyItem]['choices'] = explode("\n", str_replace("\r", "", $questions_edit[$keyItem][1]??null));
                        $content[$keyItem]['multiple'] = $questions_edit[$keyItem][2]==1?true:false;
                        $content[$keyItem]['links'] = explode("\n", str_replace("\r", "", $questions_edit[$keyItem][3]??null));
                        $content[$keyItem]['explanations'] = $questions_edit[$keyItem][4]??null;

                        if (count($content[$keyItem]['links']) == 1) {
                            if ($content[$keyItem]['links'][0] == "") {
                                $content[$keyItem]['links'] = [];
                            }
                        }

                        $toRemove = false;
                    }
                }

                if ($toRemove) {
                    unset($content[$keyItem]);
                }
            }

            $questions = $_POST['form_items']??[];
            $questions = array_values($questions);

            if (count($questions)) {
                foreach ($questions as $question) {
                    $content[] = [
                        'title' => $question[0]??null,
                        'choices' => explode("\n", str_replace("\r", "", $question[1]??null)),
                        'multiple' => $question[2]==1?true:false,
                        'links' => explode("\n", str_replace("\r", "", $question[3]??null)),
                        'explanations' => $question[4]??null,
                    ];
                }
            }

            if ($training->getQuestions() != $content) {
                $training->setAnswered(false);
            }

            $training->setQuestions($content);

            $em->flush();

            /**
             * @var UploadedFile $file
             */
            $file = $form->get('pictureFile')->getData();
            if ($file != NULL) {
                $fileName = "t".$training->getId()."_".md5(uniqid()) . '.' . $file->guessExtension();

                $file->move(
                    $this->getParameter('pictures_directory'), $fileName
                );

                $training->setPicture($fileName);

                $em->flush();
            }

            $this->get('session')->getFlashBag()->add('success', 'Questionnaire mis à jour');
            return $this->redirectToRoute("manager_trainings");
        }

        $users = [];
        $usersStr = [];

        foreach ($training->getUsers() as $user) {
            $users[] = $user->getId();
            $usersStr[] = $user->getCompanyName();
        }

        sort($usersStr);

        return $this->render('manager/trainings_edit.html.twig', [
            "form" => $form->createView(),
            "training" => $training,
            "users" => $users,
            "usersStr" => $usersStr,
        ]);
    }

    /**
     * @Route("/trainings/{id}/answer", name="trainings_answer")
     */
    public function trainingsAnswerAction(Request $request, Training $training)
    {
        $form = $this->createFormBuilder();

        foreach ($training->getQuestions() as $key => $question) {
            $choices = [];
            foreach ($question["choices"] as $choiceKey => $choiceValue) {
                $choices[$choiceValue] = $choiceKey;
            }

            $answerData = $training->getAnswers()[$key]??null;
            if ($question["multiple"]) {
                if ($answerData != null) {
                    if (!is_array($answerData)) {
                        $answerData = [$answerData];
                    }
                } else {
                    $answerData = [];
                }
            } else {
                if (is_array($answerData)) {
                    $answerData = $answerData[0];
                }
            }

            $form->add("question_".$key, ChoiceType::class, [
                "choices" => $choices,
                "expanded" => true,
                "multiple" => $question["multiple"],
                "data" => $answerData
            ]);
        }

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $training->setAnswered(true);

            $answers = [];

            foreach ($training->getQuestions() as $key => $question) {
                $answers[$key] = $form["question_".$key]->getData();
            }

            $training->setAnswers($answers);

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Questionnaire répondu');
            return $this->redirectToRoute("manager_trainings");
        }

        return $this->render('manager/trainings_answer.html.twig', [
            "form" => $form->createView(),
            "training" => $training
        ]);
    }

    /**
     * @Route("/trainings/{id}/translate/{_locale}", name="trainings_translate")
     */
    public function trainingsTranslateAction(Request $request, Training $training)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($training);

        if (!isset($translations[$request->get("_locale")])) {
            return $this->redirectToRoute("manager_trainings_gen_translate", ["id" => $training->getId(), "_locale" => "fr", "locale" => $request->get("_locale")]);
        }

        $training = $em->find(Training::class, $request->get("id"));

        $form = $this->createForm(TrainingTranslateType::class, $training);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $training->getQuestions();

            $questions_edit = $_POST['form_e_items']??[];
            $questions_edit = array_values($questions_edit);

            foreach ($content as $keyItem => $question) {
                if (count($questions_edit)) {
                    if (isset($questions_edit[$keyItem])) {
                        $content[$keyItem]['title'] = $questions_edit[$keyItem][0]??null;
                        $content[$keyItem]['choices'] = explode("\n", str_replace("\r", "", $questions_edit[$keyItem][1]??null));
                        $content[$keyItem]['links'] = explode("\n", str_replace("\r", "", $questions_edit[$keyItem][3]??null));
                        $content[$keyItem]['explanations'] = $questions_edit[$keyItem][4]??null;

                        if (count($content[$keyItem]['links']) == 1) {
                            if ($content[$keyItem]['links'][0] == "") {
                                $content[$keyItem]['links'] = [];
                            }
                        }
                    }
                }
            }

            $training->setQuestions($content);

            $em->persist($training);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Traduction du questionnaire mis à jour');
            return $this->redirectToRoute("manager_trainings");
        }

        return $this->render('manager/trainings_translate.html.twig', [
            "form" => $form->createView(),
            "training" => $training,
        ]);
    }

    /**
     * @Route("/trainings/{id}/gentranslate/{locale}_{_locale}", name="trainings_gen_translate")
     */
    public function trainingsGanTranslateAction(Request $request, Training $training, $locale, $_locale)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($training);

        if (!isset($translations[$locale])) {
            $repository
                ->translate($training, 'title', $locale, $training->getTitle())
                ->translate($training, 'questions', $locale, $training->getQuestions())
            ;

            $em->persist($training);
            $em->flush();

            return $this->redirectToRoute("manager_trainings_translate", ["id" => $training->getId(), "_locale" => $locale]);
        }

        return $this->redirectToRoute("manager_trainings");
    }

    /**
     * @Route("/trainings/{id}/delete", name="trainings_delete")
     */
    public function trainingsDeleteAction(Request $request, Training $training)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($training);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Questionnaire supprimé');
        return $this->redirectToRoute("manager_trainings");
    }

    /**
     * @Route("/treatments/gentranslations/{locale}", name="treatments_gen_translations")
     */
    public function treatmentsGanTranslationsAction(Request $request, $locale)
    {
        $em = $this->getDoctrine()->getManager();

        $treatments = $em->getRepository(TreatmentStd::class)->findBy(["user" => null]);

        foreach ($treatments as $treatment) {
            $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
            $translations = $repository->findTranslations($treatment);

            if (!isset($translations[$locale])) {
                $repository
                    ->translate($treatment, 'name', 'en', $treatment->getName())
                    ->translate($treatment, 'mainPurpose', 'en', $treatment->getMainPurpose())
                    ->translate($treatment, 'purpose1', 'en', $treatment->getPurpose1())
                    ->translate($treatment, 'purpose2', 'en', $treatment->getPurpose2())
                    ->translate($treatment, 'purpose3', 'en', $treatment->getPurpose3())
                    ->translate($treatment, 'purpose4', 'en', $treatment->getPurpose4())
                    ->translate($treatment, 'purpose5', 'en', $treatment->getPurpose5())
                    ->translate($treatment, 'othersPurpose', 'en', $treatment->getOthersPurpose())
                    ->translate($treatment, 'description', 'en', $treatment->getDescription())
                    ->translate($treatment, 'personalData', 'en', $treatment->getPersonalData())
                    ->translate($treatment, 'peopleData', 'en', $treatment->getPeopleData())
                    ->translate($treatment, 'transferOutsideUeCountries', 'en', $treatment->getTransferOutsideUeCountries())
                    ->translate($treatment, 'consentHow', 'en', $treatment->getConsentHow())
                    ->translate($treatment, 'legalBasis', 'en', $treatment->getLegalBasis())
                    ->translate($treatment, 'dataSource', 'en', $treatment->getDataSource())
                ;

                $em->persist($treatment);
                $em->flush();
            }
        }

        return $this->redirectToRoute("manager_treatments");
    }

    /**
     * @Route("/substitute/{account}", name="substitute")
     */
    public function substituteAction(Request $request, Account $account)
    {
        if ($this->getUser()->getId() == 1) {
            $session = $this->get('session');
            $firewall = 'main';
            $token = new UsernamePasswordToken($account, null, $firewall, $account->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $session->set('_security_'.$firewall, serialize($token));

            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            return $this->redirectToRoute("default_homepage");
        }

        throw new NotFoundHttpException();
    }

    /**
     * @Route("/json/users", name="json_users")
     */
    public function jsonUsersAction(Request $request)
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ["companyName" => "ASC"]);

        $returnResponse = [];

        foreach ($users as $user) {
            $returnResponse[] = [
                "id" => $user->getId(),
                "text" => $user->getCompanyName(),
            ];
        }

        return new JsonResponse($returnResponse);
    }
}
