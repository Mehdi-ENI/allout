<?php

namespace App\Service;

use App\Entity\Sortie;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment     $twig,
    ) {}

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendAnnulationSortie(Sortie $sortie): void
    {

        dump(count($sortie->getParticipants())); // à supprimer après le test

        foreach ($sortie->getParticipants() as $participant) {
            $email = (new Email())
                ->from('noreply@sorties.fr')
                ->to($participant->getEmail())
                ->subject("Sortie annulée : {$sortie->getNom()}")
                ->html($this->twig->render('emails/annulation_sortie.html.twig', [
                    'sortie' => $sortie,
                    'participant' => $participant,
                ]));

            $this->mailer->send($email);
        }
    }
}
