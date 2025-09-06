<?php

namespace App\Entity;

use App\Repository\StatutParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatutParticipationRepository::class)]
class StatutParticipation
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

    #[ORM\OneToMany(mappedBy: 'statutParticipation', targetEntity: UserFormation::class)]
    private Collection $userFormations;

    #[ORM\OneToMany(mappedBy: 'statutParticipation', targetEntity: UserMission::class)]
    private Collection $userMissions;

    public function __construct()
    {
        $this->userFormations = new ArrayCollection();
        $this->userMissions = new ArrayCollection();
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
     * @return Collection<int, UserFormation>
     */
    public function getUserFormations(): Collection
    {
        return $this->userFormations;
    }

    public function addUserFormation(UserFormation $userFormation): static
    {
        if (!$this->userFormations->contains($userFormation)) {
            $this->userFormations->add($userFormation);
            $userFormation->setStatutParticipation($this);
        }
        return $this;
    }

    public function removeUserFormation(UserFormation $userFormation): static
    {
        if ($this->userFormations->removeElement($userFormation)) {
            if ($userFormation->getStatutParticipation() === $this) {
                $userFormation->setStatutParticipation(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, UserMission>
     */
    public function getUserMissions(): Collection
    {
        return $this->userMissions;
    }

    public function addUserMission(UserMission $userMission): static
    {
        if (!$this->userMissions->contains($userMission)) {
            $this->userMissions->add($userMission);
            $userMission->setStatutParticipation($this);
        }
        return $this;
    }

    public function removeUserMission(UserMission $userMission): static
    {
        if ($this->userMissions->removeElement($userMission)) {
            if ($userMission->getStatutParticipation() === $this) {
                $userMission->setStatutParticipation(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}
