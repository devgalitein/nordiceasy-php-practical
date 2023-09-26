<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email', message: 'User with this email already exists', ignoreNull: 'email')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Please enter your name")]
    private ?string $name = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "Please enter your email")]
    #[Assert\Email(message: "Please enter valid email")]
    private ?string $email = null;

    #[ORM\Column(length: 50,nullable: true)]
    // As of now I have validated phone number should contain 10 digits only , country code not included
    #[Assert\Regex(pattern :  '/^\d{10}$/',message: "Phone number should be a 10-digit number")]
    private ?string $phone = null;

    #[ORM\Column(length: 1000)]
    #[Assert\NotBlank(message: "Please enter your comment")]
    #[Assert\Length(max: 1000,maxMessage: 'Your comment must contain a maximum of {{ limit }} characters')]
    private ?string $comment = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Please enter your client_id")]
    #[Assert\Uuid(message: "Invalid UUID format for client_id")]
    private ?string $client_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(? string $phone): static
    {
        $this->phone = $phone;

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

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(?string $client_id): static
    {
        $this->client_id = $client_id;

        return $this;
    }
}
