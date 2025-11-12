<?php

namespace App\Entity;

use App\Repository\MissionSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionSessionRepository::class)]
class MissionSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mission $mission = null;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Direction $direction = null;

    #[ORM\ManyToOne(inversedBy: 'missionSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeFonds $fonds = null;

    #[ORM\Column(length: 255)]
    private ?string $lieuPrevu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuReel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePrevueFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateReelleFin = null;

    #[ORM\ManyToOne(inversedBy: 'missionSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StatutActivite $statutActivite = null;

    #[ORM\Column]
    private ?int $dureePrevue = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeReelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $budgetPrevu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetReel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'missionSession', targetEntity: DepenseMission::class, orphanRemoval: true)]
    private Collection $depenseMissions;

    #[ORM\OneToMany(mappedBy: 'missionSession', targetEntity: UserMission::class, orphanRemoval: true)]
    private Collection $userMissions;

    #[ORM\OneToMany(mappedBy: 'missionSession', targetEntity: DocumentMission::class, orphanRemoval: true)]
    private Collection $documentMissions;

    public function __construct()
    {
        $this->depenseMissions = new ArrayCollection();
        $this->userMissions = new ArrayCollection();
        $this->documentMissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): static
    {
        $this->mission = $mission;

        return $this;
    }

    public function getDirection(): ?Direction
    {
        return $this->direction;
    }

    public function setDirection(?Direction $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getFonds(): ?TypeFonds
    {
        return $this->fonds;
    }

    public function setFonds(?TypeFonds $fonds): static
    {
        $this->fonds = $fonds;

        return $this;
    }

    public function getLieuPrevu(): ?string
    {
        return $this->lieuPrevu;
    }

    public function setLieuPrevu(string $lieuPrevu): static
    {
        $this->lieuPrevu = $lieuPrevu;

        return $this;
    }

    public function getLieuReel(): ?string
    {
        return $this->lieuReel;
    }

    public function setLieuReel(?string $lieuReel): static
    {
        $this->lieuReel = $lieuReel;

        return $this;
    }

    public function getDatePrevueDebut(): ?\DateTimeInterface
    {
        return $this->datePrevueDebut;
    }

    public function setDatePrevueDebut(\DateTimeInterface $datePrevueDebut): static
    {
        $this->datePrevueDebut = $datePrevueDebut;

        return $this;
    }

    public function getDatePrevueFin(): ?\DateTimeInterface
    {
        return $this->datePrevueFin;
    }

    public function setDatePrevueFin(\DateTimeInterface $datePrevueFin): static
    {
        $this->datePrevueFin = $datePrevueFin;

        return $this;
    }

    public function getDateReelleDebut(): ?\DateTimeInterface
    {
        return $this->dateReelleDebut;
    }

    public function setDateReelleDebut(?\DateTimeInterface $dateReelleDebut): static
    {
        $this->dateReelleDebut = $dateReelleDebut;

        return $this;
    }

    public function getDateReelleFin(): ?\DateTimeInterface
    {
        return $this->dateReelleFin;
    }

    public function setDateReelleFin(?\DateTimeInterface $dateReelleFin): static
    {
        $this->dateReelleFin = $dateReelleFin;

        return $this;
    }

    public function getStatutActivite(): ?StatutActivite
    {
        return $this->statutActivite;
    }

    public function setStatutActivite(?StatutActivite $statutActivite): static
    {
        $this->statutActivite = $statutActivite;

        return $this;
    }

    public function getDureePrevue(): ?int
    {
        return $this->dureePrevue;
    }

    public function setDureePrevue(int $dureePrevue): static
    {
        $this->dureePrevue = $dureePrevue;

        return $this;
    }

    public function getDureeReelle(): ?int
    {
        return $this->dureeReelle;
    }

    public function setDureeReelle(?int $dureeReelle): static
    {
        $this->dureeReelle = $dureeReelle;

        return $this;
    }

    public function getBudgetPrevu(): ?string
    {
        return $this->budgetPrevu;
    }

    public function setBudgetPrevu(string $budgetPrevu): static
    {
        $this->budgetPrevu = $budgetPrevu;

        return $this;
    }

    public function getBudgetReel(): ?string
    {
        return $this->budgetReel;
    }

    public function setBudgetReel(?string $budgetReel): static
    {
        $this->budgetReel = $budgetReel;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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
            $depenseMission->setMissionSession($this);
        }

        return $this;
    }

    public function removeDepenseMission(DepenseMission $depenseMission): static
    {
        if ($this->depenseMissions->removeElement($depenseMission)) {
            if ($depenseMission->getMissionSession() === $this) {
                $depenseMission->setMissionSession(null);
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
            $userMission->setMissionSession($this);
        }

        return $this;
    }

    public function removeUserMission(UserMission $userMission): static
    {
        if ($this->userMissions->removeElement($userMission)) {
            if ($userMission->getMissionSession() === $this) {
                $userMission->setMissionSession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentMission>
     */
    public function getDocumentMissions(): Collection
    {
        return $this->documentMissions;
    }

    public function addDocumentMission(DocumentMission $documentMission): static
    {
        if (!$this->documentMissions->contains($documentMission)) {
            $this->documentMissions->add($documentMission);
            $documentMission->setMissionSession($this);
        }

        return $this;
    }

    public function removeDocumentMission(DocumentMission $documentMission): static
    {
        if ($this->documentMissions->removeElement($documentMission)) {
            if ($documentMission->getMissionSession() === $this) {
                $documentMission->setMissionSession(null);
            }
        }

        return $this;
    }
}

