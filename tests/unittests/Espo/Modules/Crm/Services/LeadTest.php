<?php

namespace tests\unittests\Espo\Modules\Crm\Services;

use tests\unittests\BaseTestCase;
use Espo\Modules\Crm\Services\Lead;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Acl;

class LeadTest extends BaseTestCase
{

    const TEST_DATA_PATH = 'tests/unittests/data/testData';
    public function loadTestData($entity, $id)
    {
        $filePath = self::TEST_DATA_PATH."/$entity/{$id}.json";
        return json_decode(file_get_contents($filePath), 1);
    }

    /**
     * @test
     */
    public function assertGetConvertAttributesAccessForbidden(): void
    {
        $id = '5e1ae70981f4db343';
        $aclDouble = $this->getMockBuilder(Acl::class)->disableOriginalConstructor()->getMock();
        $aclDouble->expects($this->once())->method('check')->willReturn(false);

        $leadServiceDouble = $this->getMockBuilder(Lead::class)
                        ->setMethods(['getAcl'])
                        ->setMethodsExcept(['getConvertAttributes'])
                        ->getMock();
        $leadServiceDouble->expects($this->once())->method('getAcl')->will($this->returnValue($aclDouble));

        $this->expectException(Forbidden::class);
        $this->expectExceptionCode(403);
        $leadServiceDouble->getConvertAttributes($id);
    }

    /**
     * @test
     */
    public function assertGetConvertAttributes(): void
    {
        $id = '5e1ae70981f4db343';
        $data = $this->loadTestData('Lead', $id);
        $defs = $this->getContainer()->get('metadata')->get('entityDefs.Lead');

        $entity = new \Espo\Modules\Crm\Entities\Lead($defs);
        $entity->set($data);

        $aclDouble = $this->getMockBuilder(Acl::class)->disableOriginalConstructor()->getMock();
        $aclDouble->expects($this->once())->method('check')->willReturn(true);
        $aclDouble->expects($this->exactly(3))->method('checkScope')->willReturn(true);

        $entityManagerDouble = $this->getMockBuilder(EntityManager::class)
                                    ->setMethods(['getEntity'])
                                    ->disableOriginalConstructor()->getMock();
        $entityManagerDouble->method('getEntity')->will($this->returnCallback(function ($entityType) {
            $classname = '\\Espo\\Modules\\Crm\\Entities\\'.$entityType;
            return new $classname;
        }));

        $leadServiceDouble = $this->getMockBuilder(Lead::class)
                        ->setMethods(['getAcl'])
                        ->setMethodsExcept(['inject', 'getConvertAttributes', 'getMetadata', 'getEntityManager'])
                        ->getMock();
        $leadServiceDouble->expects($this->exactly(4))->method('getAcl')->will($this->returnValue($aclDouble));
        $leadServiceDouble->method('getEntity')->with($id)->will($this->returnValue($entity));

        $leadServiceDouble->inject('container', $this->getContainer());
        $leadServiceDouble->inject('config', $this->getContainer()->get('config'));
        $leadServiceDouble->inject('metadata', $this->getContainer()->get('metadata'));
        $leadServiceDouble->inject('entityManager', $entityManagerDouble);


        $leadServiceDouble->getConvertAttributes($id);
    }
}
