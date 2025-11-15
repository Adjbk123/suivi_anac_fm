<?php

namespace App\Entity;

use App\Repository\DepenseMissionParticipantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseMissionParticipantRepository::class)]
#[ORM\Table(name: 'depense_mission_participant')]
#[ORM\UniqueConstraint(name: 'uniq_depense_mission_user', columns: ['depense_mission_id', 'user_mission_id'])]
class DepenseMissionParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participantAllocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DepenseMission $depenseMission = null;

    #[ORM\ManyToOne(inversedBy: 'depenseAllocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserMission $userMission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantReel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepenseMission(): ?DepenseMission
    {
        return $this->depenseMission;
    }

    public function setDepenseMission(?DepenseMission $depenseMission): static
    {
        $this->depenseMission = $depenseMission;

        return $this;
    }

    public function getUserMission(): ?UserMission
    {
        return $this->userMission;
    }

    public function setUserMission(?UserMission $userMission): static
    {
        $this->userMission = $userMission;

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

