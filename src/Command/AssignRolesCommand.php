<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assign-roles',
    description: 'Attribue des rôles aux utilisateurs existants',
)]
class AssignRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email de l\'utilisateur')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Rôle à attribuer (ROLE_EDITEUR, ROLE_DIRECTEUR, ROLE_ADMIN)')
            ->addOption('all-users', null, InputOption::VALUE_NONE, 'Attribuer le rôle à tous les utilisateurs')
            ->addOption('list-roles', null, InputOption::VALUE_NONE, 'Lister tous les rôles disponibles')
            ->setHelp('Cette commande permet d\'attribuer des rôles aux utilisateurs existants.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Lister les rôles disponibles
        if ($input->getOption('list-roles')) {
            $this->listRoles($io);
            return Command::SUCCESS;
        }

        $role = $input->getOption('role');
        $email = $input->getOption('email');
        $allUsers = $input->getOption('all-users');

        if (!$role && !$allUsers) {
            $io->error('Vous devez spécifier un rôle avec --role ou utiliser --all-users');
            return Command::FAILURE;
        }

        if ($role && !$this->isValidRole($role)) {
            $io->error('Rôle invalide. Rôles disponibles : ROLE_EDITEUR, ROLE_DIRECTEUR, ROLE_ADMIN');
            return Command::FAILURE;
        }

        if ($allUsers) {
            $this->assignRoleToAllUsers($role, $io);
        } elseif ($email) {
            $this->assignRoleToUser($email, $role, $io);
        } else {
            $io->error('Vous devez spécifier un email avec --email ou utiliser --all-users');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function listRoles(SymfonyStyle $io): void
    {
        $io->title('Rôles disponibles');
        $io->table(
            ['Rôle', 'Description'],
            [
                ['ROLE_USER', 'Utilisateur de base (attribué automatiquement)'],
                ['ROLE_EDITEUR', 'Peut créer, modifier et valider des formations/missions'],
                ['ROLE_DIRECTEUR', 'Accès aux statistiques, rapports et filtres avancés'],
                ['ROLE_ADMIN', 'Accès complet à toutes les fonctionnalités'],
            ]
        );
    }

    private function isValidRole(string $role): bool
    {
        $validRoles = ['ROLE_EDITEUR', 'ROLE_DIRECTEUR', 'ROLE_ADMIN'];
        return in_array($role, $validRoles);
    }

    private function assignRoleToUser(string $email, string $role, SymfonyStyle $io): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error("Utilisateur avec l'email '$email' non trouvé");
            return;
        }

        $currentRoles = $user->getRoles();
        
        // Ajouter le nouveau rôle s'il n'existe pas déjà
        if (!in_array($role, $currentRoles)) {
            $currentRoles[] = $role;
            $user->setRoles($currentRoles);
            
            $this->entityManager->flush();
            
            $io->success("Rôle '$role' attribué avec succès à l'utilisateur '$email'");
        } else {
            $io->info("L'utilisateur '$email' a déjà le rôle '$role'");
        }

        $this->displayUserRoles($user, $io);
    }

    private function assignRoleToAllUsers(string $role, SymfonyStyle $io): void
    {
        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        foreach ($users as $user) {
            $currentRoles = $user->getRoles();
            
            if (!in_array($role, $currentRoles)) {
                $currentRoles[] = $role;
                $user->setRoles($currentRoles);
                $updatedCount++;
            }
        }

        $this->entityManager->flush();

        $io->success("Rôle '$role' attribué à $updatedCount utilisateur(s) sur " . count($users));
    }

    private function displayUserRoles(User $user, SymfonyStyle $io): void
    {
        $io->table(
            ['Utilisateur', 'Rôles actuels'],
            [
                [
                    $user->getNom() . ' ' . $user->getPrenom() . ' (' . $user->getEmail() . ')',
                    implode(', ', $user->getRoles())
                ]
            ]
        );
    }
}
