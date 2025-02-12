<?php declare(strict_types=1);

namespace App\Tests\Unit\Controller\API;

use Generator;

class AccountControllerTest extends BaseTest
{
    protected ?string $apiEndpoint = 'accounts';

    protected ?string $apiUser = 'admin';

    protected array $expectedObjects = [
        1 => [
            "id"       => "1",
            "username" => "admin",
            "team_id"  => "1",
            "type"     => "admin",
            "ip"       => null,
        ],
        2 => [
            "id"       => "2",
            "username" => "judgehost",
            "team_id"  => null,
            "type"     => null,
            "ip"       => null,
        ],
        3 => [
            "id"       => "3",
            "username" => "demo",
            "team_id"  => "2",
            "type"     => "team",
            "ip"       => null,
        ],
    ];

    protected array $expectedAbsent = ['4242', 'nonexistent'];

    /**
     * @dataProvider provideCurrentAccount
     */
    public function testCurrentAccount(string $user, array $expectedData): void
    {
        $url      = $this->helperGetEndpointURL('account');
        $response = $this->verifyApiJsonResponse('GET', $url, 200, $user);

        foreach ($expectedData as $key => $value) {
            self::assertArrayHasKey($key, $response, "$key is present");
            self::assertEquals($value, $response[$key], "$key has correct value");
        }
    }

    public function testCurrentAccountNotLoggedIn(): void
    {
        $url = $this->helperGetEndpointURL('account');
        $this->verifyApiJsonResponse('GET', $url, 404);
    }

    public function provideCurrentAccount(): Generator
    {
        yield ['admin', ['id' => '1', 'team_id' => '1', 'username' => 'admin', 'type' => 'admin']];
        yield ['demo', ['id' => '3', 'team_id' => '2', 'username' => 'demo', 'type' => 'team']];
    }

    public function testNewAddedAccount(): void
    {
        $myURL = $this->helperGetEndpointURL($this->apiEndpoint);
        $objectsBeforeTest = $this->verifyApiJsonResponse('GET', $myURL, 200, $this->apiUser);

        $newUserPostData = ['username' => 'newUser',
                            'name' => 'newUserWithName',
                            'password' => 'xkcd-password-style-password',
                            'roles' => ['team']];
        $url = $this->helperGetEndpointURL('users');
        $this->verifyApiJsonResponse('POST', $url, 201, 'admin', $newUserPostData);

        $objectsAfterTest  = $this->verifyApiJsonResponse('GET', $myURL, 200, $this->apiUser);
        $newItems = array_map('unserialize', array_diff(array_map('serialize', $objectsAfterTest), array_map('serialize', $objectsBeforeTest)));
        self::assertEquals(count($newItems), 1);
        $listKey = array_keys($newItems)[0];
        foreach ($newUserPostData as $key => $value) {
            if ($key !== 'password') {
                // For security we don't output the password in the API
                self::assertEquals($newItems[$listKey][$key], $value);
            }
        }
    }
}
