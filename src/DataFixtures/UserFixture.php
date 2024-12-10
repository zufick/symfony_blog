<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setUsername('user');
        $user->setFullname('Ulysses');
        $user->setVerified(true);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, '123123');
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}
