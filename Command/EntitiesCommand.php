<?php

namespace A5sys\DoctrineTraitBundle\Command;

use A5sys\DoctrineTraitBundle\Doctrine\DisconnectedMetadataFactory;
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
            ->addArgument('name', InputArgument::REQUIRED, 'A bundle name, a namespace, or a class name')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path where to generate entities when it cannot be guessed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new DisconnectedMetadataFactory($this->getDoctrine());

        $name = strtr($input->getArgument('name'), '/', '\\');
        if (false !== $pos = strpos($name, ':')) {
            $name = $this->getDoctrine()->getAliasNamespace(substr($name, 0, $pos)).'\\'.substr($name, $pos + 1);
        }
        $output->writeln(sprintf('Generating entities for namespace "<info>%s</info>"', $name));
        $metadata = $manager->getNamespaceMetadata($name, $input->getOption('path'));

        $generator = new EntityGenerator();

        foreach ($metadata->getMetadata() as $m) {
            $output->writeln(sprintf('  > generating <comment>%s</comment>', $m->name));
            $generator->generate(array($m), $input->getOption('path'));
        }

        return Command::SUCCESS;
    }
}
