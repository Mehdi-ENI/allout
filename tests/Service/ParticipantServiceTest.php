<?php

namespace App\Tests\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Service\ParticipantService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantServiceTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private string $uploadDir;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->uploadDir     = sys_get_temp_dir();
    }

    /**
     * Crée une instance fraîche du service avec le repository fourni.
     * On le recrée dans chaque test pour pouvoir passer un mock ou un stub selon le besoin.
     */
    private function makeService(ParticipantRepository $repository): ParticipantService
    {
        return new ParticipantService(
            $this->entityManager,
            $repository,
            $this->uploadDir
        );
    }

    // -------------------------------------------------------------------------
    // getParticipant()
    // -------------------------------------------------------------------------

    #[Test]
    public function getParticipant_retourne_le_participant_si_trouve(): void
    {
        $participant = new Participant();

        $repository = $this->createMock(ParticipantRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($participant);

        $result = $this->makeService($repository)->getParticipant(1);

        $this->assertSame($participant, $result);
    }

    #[Test]
    public function getParticipant_leve_une_exception_si_non_trouve(): void
    {
        $repository = $this->createStub(ParticipantRepository::class);
        $repository
            ->method('find')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeService($repository)->getParticipant(999);
    }

    // -------------------------------------------------------------------------
    // getParticipantOrCurrent()
    // -------------------------------------------------------------------------

    #[Test]
    public function getParticipantOrCurrent_retourne_utilisateur_courant_si_id_null(): void
    {
        $currentUser = new Participant();
        $repository  = $this->createStub(ParticipantRepository::class);

        $result = $this->makeService($repository)->getParticipantOrCurrent(null, $currentUser);

        $this->assertSame($currentUser, $result);
    }

    #[Test]
    public function getParticipantOrCurrent_retourne_le_participant_si_id_fourni(): void
    {
        $currentUser = new Participant();
        $other       = new Participant();

        $repository = $this->createMock(ParticipantRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($other);

        $result = $this->makeService($repository)->getParticipantOrCurrent(42, $currentUser);

        $this->assertSame($other, $result);
    }

    #[Test]
    public function getParticipantOrCurrent_leve_une_exception_si_id_introuvable(): void
    {
        $currentUser = new Participant();

        $repository = $this->createStub(ParticipantRepository::class);
        $repository
            ->method('find')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeService($repository)->getParticipantOrCurrent(99, $currentUser);
    }

    // -------------------------------------------------------------------------
    // changePassword()
    // -------------------------------------------------------------------------

    #[Test]
    public function changePassword_change_le_mot_de_passe_si_valide(): void
    {
        $participant    = new Participant();
        $repository     = $this->createStub(ParticipantRepository::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);

        $passwordHasher->method('isPasswordValid')->willReturn(true);
        $passwordHasher->method('hashPassword')->willReturn('hashed_new_password');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->makeService($repository)->changePassword($participant, 'old', 'new', $passwordHasher);

        $this->assertEquals('hashed_new_password', $participant->getPassword());
    }

    #[Test]
    public function changePassword_leve_une_exception_si_mot_de_passe_incorrect(): void
    {
        $participant    = new Participant();
        $repository     = $this->createStub(ParticipantRepository::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);

        $passwordHasher->method('isPasswordValid')->willReturn(false);

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Le mot de passe actuel est incorrect.');

        $this->makeService($repository)->changePassword($participant, 'wrong', 'new', $passwordHasher);
    }

    // -------------------------------------------------------------------------
    // updateParticipant()
    // -------------------------------------------------------------------------

    #[Test]
    public function updateParticipant_persiste_sans_image(): void
    {
        $participant = new Participant();
        $repository  = $this->createStub(ParticipantRepository::class);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($participant);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->makeService($repository)->updateParticipant($participant, null);
    }

    #[Test]
    public function updateParticipant_gere_upload_image(): void
    {
        $participant = new Participant();
        $participant->setPseudo('jean');
        $repository = $this->createStub(ParticipantRepository::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_') . '.png';
        file_put_contents($tmpFile, 'fake image content');

        $imageFile = $this->getMockBuilder(UploadedFile::class)
            ->setConstructorArgs([$tmpFile, 'test.png', 'image/png', null, true])
            ->onlyMethods(['move', 'guessExtension'])
            ->getMock();

        $imageFile->method('guessExtension')->willReturn('png');
        $imageFile->expects($this->once())->method('move');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->makeService($repository)->updateParticipant($participant, $imageFile);

        $this->assertNotNull($participant->getImage());
        $this->assertStringEndsWith('.png', $participant->getImage());

        unlink($tmpFile);
    }
}
