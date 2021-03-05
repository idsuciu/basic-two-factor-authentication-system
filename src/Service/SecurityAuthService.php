<?php

namespace App\Service;

use App\Entity\AuthenticationCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SecurityAuthService
{
    /** @var int Code length */
    const DIGITS = 5;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(EntityManagerInterface $entityManager, Mailer $mailer)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    /**
     * Generate authentication code and send it via email.
     *
     * @param $user User Object
     *
     * @throws TransportExceptionInterface
     */
    public function generateAndSend($user)
    {
        // Generate authentication code
        $code = $this->generateCode();

        // Save the authentication code with linked user
        $authenticationCodeEntity = new AuthenticationCode();
        $authenticationCodeEntity->setCode($code);
        $authenticationCodeEntity->setUser($user);
        $authenticationCodeEntity->setCreatedAt(new \DateTime('now'));
        $this->entityManager->persist($authenticationCodeEntity);
        $this->entityManager->flush();

        // Send code thought email
        $this->mailer->sendCode($user, $code);

    }

    /**
     * Handle user restriction.
     *
     * @param int $id   User ID.
     *
     * @return array|bool
     */
    public function handleUserRestriction(int $id)
    {
        $entity = $this->getUserEntity($id);

        // If the user is blocked and the elapsed time is less than 5 minutes, return restriction details
        if (null !== $entity->getBlockedAt() && $this->checkDateValidity($entity->getBlockedAt()->format('U'), 300) && $entity->getBlocked()) {
            $timeElapsed = time() - $entity->getBlockedAt()->format('U');
            $diffTime = (int)(ceil((300 - $timeElapsed) / 60));

            return [
                'blocked'           => true,
                'remaining_time'    => $diffTime,
            ];
        }

        return false;
    }

    /**
     * Reset user restriction after each step.
     *
     * @param int $id               User ID
     * @param bool $cleanOldCodes   If true, clean up the old auth codes generated for that user
     */
    public function resetUserRestriction(int $id, $cleanOldCodes = false)
    {
        $entity = $this->getUserEntity($id);

        if ($cleanOldCodes) {
            $this->cleanUpOldCode($id);
        }

        $entity->setLoginAttempt(0);
        $entity->setBlocked(0);
        $entity->setBlockedAt(null);

        $this->entityManager->flush();
    }

    /**
     * Set login attempt for failed login.
     *
     * @param $id
     *
     * @throws Exception
     */
    public function setLoginAttempt($id): void
    {
        $entity = $this->getUserEntity($id);
        $loginAttempt = $entity->getLoginAttempt() + 1;

        // If fail login attempt is bigger than 3, block the user
        if ($loginAttempt >= 3) {
            $entity->setLoginAttempt(0);
            $entity->setBlocked(1);
            $entity->setBlockedAt(new \DateTime('now'));
        } else {
            $entity->setLoginAttempt($loginAttempt);
        }

        $this->entityManager->flush();
    }

    /**
     * Check date validity.
     *
     * @param $createdAt
     * @param int $data
     *
     * @return bool
     */
    public function checkDateValidity($createdAt, $data = 120)
    {
        return $createdAt + $data > time();
    }

    /**
     * Generate authentication code.
     *
     * @return int
     */
    private function generateCode()
    {
        $min =  10 ** (self::DIGITS -1);
        $max =  10 ** self::DIGITS -1;

        try {
            return random_int($min, $max);
        } catch (Exception $e) {
            $this->generateCode();
        }
    }

    /**
     * Get user entity based on user id.
     *
     * @param $id   User ID
     *
     * @return object|null
     */
    private function getUserEntity($id)
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    /**
     * Clean up old codes.
     *
     * @param $id User ID
     */
    private function cleanUpOldCode($id)
    {
        $authenticationCodeEntity = $this->entityManager->getRepository(AuthenticationCode::class)->findBy([
            'user'  => $id
        ]);

        if ($authenticationCodeEntity) {
            foreach ($authenticationCodeEntity as $code) {
                $this->entityManager->remove($code);
            }
            $this->entityManager->flush();
        }
    }
}
