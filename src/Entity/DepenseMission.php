<?php

namespace App\Entity;

use App\Repository\DepenseMissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseMissionRepository::class)]
class DepenseMission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'depenseMissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MissionSession $missionSession = null;

    #[ORM\ManyToOne(inversedBy: 'depenseMissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieDepense $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantReel = null;

    #[ORM\OneToMany(mappedBy: 'depenseMission', targetEntity: DepenseMissionParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participantAllocations;

    public function __construct()
    {
        $this->participantAllocations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategorie(): ?CategorieDepense
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieDepense $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getMontantPrevu(): ?string
    {
        return $this->montantPrevu;
    }

    public function setMontantPrevu(string $montantPrevu): static
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

    /**
     * @return Collection<int, DepenseMissionParticipant>
     */
    public function getParticipantAllocations(): Collection
    {
        return $this->participantAllocations;
    }

    public function addParticipantAllocation(DepenseMissionParticipant $allocation): static
    {
        if (!$this->participantAllocations->contains($allocation)) {
            $this->participantAllocations->add($allocation);
            $allocation->setDepenseMission($this);
        }

        return $this;
    }

    public function removeParticipantAllocation(DepenseMissionParticipant $allocation): static
    {
        if ($this->participantAllocations->removeElement($allocation)) {
            if ($allocation->getDepenseMission() === $this) {
                $allocation->setDepenseMission(null);
            }
        }

        return $this;
    }
}
