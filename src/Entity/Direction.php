<?php

namespace App\Entity;

use App\Repository\DirectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DirectionRepository::class)]
class Direction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'direction', targetEntity: Service::class)]
    private Collection $services;

    #[ORM\OneToMany(mappedBy: 'direction', targetEntity: MissionSession::class)]
    private Collection $missions;

    #[ORM\OneToMany(mappedBy: 'direction', targetEntity: FormationSession::class)]
    private Collection $formationSessions;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->missions = new ArrayCollection();
        $this->formationSessions = new ArrayCollection();
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
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setDirection($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getDirection() === $this) {
                $service->setDirection(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MissionSession>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(MissionSession $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setDirection($this);
        }

        return $this;
    }

    public function removeMission(MissionSession $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getDirection() === $this) {
                $mission->setDirection(null);
            }
        }

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
            $formationSession->setDirection($this);
        }

        return $this;
    }

    public function removeFormationSession(FormationSession $formationSession): static
    {
        if ($this->formationSessions->removeElement($formationSession)) {
            // set the owning side to null (unless already changed)
            if ($formationSession->getDirection() === $this) {
                $formationSession->setDirection(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si cette direction est utilisée
     */
    public function isUsed(): bool
    {
        return !$this->services->isEmpty() || !$this->missions->isEmpty() || !$this->formationSessions->isEmpty();
    }
}
