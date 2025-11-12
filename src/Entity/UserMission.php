<?php

namespace App\Entity;

use App\Repository\UserMissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserMissionRepository::class)]
class UserMission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userMissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userMissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MissionSession $missionSession = null;

    #[ORM\ManyToOne(inversedBy: 'userMissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutParticipation $statutParticipation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMissionSession(): ?MissionSession
    {
        return $this->missionSession;
    }

    public function setMissionSession(?MissionSession $missionSession): static
    {
        $this->missionSession = $missionSession;

        return $this;
    }

    public function getMission(): ?Mission
    {
        return $this->missionSession?->getMission();
    }

    public function setMission(?Mission $mission): static
    {
        $this->missionSession = $mission ? $mission->getOrCreateSession() : null;

        return $this;
    }

    public function getStatutParticipation(): ?StatutParticipation
    {
        return $this->statutParticipation;
    }

    public function setStatutParticipation(?StatutParticipation $statutParticipation): static
    {
        $this->statutParticipation = $statutParticipation;

        return $this;
    }

    public function getStatutExecution(): ?string
    {
        return $this->statutParticipation?->getCode();
    }

    public function getRemarques(): ?string
    {
        return $this->remarques;
    }

    public function setRemarques(?string $remarques): static
    {
        $this->remarques = $remarques;

        return $this;
    }
}
