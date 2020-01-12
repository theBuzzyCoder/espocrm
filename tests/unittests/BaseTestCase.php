<?php

namespace tests\unittests;

use PHPUnit\Framework\TestCase;
use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Core\Utils\File\Manager as FileManager;

use tests\unittests\mockTraits\RepositoryFactoryMockTrait;
use tests\unittests\mockTraits\EntityManagerMockTrait;

abstract class BaseTestCase extends TestCase
{

    const BUILD_DATA_PATH = 'tests/unittests/data/buildData';

    
    private $config = null;
    
    private $application = null;
    
    private function loadBuildData($name)
    {
        $filePath = self::BUILD_DATA_PATH."/$name.json";
        return json_decode(file_get_contents($filePath), 1);
    }
    
    const TEST_DATA_PATH = 'tests/unittests/data/testData';
    public function loadTestData($entity, $id)
    {
        $filePath = self::TEST_DATA_PATH."/$entity/{$id}.json";
        return json_decode(file_get_contents($filePath), 1);
    }

    private function getConfig()
    {
        $configData = $this->loadBuildData('config');
        $config = new Config(new FileManager());
        if (file_exists('data/config.php')) {
            // We load the original config file so that we don't modify any configuration
            // and end up saving, which would later require the developer to reinstall espocrm
            // because of change in salt key and crypt keys.
            $this->invokeProtectedMethods($config, "loadConfig", []);
        } else {
            // When data/config.php is not present, we load the configuration set
            // by us
            $config->set($configData);
            $config->save();
        }
        $config->set('useCache', false);
        return $config;
    }

    private function getAdminUser()
    {
        $adminUserData = $this->loadTestData('User', '1');
        $admin = User();
        $admin->set($adminUserData);
        return $admin;
    }

    protected function invokeProtectedMethods($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function setProtectedProperty($object, $name, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function getContainer()
    {
        return $this->application->getContainer();
    }

    public function setUp(): void
    {
        // On installation of EspoCRM config gets created first. Based on the config,
        // the application starts booting the required dependencies one by one. Starting with log
        $this->config = $this->getConfig();

        $this->container = $this->getMockBuilder(Container::class)->setMethodsExcept(['get', 'set'])->getMock();
        $this->invokeProtectedMethods($this->container, 'set', ['config', $this->config]);

        $this->application = $this->getMockBuilder(Application::class)->setMethodsExcept(['getContainer'])->disableOriginalConstructor()->getMock();
        $this->setProtectedProperty($this->application, 'container', $this->container);
    }

    // /**
    //  * @test
    //  */
    // public function configDataType()
    // {
    //     $config = $this->application->getContainer()->get('config');
    //     $this->assertSame($config, $this->config);

    //     $this->assertTrue($this->config->get('logger.rotation'));
    // }

    // /**
    //  * @test
    //  */
    // public function metadata()
    // {
    //     $metadata = $this->application->getContainer()->get('metadata');
    //     $this->assertInstanceOf(Metadata::class, $metadata);
    // }

    // /**
    //  * @test
    //  */
    // public function repositoryFactory()
    // {
    //     $entityManager = $this->loadEntityManagerMock();
    //     $repositoryFactory = $this->loadRepositoryFactoryMock($entityManager, $entityManager->getEntityFactory());
    //     $leadRepository = $repositoryFactory->create('\\Espo\\Modules\\Crm\\Repositories\\Lead', $entityManager, $entityManager->getEntityFactory());
    //     $this->assertInstanceOf(Espo\Modules\Crm\Repositories\Lead::class, $leadRepository);
    // }

    public function tearDown(): void
    {
        unset($this->application);
        unset($this->config);
    }
}
