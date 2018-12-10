<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CoreAdminHome\tests\Integration;

use Piwik\Archive\ArchivePurger;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\Mail;
use Piwik\Plugins\CoreAdminHome\Emails\JsTrackingCodeMissingEmail;
use Piwik\Plugins\CoreAdminHome\Tasks;
use Piwik\Plugins\CoreAdminHome\Tasks\ArchivesToPurgeDistributedList;
use Piwik\Scheduler\Task;
use Piwik\Tests\Fixtures\RawArchiveDataWithTempAndInvalidated;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Psr\Log\NullLogger;

/**
 * @group Core
 */
class TasksTest extends IntegrationTestCase
{
    /**
     * @var RawArchiveDataWithTempAndInvalidated
     */
    public static $fixture;

    /**
     * @var Tasks
     */
    private $tasks;

    /**
     * @var Date
     */
    private $january;

    /**
     * @var Date
     */
    private $february;

    /**
     * @var Mail
     */
    private $mail;

    public function setUp()
    {
        parent::setUp();

        self::$fixture->loginAsSuperUser();

        $this->january = Date::factory('2015-01-01');
        $this->february = Date::factory('2015-02-01');

        $archivePurger = new ArchivePurger();
        $archivePurger->setTodayDate(Date::factory('2015-02-27'));
        $archivePurger->setYesterdayDate(Date::factory('2015-02-26'));
        $archivePurger->setNow(Date::factory('2015-02-27 08:00:00')->getTimestamp());

        $this->tasks = new Tasks($archivePurger, new NullLogger());

        $this->mail = null;
    }

    public function tearDown()
    {
        unset($_GET['trigger']);

        parent::tearDown();
    }

    public function test_purgeInvalidatedArchives_PurgesCorrectInvalidatedArchives_AndOnlyPurgesDataForDatesAndSites_InInvalidatedReportsDistributedList()
    {
        $this->setUpInvalidatedReportsDistributedList($dates = array($this->february));

        $this->tasks->purgeInvalidatedArchives();

        self::$fixture->assertInvalidatedArchivesPurged($this->february);
        self::$fixture->assertInvalidatedArchivesNotPurged($this->january);

        // assert invalidated reports distributed list has changed
        $archivesToPurgeDistributedList = new ArchivesToPurgeDistributedList();
        $yearMonths = $archivesToPurgeDistributedList->getAll();

        $this->assertEmpty($yearMonths);
    }

    public function test_purgeOutdatedArchives_SkipsPurging_WhenBrowserArchivingDisabled_AndCronArchiveTriggerNotPresent()
    {
        Rules::setBrowserTriggerArchiving(false);
        unset($_GET['trigger']);

        $wasPurged = $this->tasks->purgeOutdatedArchives();
        $this->assertFalse($wasPurged);
    }

    public function test_purgeOutdatedArchives_Purges_WhenBrowserArchivingEnabled_AndCronArchiveTriggerPresent()
    {
        Rules::setBrowserTriggerArchiving(false);
        $_GET['trigger'] = 'archivephp';

        $wasPurged = $this->tasks->purgeOutdatedArchives();
        $this->assertTrue($wasPurged);
    }

    public function test_schedule_addsRightAmountOfTasks()
    {
        Fixture::createWebsite('2012-01-01 00:00:00');
        Fixture::createWebsite(Date::now()->subDay(5)->getDatetime());
        Fixture::createWebsite(Date::now()->subDay(2)->getDatetime());
        Fixture::createWebsite(Date::now()->subDay(4)->getDatetime());
        Fixture::createWebsite(Date::now()->getDatetime());

        $this->tasks->schedule();

        $tasks = $this->tasks->getScheduledTasks();
        $tasks = array_map(function (Task $task) { return $task->getMethodName() . '.' . $task->getMethodParameter(); }, $tasks);

        $expected = [
            'purgeOutdatedArchives.',
            'purgeInvalidatedArchives.',
            'optimizeArchiveTable.',
            'updateSpammerBlacklist.',
            'checkSiteHasTrackedVisits.2',
            'checkSiteHasTrackedVisits.3',
            'checkSiteHasTrackedVisits.4',
            'checkSiteHasTrackedVisits.5',
        ];
        $this->assertEquals($expected, $tasks);
    }

    public function test_checkSiteHasTrackedVisits_doesNothingIfTheSiteHasVisits()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');

        $tracker = Fixture::getTracker($idSite, '2014-02-02 03:04:05');
        Fixture::checkResponse($tracker->doTrackPageView('alskdjfs'));

        $this->assertEquals(1, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertEmpty($this->mail);
    }

    public function test_checkSiteHasTrackedVisits_doesNothingIfSiteHasNoCreationUser()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');
        Db::query("UPDATE " . Common::prefixTable('site') . ' SET creator_login = NULL WHERE idsite = ' . $idSite . ';');

        $this->assertEquals(0, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertEmpty($this->mail);
    }

    public function test_checkSitesHasTrackedVisits_sendsJsCodeMissingEmailIfSiteHasNoVisitsAndCreationUser()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');

        $this->assertEquals(0, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertInstanceOf(JsTrackingCodeMissingEmail::class, $this->mail);

        /** @var JsTrackingCodeMissingEmail $mail */
        $mail = $this->mail;
        $this->assertEquals($mail->getLogin(), 'superUserLogin');
        $this->assertEquals($mail->getEmailAddress(), 'hello@example.org');
        $this->assertEquals($mail->getIdSite(), $idSite);
    }

    /**
     * @param Date[] $dates
     */
    private function setUpInvalidatedReportsDistributedList($dates)
    {
        $yearMonths = array();
        foreach ($dates as $date) {
            $yearMonths[] = $date->toString('Y_m');
        }

        $archivesToPurgeDistributedList = new ArchivesToPurgeDistributedList();
        $archivesToPurgeDistributedList->add($yearMonths);
    }

    public function provideContainerConfig()
    {
        return [
            'observers.global' => \DI\add([
                ['Mail.send', function (Mail $mail) {
                    $this->mail = $mail;
                }],
            ]),
        ];
    }

    /**
     * @param Fixture $fixture
     */
    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = true;
    }
}

TasksTest::$fixture = new RawArchiveDataWithTempAndInvalidated();