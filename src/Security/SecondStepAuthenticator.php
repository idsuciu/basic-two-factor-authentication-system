<?php

namespace App\Security;

use App\Entity\AuthenticationCode;
use App\Exception\AuthenticatorException;
use App\Service\SecurityAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecondStepAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    /** @var string The route for second step */
    public const TWO_FACTOR_ROUTE = 'app_two_factor';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var SecurityAuthService
     */
    private $securityAuthService;

    /**
     * @var AuthenticatorException
     */
    private $authenticatorException;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, SecurityAuthService $securityAuthService, AuthenticatorException $authenticatorException)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->securityAuthService = $securityAuthService;
        $this->authenticatorException = $authenticatorException;
    }

    /**
     * Check the route and if the session has the "user" key.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return self::TWO_FACTOR_ROUTE === $request->attributes->get('_route')
            && $request->getSession()->has('user') && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $code = $request->request->get('code');
        $userId = $request->getSession()->get('user')->getId();

        if (strlen($code)) {
            $request->getSession()->set(
                'code',
                $request->request->get('code')
            );

            // Handle user restriction
            $userRestriction = $this->securityAuthService->handleUserRestriction($userId);

            // If the maximum number of login attempts has been reached for an user, throw an exception
            if ($userRestriction) {
                throw new CustomUserMessageAuthenticationException('The maximum number of login attempts has been reached. Please try again in '.$userRestriction['remaining_time'].' minutes!');
            }

            return [
                'code'          => $code,
                'user_id'       => $userId
            ];
        }

        throw new AuthenticatorException(null, 'The code is empty!');
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $authenticationCode = $this->entityManager->getRepository(AuthenticationCode::class)->findOneBy([
            'code'      => $credentials['code'],
            'user'      => $credentials['user_id']
        ]);


        // Verify code validity
        if ($authenticationCode) {
            $user = $authenticationCode->getUser();
            $codeValidity = $this->securityAuthService->checkDateValidity($authenticationCode->getCreatedAt()->format('U'));

            if (!$codeValidity) {
                $this->securityAuthService->setLoginAttempt($user->getId());
                throw new AuthenticatorException(null, 'The code is expired!');
            }

            return $user;
        }

        $this->securityAuthService->setLoginAttempt($credentials['user_id']);
        throw new AuthenticatorException(null, 'The code is not valid!');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param $credentials
     *
     * @return string|null
     */
    public function getPassword($credentials): ?string
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
            'error' => true,
            'code'  => $exception->getCode(),
            'message'   => $exception->getMessage()
        ]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        $user = $request->getSession()->get('user');
        $this->securityAuthService->resetUserRestriction($user->getId());

        $request->getSession()->remove('user');

        return new JsonResponse([
            'error' => false,
            'url'  => $this->urlGenerator->generate('app_account')
        ]);

    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::TWO_FACTOR_ROUTE);
    }
}
