<?php

namespace App\Entity;

use App\Repository\UserFormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFormationRepository::class)]
class UserFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userFormations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userFormations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FormationSession $formationSession = null;

    #[ORM\ManyToOne(inversedBy: 'userFormations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutParticipation $statutParticipation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarques = null;

    #[ORM\OneToMany(mappedBy: 'userFormation', targetEntity: DepenseFormationParticipant::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $depenseAllocations;

    public function __construct()
    {
        $this->depenseAllocations = new ArrayCollection();
    }

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

    public function getFormationSession(): ?FormationSession
    {
        return $this->formationSession;
    }

    public function setFormationSession(?FormationSession $formationSession): static
    {
        $this->formationSession = $formationSession;

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

    /**
     * @return Collection<int, DepenseFormationParticipant>
     */
    public function getDepenseAllocations(): Collection
    {
        return $this->depenseAllocations;
    }

    public function addDepenseAllocation(DepenseFormationParticipant $allocation): static
    {
        if (!$this->depenseAllocations->contains($allocation)) {
            $this->depenseAllocations->add($allocation);
            $allocation->setUserFormation($this);
        }

        return $this;
    }

    public function removeDepenseAllocation(DepenseFormationParticipant $allocation): static
    {
        if ($this->depenseAllocations->removeElement($allocation)) {
            if ($allocation->getUserFormation() === $this) {
                $allocation->setUserFormation(null);
            }
        }

        return $this;
    }
}
