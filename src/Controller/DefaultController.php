<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\LoginLog;
use App\Entity\TrainingRequest;
use App\Entity\TrainingRequestHistory;
use App\Services\SendEmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use App\Security\PasswordEncoder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/", name="default_")
 */
class DefaultController extends AbstractController
{
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @Route("/logi", name="homepage")
     */
    public function indexAction(Request $request)
    {
        if ($this->getUser()) {
            if ($this->isGranted("ROLE_MANAGER")) {
                return $this->redirectToRoute("manager_homepage");
            } else {
                return $this->redirectToRoute("user_homepage");
            }
        }

        return $this->redirectToRoute("default_login");
    }

    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request, SendEmailService $sendEmailService)
    {
        $PasswordEncoder = new PasswordEncoder;
        if ($this->getUser()) {
            if ($this->isGranted("ROLE_MANAGER")) {
                return $this->redirectToRoute("manager_homepage");
            } else {
                return $this->redirectToRoute("user_homepage");
            }
        }

        $form = $this->createFormBuilder()
            ->add('username', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Identifiant',
                ),
                'required' => true,
            ))
            ->add('password', PasswordType::class, array(
                'attr' => array(
                    'placeholder' => 'Mot de passe',
                ),
                'required' => true,
            ))->getForm();

        $form->handleRequest($request);
        $error = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $em = $this->getDoctrine()->getManager();

                $account = $em
                    ->getRepository(Account::class)
                    ->findOneBy(["email" => $data["username"]]);
                if ($account) {
                    $isValid = $PasswordEncoder->isPasswordValid($account->getPassword(), $data["password"], $account->getSalt());
                    if ($isValid) {
                        if ($account->isEnabled()) {
                            if (!$account->isLocked()) {
                                $now = new \DateTime("now");
                                $account->setLastLogin($now);

                                $loginLog = new LoginLog();
                                $loginLog->setAccount($account);
                                $loginLog->setDate($now);
                                $loginLog->setEndDate($now);
                                $loginLog->setIp($this->getUserIPAddress());

                                $em->persist($loginLog);

                                $account->setLoginLog($loginLog);

                                $em->flush();
                                $session = $this->get('session');
                                $firewall = 'main';
                                $token = new UsernamePasswordToken($account, null, $firewall, $account->getRoles());
                                $this->get('security.token_storage')->setToken($token);
                                $session->set('_security_'.$firewall, serialize($token));

                                $event = new InteractiveLoginEvent($request, $token);
                                $this->eventDispatcher->dispatch($event, "security.interactive_login");
                                // $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                                return $this->redirectToRoute("default_homepage");
                            } else {
                                $this->get('session')->getFlashBag()->add("danger", "Votre compte a été bloqué en raison d’un nombre d’erreur de connexion trop important. Veuillez contacter votre délégué à la protection des données.");
                            }
                        } else {
                            $this->get('session')->getFlashBag()->add("danger", "Votre compte n'est pas actif");
                        }
                    } else {
                        if ($account->getUser()) {
                            $account->setErroredLoginCount($account->getErroredLoginCount()+1);
                            if ($account->getErroredLoginCount() == 5) {
                                $account->setLocked(true);
                                $em->flush();

                                $content = "<p>Bonjour,<br/>
                        <br/>
                        Une compte utilisateur du client ".$account->getUser()->getCompanyName()." a été bloqué suite à un nombre trop important de tentatives de connexion.<br/>
                        <br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site Pilot. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
                                $sendEmailService->send(
                                    "Compte utilisateur bloqué",
                                    $account->getUser()->getManager()->getEmail(),
                                    'template_emails/left_text.html.twig',
                                    [
                                        "title" => "Compte utilisateur bloqué",
                                        "content" => $content
                                    ]
                                );
                            }
                            if ($account->getErroredLoginCount() >= 5) {
                                $this->get('session')->getFlashBag()->add("danger", "Votre compte a été bloqué en raison d’un nombre d’erreur de connexion trop important. Veuillez contacter votre délégué à la protection des données.");
                            } else {
                                $this->get('session')->getFlashBag()->add("danger", "Identifiant ou mot de passe incorrect");
                            }
                        } else {
                            $this->get('session')->getFlashBag()->add("danger", "Identifiant ou mot de passe incorrect");
                        }
                    }
                } else {
                    $this->get('session')->getFlashBag()->add("danger", "Identifiant ou mot de passe incorrect");
                }
            } else {
                $this->get('session')->getFlashBag()->add("warning", "Merci de saisir votre identifiant et votre mot de passe");
            }
        }

        return $this->render('login.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();
        return $this->redirectToRoute("default_homepage");
    }

    /**
     * @Route("/logoutabo", name="logout_abo")
     */
    public function logoutAboAction(Request $request)
    {
        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();
        $this->get('session')->getFlashBag()->add('danger', 'Vous ne disposez d\'aucun abonnement actif. Veuillez contacter votre interlocuteur myDiditplace');
        return $this->redirectToRoute("default_homepage");
    }

    /**
     * @Route("/forgot", name="forgot_password")
     */
    public function forgotPasswordAction(Request $request, SendEmailService $sendEmailService)
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, array(
                'attr' => array(
                    'placeholder' => 'Adresse mail',
                ),
                'required' => true,
            ))
            ->getForm();

        $form->handleRequest($request);
        $error = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $em = $this->getDoctrine()->getManager();

                $account = $em->getRepository(Account::class)->findOneBy(["email" => $data["email"]]);

                if ($account) {
                    if ($account->isEnabled()) {
                        if (!$account->isLocked()) {
                            $token = hash("sha256", uniqid());
                            $account->setPasswordRequest($token);
                            $account->setPasswordRequestDate(new \DateTime('now'));

                            $em->flush();

                            if ($account->getUser()) {
                                $firstName = $account->getUser()->getContactFirstName();
                            } else {
                                $firstName = $account->getManager()->getFirstName();
                            }

                            $content = "<p>Bonjour ".$firstName.",<br/>
                        <br/>
                        Vous venez de demander à réinitialiser votre mot de passe sur le site Pilot.<br/>
                        <br/>
                        <br/>
                        Voici votre lien pour définir un nouveau mot de passe:<br/><a href='".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."'>
                        ".$this->generateUrl("default_reset_password", ["email" => $account->getEmail(), "token" => $token], UrlGeneratorInterface::ABSOLUTE_URL)."
                        </a>
                        <br/>
                        <br/>
                        Si le lien n'est pas cliquable, collez le dans la barre d'adresse de votre navigateur.<br/><br/>
                        Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce message. Votre compte est toujours sécurisé.<br/>
                        <br/>
                        Bien cordialement,<br/>
                        <br/>
                        L’équipe Pilot<br/>
                        <br/>
                        <i>Cet e-mail a été envoyé depuis le site Pilot. NE PAS répondre à ce message automatique.</i><br/>
                        </p>";
                            $sendEmailService->send(
                                "Réinitialisation de votre mot de passe",
                                $account->getEmail(),
                                'template_emails/left_text.html.twig',
                                [
                                    "title" => "Réinitialisation de votre mot de passe",
                                    "content" => $content
                                ]
                            );

                            $this->get('session')->getFlashBag()->add("success", "Un mail contenant un lien de réinitialisation a été envoyé à l'adresse indiquée. Ce lien est valide pendant 24h.");

                            return $this->redirectToRoute("default_login");
                        } else {
                            $this->get('session')->getFlashBag()->add("warning", "Votre compte a été bloqué en raison d’un nombre d’erreur de connexion trop important. Veuillez contacter votre délégué à la protection des données.");
                        }
                    } else {
                        $this->get('session')->getFlashBag()->add("warning", "Votre compte n'est pas actif");
                    }
                } else {
                    $this->get('session')->getFlashBag()->add("danger", "Aucun compte trouvé avec cette adresse");
                }
            } else {
                $this->get('session')->getFlashBag()->add("warning", "Merci de saisir une adresse mail valide");
            }
        }

        return $this->render('forgot_pwd.html.twig', [
            "form" => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset/{email}/{token}", name="reset_password")
     */
    public function resetPasswordAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $account = $em->getRepository(Account::class)->findOneBy(["email" => $request->get("email")]);

        if ($account) {
            if ($account->getPasswordRequest() && $account->getPasswordRequestDate()) {
                if ($account->getPasswordRequest() == $request->get("token")) {
                    $now = new \DateTime('now');
                    if ($now->getTimestamp() - $account->getPasswordRequestDate()->getTimestamp() < 86400) {
                        $form = $this->createFormBuilder()
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
                                ),
                                'second_options' => array('attr' => array('placeholder' => 'Confirmation mot de passe')),
                                'label' => false,
                                'mapped' => false
                            ))->getForm();

                        $form->handleRequest($request);

                        if ($form->isSubmitted() && $form->isValid()) {
                            $salt = md5(uniqid());
                            $pwd = $form['password']->getData();
                            $account->setSalt($salt);
                            $PasswordEncoder = new PasswordEncoder;
                            $enc_pwd = $PasswordEncoder->encodePassword($pwd, $salt);
                            $account->setPassword($enc_pwd);

                            $account->setPasswordRequest(null);
                            $account->setPasswordRequestDate(null);

                            $em->flush();

                            $this->get('session')->getFlashBag()->add("success", "Votre mot de passe a été réinitialisé, vous pouvez maintenant vous connecter à votre espace.");

                            return $this->redirectToRoute("default_login");
                        }

                        return $this->render('reset_pwd.html.twig', [
                            "form" => $form->createView(),
                        ]);
                    } else {
                        throw new NotFoundHttpException("Cette URL n'est plus valide");
                    }
                } else {
                    throw new NotFoundHttpException("Cette URL n'est pas valide");
                }
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * @Route("/training/{email}/{token}", name="training")
     */
    public function trainingAction(Request $request, $email, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $trainingRequest = $em->getRepository(TrainingRequest::class)->findOneBy(["email" => $email, "token" => $token]);

        if (!$trainingRequest) {
            throw new NotFoundHttpException();
        }

        if ($request->get("reset")) {
            $trainingRequest->setAnswerDate(null);
            $trainingRequest->setUserAnswers(null);
            $trainingRequest->setResult(0);

            $em->flush();

            return $this->redirectToRoute("default_training", ["email" => $email, "token" => $token]);
        }

        if ($request->get("answers")) {
            return $this->render('training_answers.html.twig', [
                "trainingRequest" => $trainingRequest
            ]);
        }

        $now = new \DateTime('now');

        $form = $this->createFormBuilder()
            ->add("firstName", TextType::class, [
                "attr" => [
                    "placeholder" => "Prénom"
                ],
                "label" => "Prénom",
                "data" => $trainingRequest->getLastName()
            ])
            ->add("lastName", TextType::class, [
                "attr" => [
                    "placeholder" => "Nom"
                ],
                "label" => "Nom",
                "data" => $trainingRequest->getFirstName()
            ])
            ->add("position", TextType::class, [
                "attr" => [
                    "placeholder" => "Fonction"
                ],
                "label" => "Fonction",
                "data" => $trainingRequest->getPosition()
            ]);

        foreach ($trainingRequest->getTrainingCampain()->getQuestions() as $key => $question) {
            $choices = [];
            foreach ($question["choices"] as $choiceKey => $choiceValue) {
                $choices[$choiceValue] = $choiceKey;
            }

            $this->shuffle_assoc($choices);

            $form->add("question_".$key, ChoiceType::class, [
                "choices" => $choices,
                "expanded" => true,
                "multiple" => $question["multiple"],
            ]);
        }

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $answers = [];

            $goodAnswers = 0;

            foreach ($trainingRequest->getTrainingCampain()->getQuestions() as $key => $question) {
                $answers[$key] = $form["question_".$key]->getData();

                if (is_array($trainingRequest->getTrainingCampain()->getAnswers()[$key])) {
                    if (is_array($answers[$key])) {
                        if ($this->consistsOfTheSameValues($answers[$key], $trainingRequest->getTrainingCampain()->getAnswers()[$key])) {
                            $goodAnswers++;
                        }
                    }
                } else {
                    if ($answers[$key] == $trainingRequest->getTrainingCampain()->getAnswers()[$key]) {
                        $goodAnswers++;
                    }
                }
            }

            $trainingRequest->setFirstName($form["firstName"]->getData());
            $trainingRequest->setLastName($form["lastName"]->getData());
            $trainingRequest->setPosition($form["position"]->getData());

            $trainingRequest->setAnswerDate($now);
            $trainingRequest->setUserAnswers($answers);
            $trainingRequest->setResult($goodAnswers / count($trainingRequest->getTrainingCampain()->getQuestions()));

            $trainingRequestHistory = new TrainingRequestHistory();

            $trainingRequestHistory->setAnswerDate($now);
            $trainingRequestHistory->setUserAnswers($answers);
            $trainingRequestHistory->setResult($goodAnswers / count($trainingRequest->getTrainingCampain()->getQuestions()));
            $trainingRequestHistory->setTrainingRequest($trainingRequest);

            $em->persist($trainingRequestHistory);
            $em->flush();

            return $this->redirectToRoute("default_training", ["email" => $email, "token" => $token]);
        }

        return $this->render('training.html.twig', [
            "form" => $form->createView(),
            "trainingRequest" => $trainingRequest
        ]);
    }

    public function shuffle_assoc(&$array) {
        $keys = array_keys($array);

        shuffle($keys);

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
    }

    public function consistsOfTheSameValues(array $a, array $b)
    {
        // check size of both arrays
        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($b as $key => $bValue) {

            // check that expected value exists in the array
            if (!in_array($bValue, $a, true)) {
                return false;
            }

            // check that expected value occurs the same amount of times in both arrays
            if (count(array_keys($a, $bValue, true)) !== count(array_keys($b, $bValue, true))) {
                return false;
            }

        }

        return true;
    }

    public function getUserIPAddress() {
        //whether ip is from the share internet
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from the proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from the remote address
        else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
