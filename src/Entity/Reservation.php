<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Room $room = null;

    #[ORM\Column(name: 'full_name', length: 100)]
    private string $fullName = '';

    #[ORM\Column(length: 100)]
    private string $email = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'room_type', length: 100)]
    private string $roomType = '';

    #[ORM\Column(name: 'checkin_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $checkinDate = null;

    #[ORM\Column(name: 'checkout_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $checkoutDate = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 1])]
    private int $adults = 1;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $children = 0;

    #[ORM\Column(name: 'additional_guests', type: Types::TEXT, nullable: true)]
    private ?string $additionalGuests = null;

    #[ORM\Column(name: 'payment_method', length: 30)]
    private string $paymentMethod = 'cash';

    #[ORM\Column(name: 'special_requests', type: Types::TEXT, nullable: true)]
    private ?string $specialRequests = null;

    #[ORM\Column(name: 'total_amount', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(length: 30, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getRoomType(): string
    {
        return $this->roomType;
    }

    public function setRoomType(string $roomType): static
    {
        $this->roomType = $roomType;
        return $this;
    }

    public function getCheckinDate(): ?\DateTimeInterface
    {
        return $this->checkinDate;
    }

    public function setCheckinDate(?\DateTimeInterface $checkinDate): static
    {
        if ($checkinDate instanceof \DateTimeImmutable) {
            $checkinDate = \DateTime::createFromImmutable($checkinDate);
        }
        $this->checkinDate = $checkinDate;
        return $this;
    }

    public function getCheckoutDate(): ?\DateTimeInterface
    {
        return $this->checkoutDate;
    }

    public function setCheckoutDate(?\DateTimeInterface $checkoutDate): static
    {
        if ($checkoutDate instanceof \DateTimeImmutable) {
            $checkoutDate = \DateTime::createFromImmutable($checkoutDate);
        }
        $this->checkoutDate = $checkoutDate;
        return $this;
    }

    public function getAdults(): int
    {
        return $this->adults;
    }

    public function setAdults(int $adults): static
    {
        $this->adults = $adults;
        return $this;
    }

    public function getChildren(): int
    {
        return $this->children;
    }

    public function setChildren(int $children): static
    {
        $this->children = $children;
        return $this;
    }

    public function getAdditionalGuests(): ?string
    {
        return $this->additionalGuests;
    }

    public function setAdditionalGuests(?string $additionalGuests): static
    {
        $this->additionalGuests = $additionalGuests;
        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getSpecialRequests(): ?string
    {
        return $this->specialRequests;
    }

    public function setSpecialRequests(?string $specialRequests): static
    {
        $this->specialRequests = $specialRequests;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = (string) $totalAmount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
