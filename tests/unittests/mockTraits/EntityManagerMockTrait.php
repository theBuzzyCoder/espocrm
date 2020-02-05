<?php
namespace tests\unittests\mockTraits;

use Espo\Core\ORM\EntityManager;

trait EntityManagerMockTrait
{
    private $entityManagerMockDisabledMethods = [
        'getMapperClassName',
        'getRepository',
        'setMetadata',
        'hasRepository',
        'getMetadata',
        'getOrmMetadata',
        'normalizeRepositoryName',
        'normalizeEntityName',
        'createCollection',
        'createSthCollection',
        'getEntityFactory',
        'init'
    ];
    
    private $entityManagerMockMethods = [
        'getEntity',
        'saveEntity',
        'removeEntity',
        'createEntity',
        'fetchEntity',
    ];

    private $entityManagerUnitTestNotSupported = [
        'runQuery',
        'getPDO',
        'getQuery',
        'getMapper',
        'initPDO',
    ];
    public function loadEntityManagerMock()
    {
        $config = $this->getContainer()->get('config');
        $params = array(
            'host' => $config->get('database.host'),
            'port' => $config->get('database.port'),
            'dbname' => $config->get('database.dbname'),
            'user' => $config->get('database.user'),
            'charset' => $config->get('database.charset', 'utf8'),
            'password' => $config->get('database.password'),
            'metadata' => $this->getContainer()->get('ormMetadata')->getData(),
            'repositoryFactoryClassName' => '\\Espo\\Core\\ORM\\RepositoryFactory',
            'driver' => $config->get('database.driver'),
            'platform' => $config->get('database.platform'),
            'sslCA' => $config->get('database.sslCA'),
            'sslCert' => $config->get('database.sslCert'),
            'sslKey' => $config->get('database.sslKey'),
            'sslCAPath' => $config->get('database.sslCAPath'),
            'sslCipher' => $config->get('database.sslCipher')
        );
        $entityManager = $this->getMockBuilder(EntityManager::class)
                            ->setConstructorArgs([$params])
                            ->setMethodsExcept($this->entityManagerMockDisabledMethods)
                            ->getMock();
        $entityManager->setEspoMetadata($this->getContainer()->get('metadata'));
        $entityManager->setHookManager($this->getContainer()->get('hookManager'));
        $entityManager->setContainer($this->getContainer());

        // foreach ($this->entityManagerUnitTestNotSupported as $method) {
        //     $entityManager->expects($this->any())->method($method)->will($this->throwException(new \Exception('Unit Test not supported')));
        // }

        return $entityManager;

        // $entityManager->expects($this->any())
        //             ->method("getEntity")
        //             ->with($this->callback(function (string $entityType, string $id) {
        //                 $data = $this->loadTestData($entityType, $id);
        //                 $entity = \Espo\Core\ORM\Entity();
        //                 $entity->set($data);
        //                 return $entity;
        //             }));
        
        // $entityManager->expects($this->any())
        //     ->method("fetchEntity")
        //     ->with($this->callback(function (string $entityType, string $id) {
        //         return $id;
        //     }));
    }
}