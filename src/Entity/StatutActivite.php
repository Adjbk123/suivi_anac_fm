<?php

namespace App\Entity;

use App\Repository\StatutActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatutActiviteRepository::class)]
class StatutActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $couleur = null;

    #[ORM\OneToMany(mappedBy: 'statutActivite', targetEntity: FormationSession::class)]
    private Collection $formationSessions;

    #[ORM\OneToMany(mappedBy: 'statutActivite', targetEntity: MissionSession::class)]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
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

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;
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
            $formationSession->setStatutActivite($this);
        }
        return $this;
    }

    public function removeFormationSession(FormationSession $formationSession): static
    {
        if ($this->formationSessions->removeElement($formationSession)) {
            if ($formationSession->getStatutActivite() === $this) {
                $formationSession->setStatutActivite(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissionSessions(): Collection
    {
        return $this->missionSessions;
    }

    public function addMissionSession(MissionSession $missionSession): static
    {
        if (!$this->missionSessions->contains($missionSession)) {
            $this->missionSessions->add($missionSession);
            $missionSession->setStatutActivite($this);
        }
        return $this;
    }

    public function removeMissionSession(MissionSession $missionSession): static
    {
        if ($this->missionSessions->removeElement($missionSession)) {
            if ($missionSession->getStatutActivite() === $this) {
                $missionSession->setStatutActivite(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }

    /**
     * Vérifie si ce statut d'activité est utilisé
     */
    public function isUsed(): bool
    {
        return !$this->formationSessions->isEmpty() || !$this->missionSessions->isEmpty();
    }
}
