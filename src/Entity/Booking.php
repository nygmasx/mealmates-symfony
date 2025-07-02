<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $isConfirmed = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $isOutdated = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?\DateTimeImmutable $outdatedAt = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?float $totalPrice = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\OneToOne(mappedBy: 'booking', cascade: ['persist', 'remove'])]
    private ?Chat $chat = null;

    #[ORM\Column()]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $isPaid = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?\DateTimeImmutable $isPaidAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?string $paymentIntentId = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $isDelivered = false;

    /**
     * @var Collection<int, QrValidationToken>
     */
    #[ORM\OneToMany(targetEntity: QrValidationToken::class, mappedBy: 'booking', orphanRemoval: true)]
    private Collection $qrValidationTokens;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $isDeliveredAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $validationMethod = null;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $buyerReviewLeft = false;

    #[ORM\Column]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?bool $sellerReviewLeft = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['booking:summary', 'booking:read'])]
    private ?\\DateTimeImmutable $reviewReminderSentAt = null;

    public function __construct()
    {
        $this->qrValidationTokens = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function isOutdated(): ?bool
    {
        return $this->isOutdated;
    }

    public function setIsOutdated(bool $isOutdated): static
    {
        $this->isOutdated = $isOutdated;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getOutdatedAt(): ?\DateTimeImmutable
    {
        return $this->outdatedAt;
    }

    public function setOutdatedAt(\DateTimeImmutable $outdatedAt): static
    {
        $this->outdatedAt = $outdatedAt;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        if ($chat === null && $this->chat !== null) {
            $this->chat->setBooking(null);
        }

        if ($chat !== null && $chat->getBooking() !== $this) {
            $chat->setBooking($this);
        }

        $this->chat = $chat;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getIsPaidAt(): ?\DateTimeImmutable
    {
        return $this->isPaidAt;
    }

    public function setIsPaidAt(?\DateTimeImmutable $isPaidAt): static
    {
        $this->isPaidAt = $isPaidAt;

        return $this;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): static
    {
        $this->paymentIntentId = $paymentIntentId;

        return $this;
    }

    public function isDelivered(): ?bool
    {
        return $this->isDelivered;
    }

    public function setIsDelivered(bool $isDelivered): static
    {
        $this->isDelivered = $isDelivered;

        return $this;
    }

    /**
     * @return Collection<int, QrValidationToken>
     */
    public function getQrValidationTokens(): Collection
    {
        return $this->qrValidationTokens;
    }

    public function addQrValidationToken(QrValidationToken $qrValidationToken): static
    {
        if (!$this->qrValidationTokens->contains($qrValidationToken)) {
            $this->qrValidationTokens->add($qrValidationToken);
            $qrValidationToken->setBooking($this);
        }

        return $this;
    }

    public function removeQrValidationToken(QrValidationToken $qrValidationToken): static
    {
        if ($this->qrValidationTokens->removeElement($qrValidationToken)) {
            // set the owning side to null (unless already changed)
            if ($qrValidationToken->getBooking() === $this) {
                $qrValidationToken->setBooking(null);
            }
        }

        return $this;
    }

    public function getIsDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->isDeliveredAt;
    }

    public function setIsDeliveredAt(?\DateTimeImmutable $isDeliveredAt): static
    {
        $this->isDeliveredAt = $isDeliveredAt;

        return $this;
    }

    public function getValidationMethod(): ?string
    {
        return $this->validationMethod;
    }

    public function setValidationMethod(?string $validationMethod): static
    {
        $this->validationMethod = $validationMethod;

        return $this;
    }

    public function isBuyerReviewLeft(): ?bool
    {
        return $this->buyerReviewLeft;
    }

    public function setBuyerReviewLeft(bool $buyerReviewLeft): static
    {
        $this->buyerReviewLeft = $buyerReviewLeft;

        return $this;
    }

    public function isSellerReviewLeft(): ?bool
    {
        return $this->sellerReviewLeft;
    }

    public function setSellerReviewLeft(bool $sellerReviewLeft): static
    {
        $this->sellerReviewLeft = $sellerReviewLeft;

        return $this;
    }

    public function getReviewReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reviewReminderSentAt;
    }

    public function setReviewReminderSentAt(?\DateTimeImmutable $reviewReminderSentAt): static
    {
        $this->reviewReminderSentAt = $reviewReminderSentAt;

        return $this;
    }

    public function areAllReviewsCompleted(): bool
    {
        return $this->buyerReviewLeft && $this->sellerReviewLeft;
    }

    public function canSendReviewReminder(): bool
    {
        if (!$this->isDelivered()) {
            return false;
        }

        if ($this->areAllReviewsCompleted()) {
            return false;
        }

        if ($this->reviewReminderSentAt === null) {
            return true;
        }

        $daysSinceLastReminder = (new \DateTimeImmutable())->diff($this->reviewReminderSentAt)->days;
        return $daysSinceLastReminder >= 7;
    }
}
