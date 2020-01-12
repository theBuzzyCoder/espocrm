<?php

namespace tests\unittests\mockTraits;

use Espo\ORM\RepositoryFactory;

trait RepositoryFactoryMockTrait
{
    public function loadRepositoryFactoryMock($entityManager, $entityFactory)
    {
        $mock = $this->getMockBuilder(RepositoryFactory::class)
                    ->setMethodsExcept(['normalizeName', 'setDefaultRepositoryClassName'])
                    ->setConstructorArgs([$entityManager, $entityFactory])
                    ->getMock();

        $mock->expects($this->any())->method('create')->with(
            $this->stringContains("\\Espo\\"),
            $this->anything(),
            $this->anything()
        )->will($this->returnCallback(function (string $repositoryName, EntityManager $entityManager, EntityFactory $entityFactory) {
            $entityType = array_pop(explode('\\', $repositoryName));
            return new $repositoryName($entityType, $entityManager, $entityFactory);
        }));
        return $mock;
    }
}