<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';
}
