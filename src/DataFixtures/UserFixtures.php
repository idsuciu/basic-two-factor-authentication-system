<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
    }

    /**
     * Insert dummy users to User entity
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <=  5; $i++) {
            $user = new User();
            $user->setEmail('admin'.$i.'@company.com');
            $user->setPassword($this->passwordEncoder->encodePassword(
                $user,
                'admin'.$i
            ));
            $user->setLoginAttempt(0);
            $user->setBlocked(0);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }
}
