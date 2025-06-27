<?php

namespace App\Entity;

use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChatRepository::class)]
class Chat
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['chat:list', 'chat:read'])]
    private ?Uuid $id = null;

    #[ORM\Column]
    #[Groups(['chat:list', 'chat:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['chat:list', 'chat:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'chats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chat:list', 'chat:read'])]
    private ?Product $relatedProduct = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'chat')]
    #[Groups(['chat:read'])]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'chats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chat:list', 'chat:read'])]
    private ?User $userOne = null;

    #[ORM\ManyToOne(inversedBy: 'chats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chat:list', 'chat:read'])]
    private ?User $userTwo = null;

    #[ORM\Column(nullable: true)]
    #[Groups([ 'chat:read'])]
    private ?\DateTimeImmutable $userOneLastSeenAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['chat:read'])]
    private ?\DateTimeImmutable $userTwoLastSeenAt = null;

    #[ORM\OneToOne(inversedBy: 'chat')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['chat:read'])]
    private ?Booking $booking = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRelatedProduct(): ?Product
    {
        return $this->relatedProduct;
    }

    public function setRelatedProduct(?Product $relatedProduct): static
    {
        $this->relatedProduct = $relatedProduct;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setChat($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getChat() === $this) {
                $message->setChat(null);
            }
        }

        return $this;
    }

    public function getUserOne(): ?User
    {
        return $this->userOne;
    }

    public function setUserOne(?User $userOne): static
    {
        $this->userOne = $userOne;

        return $this;
    }

    public function getUserTwo(): ?User
    {
        return $this->userTwo;
    }

    public function setUserTwo(?User $userTwo): static
    {
        $this->userTwo = $userTwo;

        return $this;
    }

    public function getUserOneLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->userOneLastSeenAt;
    }

    public function setUserOneLastSeenAt(?\DateTimeImmutable $userOneLastSeenAt): static
    {
        $this->userOneLastSeenAt = $userOneLastSeenAt;

        return $this;
    }

    public function getUserTwoLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->userTwoLastSeenAt;
    }

    public function setUserTwoLastSeenAt(?\DateTimeImmutable $userTwoLastSeenAt): static
    {
        $this->userTwoLastSeenAt = $userTwoLastSeenAt;

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
}
