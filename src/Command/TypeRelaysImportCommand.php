<?php

namespace App\Command;

use App\Entity\TypeRelays;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Helper\ImportCommandTrait;
use Doctrine\ORM\EntityManagerInterface;

class TypeRelaysImportCommand extends Command
{
    use ImportCommandTrait;

    protected static $defaultName = 'ods:import:typesRelays';

    public $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import types relais')
            ->setHelp('Import types relais')
        ;
        $this->addArgument(
            'importEventtypesRelaysAndPeriods',
            InputArgument::OPTIONAL,
            'import EventtypesRelays and fill periods [y/N]'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $this->em->getClassMetadata(TypeRelays::class);
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeQuery('DELETE FROM '.$cmd->getTableName());
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();

            $typesRelays = json_decode(file_get_contents('src/Ressources/ods_types_relays.json'));

            foreach ($typesRelays as $singletypesRelays) {

                $typesRelays = new typeRelays();
                $typesRelays->setName($singletypesRelays->name);
                $typesRelays->setCode($singletypesRelays->code);

                $this->em->persist($typesRelays);
                
            }

            $this->em->flush();

            return 0;
        } catch (\Exception $e) {
            $connection->rollback();
        }
        
    }

}
