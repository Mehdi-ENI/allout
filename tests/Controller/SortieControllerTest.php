<?php
namespace App\Tests\Controller;

use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SortieControllerTest extends WebTestCase
{
    public function testCreateSortie(): void
    {
        $client = static::createClient();
        $participantRepository = static::getContainer()->get(ParticipantRepository::class);
        $user = $participantRepository->findOneBy(['email' => 'admin@test.com']);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/sortie/create');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form();

        $form['sortie[nom]'] = 'Sortie de test';
        $form['sortie[dateHeureDebut]'] = '2026-07-01T18:00';
        $form['sortie[dateLimiteInscription]'] = '2026-06-25';

        $form['sortie[duree][days]'] = 0;
        $form['sortie[duree][hours]'] = 2;
        $form['sortie[duree][minutes]'] = 0;

        $form['sortie[nbInscriptionsMax]'] = 10;

        $form['sortie[infosSortie]'] = 'Description de test';

        // IDs existants dans la BDD de test
        $form['sortie[site]'] = 1;
        $form['sortie[lieu]'] = 1;

        $client->submit($form);

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertSelectorTextContains('h2', 'Sortie de test');
    }

    public function testDetailSortie(): void
    {
        $client = static::createClient();

        $client->request('GET', '/sortie/2');

        self::assertResponseIsSuccessful();
    }

    public function testDeleteSortie(): void
    {
        $client = static::createClient();

        $participantRepository = static::getContainer()->get(ParticipantRepository::class);

        $user = $participantRepository->findOneBy(['email' => 'admin@test.com']);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/sortie/1');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Supprimer')->form();
        $client->submit($form);
        self::assertResponseRedirects('/sortie/list');
    }
}
