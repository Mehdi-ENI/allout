<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
class AnnulationDTO
{
    #[Assert\NotBlank(message: 'Le motif est obligatoire.')]
    #[Assert\Length(min: 5, max: 500)]
    public ?string $motif = null;

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): void
    {
        $this->motif = $motif;
    }


}
