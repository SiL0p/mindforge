<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $loginType = $request->request->get('_login_type');

        if ($loginType === 'company' && !in_array('ROLE_COMPANY', $user->getRoles())) {
            // Log out the user - wrong portal
            $this->tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add('error', 'This account does not have company access.');
            return new RedirectResponse($this->router->generate('app_login_company'));
        }

        if ($loginType === 'student' && in_array('ROLE_COMPANY', $user->getRoles())) {
            // Log out the user - wrong portal
            $this->tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add('error', 'This is a company account. Please use the company login.');
            return new RedirectResponse($this->router->generate('app_login_student'));
        }

        return new RedirectResponse($this->router->generate('app_workspace'));
    }
}
