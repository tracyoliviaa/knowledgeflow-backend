// tests/Controller/ItemControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemControllerTest extends WebTestCase
{
    public function testCreateItem(): void
    {
        $client = static::createClient();
        
        // Login-Token holen
        $client->request('POST', '/api/login', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@example.com', 'password' => 'test123'])
        );
        $token = json_decode($client->getResponse()->getContent())->token;

        // Item erstellen
        $client->request('POST', '/api/items', [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode(['title' => 'Test Item', 'type' => 'note'])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['message' => 'Item created']);
    }
}