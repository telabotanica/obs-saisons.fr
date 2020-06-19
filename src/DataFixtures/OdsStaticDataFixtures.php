<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;

class OdsStaticDataFixtures extends Fixture
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function load(ObjectManager $manager)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => 'ods:bootstrap:all-static-data']);
        try {
            $application->run($input);
        } catch (\Exception $e) {
            return 1;
        }

        return 0;
    }
}
