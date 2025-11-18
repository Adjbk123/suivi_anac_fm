<?php

namespace App\Command;

use App\Service\StatutInitializerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-statuts',
    description: 'Initialise automatiquement les statuts d\'activité et de participation s\'ils n\'existent pas',
)]
class InitializeStatutsCommand extends Command
{
    public function __construct(
        private StatutInitializerService $statutInitializerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des statuts');

        try {
            $io->info('Vérification et création des statuts...');
            
            $this->statutInitializerService->initializeStatuts();
            
            $io->success('Les statuts ont été initialisés avec succès !');
            $io->note('Les statuts suivants ont été vérifiés/créés :');
            $io->listing([
                'Statuts d\'activité : prevue_non_executee, prevue_executee, non_prevue_executee, annulee',
                'Statuts de participation : inscrit, participe, absent, non_prevus_participe'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'initialisation des statuts : ' . $e->getMessage());
            
            if ($output->isVerbose()) {
                $io->error($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
}

