<?php

namespace App\Entity;

use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
class Mission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'mission', targetEntity: MissionSession::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sessions;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, MissionSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(MissionSession $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setMission($this);
        }

        return $this;
    }

    public function removeSession(MissionSession $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getMission() === $this) {
                $session->setMission(null);
            }
        }

        return $this;
    }

    public function getPrimarySession(): ?MissionSession
    {
        if ($this->sessions->isEmpty()) {
            return null;
        }

        return $this->sessions->first() ?: null;
    }

    public function getOrCreateSession(): MissionSession
    {
        $session = $this->getPrimarySession();
        if (!$session) {
            $session = new MissionSession();
            $session->setMission($this);
            $this->sessions->add($session);
        }

        return $session;
    }

    public function getDirection(): ?Direction
    {
        return $this->getPrimarySession()?->getDirection();
    }

    public function setDirection(?Direction $direction): static
    {
        $this->getOrCreateSession()->setDirection($direction);

        return $this;
    }

    public function getFonds(): ?TypeFonds
    {
        return $this->getPrimarySession()?->getFonds();
    }

    public function setFonds(?TypeFonds $fonds): static
    {
        $this->getOrCreateSession()->setFonds($fonds);

        return $this;
    }

    public function getLieuPrevu(): ?string
    {
        return $this->getPrimarySession()?->getLieuPrevu();
    }

    public function setLieuPrevu(string $lieuPrevu): static
    {
        $this->getOrCreateSession()->setLieuPrevu($lieuPrevu);

        return $this;
    }

    public function getLieuReel(): ?string
    {
        return $this->getPrimarySession()?->getLieuReel();
    }

    public function setLieuReel(?string $lieuReel): static
    {
        $this->getOrCreateSession()->setLieuReel($lieuReel);

        return $this;
    }

    public function getDatePrevueDebut(): ?\DateTimeInterface
    {
        return $this->getPrimarySession()?->getDatePrevueDebut();
    }

    public function setDatePrevueDebut(\DateTimeInterface $datePrevueDebut): static
    {
        $this->getOrCreateSession()->setDatePrevueDebut($datePrevueDebut);

        return $this;
    }

    public function getDatePrevueFin(): ?\DateTimeInterface
    {
        return $this->getPrimarySession()?->getDatePrevueFin();
    }

    public function setDatePrevueFin(\DateTimeInterface $datePrevueFin): static
    {
        $this->getOrCreateSession()->setDatePrevueFin($datePrevueFin);

        return $this;
    }

    public function getDateReelleDebut(): ?\DateTimeInterface
    {
        return $this->getPrimarySession()?->getDateReelleDebut();
    }

    public function setDateReelleDebut(?\DateTimeInterface $dateReelleDebut): static
    {
        $this->getOrCreateSession()->setDateReelleDebut($dateReelleDebut);

        return $this;
    }

    public function getDateReelleFin(): ?\DateTimeInterface
    {
        return $this->getPrimarySession()?->getDateReelleFin();
    }

    public function setDateReelleFin(?\DateTimeInterface $dateReelleFin): static
    {
        $this->getOrCreateSession()->setDateReelleFin($dateReelleFin);

        return $this;
    }

    public function getStatutActivite(): ?StatutActivite
    {
        return $this->getPrimarySession()?->getStatutActivite();
    }

    public function setStatutActivite(?StatutActivite $statutActivite): static
    {
        $this->getOrCreateSession()->setStatutActivite($statutActivite);

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->getStatutActivite()?->getCode();
    }

    public function getDureePrevue(): ?int
    {
        return $this->getPrimarySession()?->getDureePrevue();
    }

    public function setDureePrevue(int $dureePrevue): static
    {
        $this->getOrCreateSession()->setDureePrevue($dureePrevue);

        return $this;
    }

    public function getDureeReelle(): ?int
    {
        return $this->getPrimarySession()?->getDureeReelle();
    }

    public function setDureeReelle(?int $dureeReelle): static
    {
        $this->getOrCreateSession()->setDureeReelle($dureeReelle);

        return $this;
    }

    public function getBudgetPrevu(): ?float
    {
        $budget = $this->getPrimarySession()?->getBudgetPrevu();
        return $budget !== null ? (float) $budget : null;
    }

    public function setBudgetPrevu(float $budgetPrevu): static
    {
        $this->getOrCreateSession()->setBudgetPrevu((string) $budgetPrevu);

        return $this;
    }

    public function getBudgetReel(): ?float
    {
        $budget = $this->getPrimarySession()?->getBudgetReel();
        return $budget !== null ? (float) $budget : null;
    }

    public function setBudgetReel(?float $budgetReel): static
    {
        $this->getOrCreateSession()->setBudgetReel($budgetReel !== null ? (string) $budgetReel : null);

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->getPrimarySession()?->getNotes();
    }

    public function setNotes(?string $notes): static
    {
        $this->getOrCreateSession()->setNotes($notes);

        return $this;
    }

    /**
     * @return Collection<int, DepenseMission>
     */
    public function getDepenseMissions(): Collection
    {
        return $this->getOrCreateSession()->getDepenseMissions();
    }

    public function addDepenseMission(DepenseMission $depenseMission): static
    {
        $this->getOrCreateSession()->addDepenseMission($depenseMission);

        return $this;
    }

    public function removeDepenseMission(DepenseMission $depenseMission): static
    {
        $this->getOrCreateSession()->removeDepenseMission($depenseMission);

        return $this;
    }

    /**
     * @return Collection<int, UserMission>
     */
    public function getUserMissions(): Collection
    {
        return $this->getOrCreateSession()->getUserMissions();
    }

    public function addUserMission(UserMission $userMission): static
    {
        $this->getOrCreateSession()->addUserMission($userMission);

        return $this;
    }

    public function removeUserMission(UserMission $userMission): static
    {
        $this->getOrCreateSession()->removeUserMission($userMission);

        return $this;
    }

    /**
     * @return Collection<int, DocumentMission>
     */
    public function getDocumentMissions(): Collection
    {
        return $this->getOrCreateSession()->getDocumentMissions();
    }

    public function addDocumentMission(DocumentMission $documentMission): static
    {
        $this->getOrCreateSession()->addDocumentMission($documentMission);

        return $this;
    }

    public function removeDocumentMission(DocumentMission $documentMission): static
    {
        $this->getOrCreateSession()->removeDocumentMission($documentMission);

        return $this;
    }
}
