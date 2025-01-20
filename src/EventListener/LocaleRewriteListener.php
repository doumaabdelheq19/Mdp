<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 28/03/2019
 * Time: 17:23
 */

namespace App\EventListener;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleRewriteListener implements EventSubscriberInterface
{
    /**
     * @var Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var routeCollection \Symfony\Component\Routing\RouteCollection
     */
    private $routeCollection;

    private $container;

    private $doctrine;

    private $authorizationChecker;

    private $tokenStorage;

    protected $security;

    protected $session;

    protected $translator;

    public function __construct(ContainerInterface $container, EntityManagerInterface $doctrine, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, Security $security, TranslatorInterface $translator, SessionInterface $session)
    {
        $this->router = $router;
        $this->routeCollection = $router->getRouteCollection();
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->security = $security;
        $this->session = $session;
        $this->translator = $translator;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $account = $token->getUser();
            if ($account && !is_string($account)) {
                if ($account->getUser()) {
                    $request = $event->getRequest();
                    $request->setLocale($account->getUser()->getLanguage());
                    $this->translator->setLocale($request->getLocale());

                    if (!$this->security->isGranted("ROLE_PREVIOUS_ADMIN")) {
                        $routesExceptions = ['default_logout', 'default_logout_abo', 'default_login', "default_homepage"];
                        $request = $event->getRequest();
                        $routeName = $request->get('_route');

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
                                    }
                                }
                            }
                        }

                        if ($noSubscription) {
                            if (!in_array($routeName, $routesExceptions)) {
                                $url = $this->router->generate("default_logout_abo");
                                $event->setResponse(new RedirectResponse($url));
                            }
                        }

                        $lastLoginLog = $account->getLoginLog();
                        if ($lastLoginLog) {
                            $now = new \DateTime("now");

                            if ($now->getTimestamp() - $lastLoginLog->getEndDate()->getTimestamp() <= 3600) {
                                $lastLoginLog->setEndDate($now);
                                $this->doctrine->flush();
                            } else {
                                $this->tokenStorage->setToken(null);
                                $this->session->invalidate();

                                $this->session->getFlashBag()->add("danger", "Votre session utilisateur a expirée. Veuillez vous reconnecter.");

                                $url = $this->router->generate("default_homepage");
                                $event->setResponse(new RedirectResponse($url));
                            }
                        }
                    }
                }
            }
        }
        /*$token = $this->tokenStorage->getToken();
        if ($token) {
            $account = $token->getUser();
            if ($account && !is_string($account)) {
                if ($account->getUser() && !$this->security->isGranted('ROLE_PREVIOUS_ADMIN')) {

                }
            }
        }*/
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 1)),
        );
    }
}