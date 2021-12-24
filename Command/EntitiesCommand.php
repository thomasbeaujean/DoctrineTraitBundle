<?php

namespace A5sys\DoctrineTraitBundle\Command;

use A5sys\DoctrineTraitBundle\Generator\EntityGenerator;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class EntitiesCommand extends DoctrineCommand
{
    protected static $defaultName = 'generate:doctrine:traits';

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine);
    }

    protected function configure()
    {
        $this
            ->setAliases(array('generate:doctrine:traits'))
            ->addArgument('name', InputArgument::REQUIRED, 'A bundle name, a namespace, or a class name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata();

        $generator = new EntityGenerator();
        $path = 'src';

        foreach ($metadata as $m) {
            if (substr($m->name, 0, 4) === 'App\\') {
                $output->writeln(sprintf('  > generating <comment>%s</comment>', $m->name));
                $generator->generate(array($m), $path);
            }
        }

        return Command::SUCCESS;
    }
}
