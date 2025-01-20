<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 28/03/2019
 * Time: 17:23
 */

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserListener
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

    public function __construct(ContainerInterface $container, EntityManagerInterface $doctrine, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, Security $security)
    {
        $this->router = $router;
        $this->routeCollection = $router->getRouteCollection();
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->security = $security;
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $account = $token->getUser();
            if ($account && !is_string($account)) {
                if (!$this->security->isGranted("ROLE_PREVIOUS_ADMIN")) {
                    if (!$this->security->isGranted("ROLE_DPO")) {
                        if ($this->security->isGranted("ROLE_JURISTE") || $this->security->isGranted("ROLE_COMMERCE")) {
                            if ($this->security->isGranted("ROLE_JURISTE")) {
                                if ($event->getTargetUser()->getUser()->getManager()->getId() != $account->getManager()->getId()
                                && ($event->getTargetUser()->getUser()->getLawyer() && $event->getTargetUser()->getUser()->getLawyer()->getId() != $account->getManager()->getId())) {
                                    $event->stopPropagation();
                                    throw new AccessDeniedHttpException();
                                }
                            } else {
                                if (!$event->getTargetUser()->getUser()->isDemo()) {
                                    $event->stopPropagation();
                                    throw new AccessDeniedHttpException();
                                }
                            }
                        } else {
                            if (!$event->getTargetUser()->getUser() || !$event->getTargetUser()->getUser()->getParentUser() || $event->getTargetUser()->getUser()->getParentUser()->getId() != $account->getUser()->getId()) {
                                $event->stopPropagation();
                                throw new AccessDeniedHttpException();
                            }
                        }
                    }
                }
            } else {
                $event->stopPropagation();
                throw new AccessDeniedHttpException();
            }
        } else {
            $event->stopPropagation();
            throw new AccessDeniedHttpException();
        }
    }
}