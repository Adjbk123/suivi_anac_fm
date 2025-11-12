<?php

namespace App\Entity;

use App\Repository\TypeFondsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeFondsRepository::class)]
class TypeFonds
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'fonds', targetEntity: FormationSession::class)]
    private Collection $formationSessions;

    #[ORM\OneToMany(mappedBy: 'fonds', targetEntity: MissionSession::class)]
    private Collection $missionSessions;

    public function __construct()
    {
        $this->formationSessions = new ArrayCollection();
        $this->missionSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, FormationSession>
     */
    public function getFormationSessions(): Collection
    {
        return $this->formationSessions;
    }

    public function addFormationSession(FormationSession $formationSession): static
    {
        if (!$this->formationSessions->contains($formationSession)) {
            $this->formationSessions->add($formationSession);
            $formationSession->setFonds($this);
        }

        return $this;
    }

    public function removeFormationSession(FormationSession $formationSession): static
    {
        if ($this->formationSessions->removeElement($formationSession)) {
            // set the owning side to null (unless already changed)
            if ($formationSession->getFonds() === $this) {
                $formationSession->setFonds(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MissionSession>
     */
    public function getMissionSessions(): Collection
    {
        return $this->missionSessions;
    }

    public function addMissionSession(MissionSession $missionSession): static
    {
        if (!$this->missionSessions->contains($missionSession)) {
            $this->missionSessions->add($missionSession);
            $missionSession->setFonds($this);
        }

        return $this;
    }

    public function removeMissionSession(MissionSession $missionSession): static
    {
        if ($this->missionSessions->removeElement($missionSession)) {
            // set the owning side to null (unless already changed)
            if ($missionSession->getFonds() === $this) {
                $missionSession->setFonds(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si ce type de fonds est utilisé
     */
    public function isUsed(): bool
    {
        return !$this->formationSessions->isEmpty() || !$this->missionSessions->isEmpty();
    }
}
