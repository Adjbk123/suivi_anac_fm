<?php

namespace App\Entity;

use App\Repository\CategorieDepenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieDepenseRepository::class)]
class CategorieDepense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: DepenseFormation::class)]
    private Collection $depenseFormations;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: DepenseMission::class)]
    private Collection $depenseMissions;

    public function __construct()
    {
        $this->depenseFormations = new ArrayCollection();
        $this->depenseMissions = new ArrayCollection();
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
     * @return Collection<int, DepenseFormation>
     */
    public function getDepenseFormations(): Collection
    {
        return $this->depenseFormations;
    }

    public function addDepenseFormation(DepenseFormation $depenseFormation): static
    {
        if (!$this->depenseFormations->contains($depenseFormation)) {
            $this->depenseFormations->add($depenseFormation);
            $depenseFormation->setCategorie($this);
        }

        return $this;
    }

    public function removeDepenseFormation(DepenseFormation $depenseFormation): static
    {
        if ($this->depenseFormations->removeElement($depenseFormation)) {
            // set the owning side to null (unless already changed)
            if ($depenseFormation->getCategorie() === $this) {
                $depenseFormation->setCategorie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DepenseMission>
     */
    public function getDepenseMissions(): Collection
    {
        return $this->depenseMissions;
    }

    public function addDepenseMission(DepenseMission $depenseMission): static
    {
        if (!$this->depenseMissions->contains($depenseMission)) {
            $this->depenseMissions->add($depenseMission);
            $depenseMission->setCategorie($this);
        }

        return $this;
    }

    public function removeDepenseMission(DepenseMission $depenseMission): static
    {
        if ($this->depenseMissions->removeElement($depenseMission)) {
            // set the owning side to null (unless already changed)
            if ($depenseMission->getCategorie() === $this) {
                $depenseMission->setCategorie(null);
            }
        }

        return $this;
    }
}
