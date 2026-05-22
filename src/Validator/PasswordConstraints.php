<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class PasswordConstraints
{
    public static function get(): array
    {
        return [
            new NotBlank(message: 'Veuillez saisir un mot de passe'),
            new Length(
                min: 12,
                max: 4096,
                minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères',
            ),
            //new PasswordStrength(),
            //new NotCompromisedPassword(),
        ];
    }
}
