<?php

namespace App\Tests\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Service\ParticipantService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantServiceTest extends TestCase
{
    private string $uploadDir;

    protected function setUp(): void
    {
        $this->uploadDir = sys_get_temp_dir();
    }

    /**
     * Crée une instance du service avec les dépendances fournies.
     */
    private function makeService(
        EntityManagerInterface $entityManager,
        ParticipantRepository  $repository
    ): ParticipantService {
        return new ParticipantService($entityManager, $repository, $this->uploadDir);
    }

    // -------------------------------------------------------------------------
    // getParticipant()
    // -------------------------------------------------------------------------

    #[Test]
    public function getParticipant_retourne_le_participant_si_trouve(): void
    {
        $participant   = new Participant();
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createMock(ParticipantRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($participant);

        $result = $this->makeService($entityManager, $repository)->getParticipant(1);

        $this->assertSame($participant, $result);
    }

    #[Test]
    public function getParticipant_leve_une_exception_si_non_trouve(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createStub(ParticipantRepository::class);
        $repository->method('find')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeService($entityManager, $repository)->getParticipant(999);
    }

    // -------------------------------------------------------------------------
    // getParticipantOrCurrent()
    // -------------------------------------------------------------------------

    #[Test]
    public function getParticipantOrCurrent_retourne_utilisateur_courant_si_id_null(): void
    {
        $currentUser   = new Participant();
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $repository    = $this->createStub(ParticipantRepository::class);

        $result = $this->makeService($entityManager, $repository)
            ->getParticipantOrCurrent(null, $currentUser);

        $this->assertSame($currentUser, $result);
    }

    #[Test]
    public function getParticipantOrCurrent_retourne_le_participant_si_id_fourni(): void
    {
        $currentUser   = new Participant();
        $other         = new Participant();
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createMock(ParticipantRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($other);

        $result = $this->makeService($entityManager, $repository)
            ->getParticipantOrCurrent(42, $currentUser);

        $this->assertSame($other, $result);
    }

    #[Test]
    public function getParticipantOrCurrent_leve_une_exception_si_id_introuvable(): void
    {
        $currentUser   = new Participant();
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createStub(ParticipantRepository::class);
        $repository->method('find')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->makeService($entityManager, $repository)
            ->getParticipantOrCurrent(99, $currentUser);
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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $this->makeService($entityManager, $repository)
            ->changePassword($participant, 'old', 'new', $passwordHasher);

        $this->assertEquals('hashed_new_password', $participant->getPassword());
    }

    #[Test]
    public function changePassword_leve_une_exception_si_mot_de_passe_incorrect(): void
    {
        $participant    = new Participant();
        $repository     = $this->createStub(ParticipantRepository::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);

        $passwordHasher->method('isPasswordValid')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Le mot de passe actuel est incorrect.');

        $this->makeService($entityManager, $repository)
            ->changePassword($participant, 'wrong', 'new', $passwordHasher);
    }

    // -------------------------------------------------------------------------
    // updateParticipant()
    // -------------------------------------------------------------------------

    #[Test]
    public function updateParticipant_persiste_sans_image(): void
    {
        $participant = new Participant();
        $repository  = $this->createStub(ParticipantRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($participant);
        $entityManager->expects($this->once())->method('flush');

        $this->makeService($entityManager, $repository)
            ->updateParticipant($participant, null);
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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $this->makeService($entityManager, $repository)
            ->updateParticipant($participant, $imageFile);

        $this->assertNotNull($participant->getImage());
        $this->assertStringEndsWith('.png', $participant->getImage());

        unlink($tmpFile);
    }
}
