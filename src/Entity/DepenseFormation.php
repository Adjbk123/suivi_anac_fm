<?php

namespace App\Entity;

use App\Repository\DepenseFormationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseFormationRepository::class)]
class DepenseFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'depenseFormations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FormationSession $formationSession = null;

    #[ORM\ManyToOne(inversedBy: 'depenseFormations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieDepense $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantReel = null;

    public function getId(): ?int
    {
        return $this->id;
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
}
