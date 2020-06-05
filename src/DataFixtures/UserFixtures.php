<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        $admin = new User();
        $admin->setEmail('admin@example.org');
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->passwordEncoder->encodePassword($admin, 'admin'));
        $admin->setStatus(1);
        $admin->setName('Admin');
        $admin->setDisplayName('Johnny Admin');
        $admin->setCreatedAt(new \DateTime());

        $manager->persist($admin);

        for ($i = 0; $i < 10; ++$i) {
            $user = new User();
            $user->setEmail($faker->safeEmail);
            $user->setRoles([User::ROLE_USER]);
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'user'));
            $user->setStatus($faker->biasedNumberBetween(0, 1));
            $user->setName($faker->name);
            $user->setDisplayName($user->getName());
            $user->setCreatedAt(new \DateTime());

            $manager->persist($user);

            $this->addReference(sprintf('user-%d', $i), $user);
        }

        $manager->flush();
    }
}
