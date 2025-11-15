<?php

namespace App\Entity;

use App\Repository\DepenseFormationParticipantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseFormationParticipantRepository::class)]
#[ORM\Table(name: 'depense_formation_participant')]
#[ORM\UniqueConstraint(name: 'uniq_depense_formation_user', columns: ['depense_formation_id', 'user_formation_id'])]
class DepenseFormationParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participantAllocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DepenseFormation $depenseFormation = null;

    #[ORM\ManyToOne(inversedBy: 'depenseAllocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserFormation $userFormation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantReel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepenseFormation(): ?DepenseFormation
    {
        return $this->depenseFormation;
    }

    public function setDepenseFormation(?DepenseFormation $depenseFormation): static
    {
        $this->depenseFormation = $depenseFormation;

        return $this;
    }

    public function getUserFormation(): ?UserFormation
    {
        return $this->userFormation;
    }

    public function setUserFormation(?UserFormation $userFormation): static
    {
        $this->userFormation = $userFormation;

        return $this;
    }

    public function getMontantPrevu(): ?string
    {
        return $this->montantPrevu;
    }

    public function setMontantPrevu(?string $montantPrevu): static
    {
        $this->montantPrevu = $montantPrevu;

        return $this;
    }

    public function getMontantReel(): ?string
    {
        return $this->montantReel;
    }

    public function setMontantReel(?string $montantReel): static
    {
        $this->montantReel = $montantReel;

        return $this;
    }
}

