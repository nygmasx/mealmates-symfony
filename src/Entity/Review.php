<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['review:read', 'review:summary'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'receivedReviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?User $reviewedUser = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read'])]
    private ?Booking $booking = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['buyer_to_seller', 'seller_to_buyer'])]
    #[Groups(['review:read', 'review:summary'])]
    private ?string $reviewType = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read', 'review:summary'])]
    private ?int $overallRating = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read'])]
    private ?int $productQualityRating = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read'])]
    private ?int $punctualityRating = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read'])]
    private ?int $friendlinessRating = null;

    // Critères pour vendeur -> acheteur
    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read'])]
    private ?int $communicationRating = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read'])]
    private ?int $reliabilityRating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['review:read'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['review:read', 'review:summary'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['review:read'])]
    private ?bool $isVisible = true;

    #[ORM\Column(nullable: true)]
    #[Groups(['review:read'])]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $moderationReason = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getReviewedUser(): ?User
    {
        return $this->reviewedUser;
    }

    public function setReviewedUser(?User $reviewedUser): static
    {
        $this->reviewedUser = $reviewedUser;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getReviewType(): ?string
    {
        return $this->reviewType;
    }

    public function setReviewType(string $reviewType): static
    {
        $this->reviewType = $reviewType;

        return $this;
    }

    public function getOverallRating(): ?int
    {
        return $this->overallRating;
    }

    public function setOverallRating(int $overallRating): static
    {
        $this->overallRating = $overallRating;

        return $this;
    }

    public function getProductQualityRating(): ?int
    {
        return $this->productQualityRating;
    }

    public function setProductQualityRating(?int $productQualityRating): static
    {
        $this->productQualityRating = $productQualityRating;

        return $this;
    }

    public function getPunctualityRating(): ?int
    {
        return $this->punctualityRating;
    }

    public function setPunctualityRating(?int $punctualityRating): static
    {
        $this->punctualityRating = $punctualityRating;

        return $this;
    }

    public function getFriendlinessRating(): ?int
    {
        return $this->friendlinessRating;
    }

    public function setFriendlinessRating(?int $friendlinessRating): static
    {
        $this->friendlinessRating = $friendlinessRating;

        return $this;
    }

    public function getCommunicationRating(): ?int
    {
        return $this->communicationRating;
    }

    public function setCommunicationRating(?int $communicationRating): static
    {
        $this->communicationRating = $communicationRating;

        return $this;
    }

    public function getReliabilityRating(): ?int
    {
        return $this->reliabilityRating;
    }

    public function setReliabilityRating(?int $reliabilityRating): static
    {
        $this->reliabilityRating = $reliabilityRating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeImmutable $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function getModerationReason(): ?string
    {
        return $this->moderationReason;
    }

    public function setModerationReason(?string $moderationReason): static
    {
        $this->moderationReason = $moderationReason;

        return $this;
    }
}
