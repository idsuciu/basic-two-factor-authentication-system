<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Home route.
     *
     * @Route("/", name="app_homepage")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function home(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('app_account');
         }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('base.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }


    /**
     * Login route.
     *
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * Logout route.
     *
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('Logout action is done from the firewall.');
    }

    /**
     * Second step route (two factor authentication check).
     *
     * @Route("second-step", name="app_two_factor")
     * @param AuthenticationUtils $authenticationUtils
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return Response
     */
    public function secondStep(AuthenticationUtils $authenticationUtils, Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // If the login with credentials (first step from authentication process is successful
        // save the key "step" is saved in the session
        // If the first step is not completed redirect user to login page
        if (!$request->getSession()->has('step')) {
            return new RedirectResponse($urlGenerator->generate('app_login'));
        }

        // If the fist step is successful, remove from session the key "step"
        $request->getSession()->remove('step');

        return $this->render('security/second_step.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * Account route (requires fully authentication)).
     *
     * @Route("account", name="app_account")
     * @return Response
     */
    public function index()
    {
        return $this->render('account/account.html.twig', []);
    }

}
