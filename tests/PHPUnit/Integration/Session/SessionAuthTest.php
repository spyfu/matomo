<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Tests\Integration\Session;

use Piwik\AuthResult;
use Piwik\Container\StaticContainer;
use Piwik\Session\SessionAuth;
use Piwik\Session\SessionFingerprint;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\Model as UsersModel;

class SessionAuthTest extends IntegrationTestCase
{
    const TEST_OTHER_USER = 'testuser';

    /**
     * @var SessionAuth
     */
    private $testInstance;

    public function setUp()
    {
        parent::setUp();

        UsersManagerAPI::getInstance()->addUser(self::TEST_OTHER_USER, 'testpass', 'test@example.com');

        $this->testInstance = StaticContainer::get(SessionAuth::class);
    }

    public function test_authenticate_ReturnsFailure_IfNoSessionExists()
    {
        $this->initializeSession(Fixture::ADMIN_USER_LOGIN);

        $this->destroySession();

        $result = $this->testInstance->authenticate();
        $this->assertEquals(AuthResult::FAILURE, $result->getCode());
    }

    public function test_authenticate_ReturnsFailure_IfAuthenticatedSession_AndPasswordChangedAfterSessionCreated()
    {
        $this->initializeSession(self::TEST_OTHER_USER);

        sleep(1);

        UsersManagerAPI::getInstance()->updateUser(self::TEST_OTHER_USER, 'testpass2');

        $result = $this->testInstance->authenticate();
        $this->assertEquals(AuthResult::FAILURE, $result->getCode());

        $this->assertEmpty($_SESSION);
    }

    public function test_authenticate_ReturnsFailure_IfUsersModelReturnsIncorrectUser()
    {
        $this->initializeSession(self::TEST_OTHER_USER);

        $sessionAuth = new SessionAuth(new MockUsersModel([
            'login' => 'wronguser',
        ]));
        $result = $sessionAuth->authenticate();

        $this->assertEquals(AuthResult::FAILURE, $result->getCode());
    }

    public function test_authenticate_ReturnsSuccess_IfUserDataHasNoPasswordModifiedTimestamp()
    {
        $this->initializeSession(self::TEST_OTHER_USER);

        $usersModel = new UsersModel();
        $user = $usersModel->getUser(self::TEST_OTHER_USER);
        unset($user['ts_password_modified']);

        $sessionAuth = new SessionAuth(new MockUsersModel($user));
        $result = $sessionAuth->authenticate();

        $this->assertEquals(AuthResult::SUCCESS, $result->getCode());
    }

    private function initializeSession($userLogin)
    {
        $sessionFingerprint = new SessionFingerprint();
        $sessionFingerprint->initialize($userLogin);
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

        $fixture->createSuperUser = true;
    }

    private function destroySession()
    {
        unset($_SESSION[SessionFingerprint::SESSION_INFO_SESSION_VAR_NAME]);
        unset($_SESSION[SessionFingerprint::USER_NAME_SESSION_VAR_NAME]);
    }

    public function provideContainerConfig()
    {
        return [
            SessionAuth::class => \DI\object()
                ->constructorParameter('shouldDestroySession', false),
        ];
    }
}

class MockUsersModel extends UsersModel
{
    /**
     * @var array
     */
    private $userData;

    public function __construct(array $userData)
    {
        parent::__construct();
        $this->userData = $userData;
    }

    public function getUser($userLogin)
    {
        return $this->userData;
    }
}