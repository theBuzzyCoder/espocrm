<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\integration\Espo\User;

class AclTest extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'User/Login.php';

    protected $userName = 'admin';
    protected $password = '1';

    private function setFieldsDefs($app, $entityType, $data)
    {
        $metadata = $app->getContainer()->get('metadata');
        $metadata->set('entityDefs', $entityType, [
            'fields' => $data
        ]);
        $metadata->save();
    }

    public function testUserAccess()
    {
        $this->expectException('\\Espo\\Core\\Exceptions\\Forbidden');

        $this->createUser('tester', array(
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' => array(
                'Account' => false,
                'Call' =>
                array (
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'team',
                    'delete' => 'no'
                )
            ),
            'fieldData' => array(
                'Call' => array(
                    'direction' => array(
                        'read' => 'yes',
                        'edit' => 'no'
                    )
                )
            )
        ));

        $this->auth('tester');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = array();
        $data = '{"name":"Test Account"}';
        $request = $this->createRequest('POST', $params, array('CONTENT_TYPE' => 'application/json'));
        $result = $controllerManager->process('Account', 'create', $params, $data, $request);
    }

    public function testPortalUserAccess()
    {
        $this->expectException('\\Espo\\Core\\Exceptions\\Forbidden');

        $newUser = $this->createUser(array(
                'userName' => 'tester',
                'lastName' => 'tester',
                'portalsIds' => array(
                    'testPortalId'
                )
            ), array(
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' => array (
                'Account' => false,
            ),
            'fieldData' => array (
                'Call' => array (
                    'direction' => array (
                        'read' => 'yes',
                        'edit' => 'no'
                    )
                )
            )
        ), true);

        $this->auth('tester', null, 'testPortalId');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = array();
        $data = '{"name":"Test Account"}';
        $request = $this->createRequest('POST', $params, array('CONTENT_TYPE' => 'application/json'));
        $result = $controllerManager->process('Account', 'create', $params, $data, $request);
    }

    public function testUserAccessEditOwn1()
    {
        $user1 = $this->createUser('test-1', [
            "id" => "test-1",
            'data' => [
                'User' => [
                    'read' => 'all',
                    'edit' => 'own'
                ]
            ]
        ]);

        $this->createUser('test-2', []);

        $this->auth('test-1');
        $app = $this->createApplication();
        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);

        $this->assertTrue(is_string($result));

        $params = [
            'id' => $user2->id
        ];
        $data = [
            'id' => $user2->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);

        $result = null;
        try {
            $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        } catch (\Exception $e) {};

        $this->assertNull($result);


        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'type' => 'admin',
            'teamsIds' => ['id']
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        $resultData = json_decode($result);

        $this->assertTrue(!property_exists($resultData, 'type') || $resultData->type !== 'admin');
        $this->assertTrue(
            !property_exists($resultData, 'teamsIds') || !is_array($resultData->teamsIds) || !in_array('id', $resultData->teamsIds)
        );
    }

    public function testUserAccessEditOwn2()
    {
        $user1 = $this->createUser('test-1', [
            "id" => "test-1",
            'data' => [
                'User' => [
                    'read' => 'all',
                    'edit' => 'no'
                ]
            ]
        ]);

        $this->auth('test-1');
        $app = $this->createApplication();
        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);

        $result = null;
        try {
            $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        } catch (\Exception $e) {};

        $this->assertNull($result);
    }

    protected function prepareTestUser()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $team = $entityManager->getEntity('Team');
        $team->set('id', 'testTeamId');
        $entityManager->saveEntity($team);

        $team = $entityManager->getEntity('Team');
        $team->set('id', 'testOtherTeamId');
        $entityManager->saveEntity($team);

        $this->createUser(
            [
                'id' => 'testUserId',
                'userName' => 'test',
                'lastName' => 'test',
                'teamsIds' => ['testTeamId']
            ],
            [
                'assignmentPermission' => 'team',
                'data' => [
                    'Account' => false,
                    'Lead' => [
                        'create' => 'no',
                        'read' => 'own',
                        'edit' => 'own',
                        'delete' => 'no'
                    ],
                    'Meeting' => [
                        'create' => 'yes',
                        'read' => 'team',
                        'edit' => 'own',
                        'delete' => 'own'
                    ]
                ]
            ]
        );
    }

    public function testUserAccessCreateNo1()
    {
        $this->prepareTestUser();
        $this->auth('test');
        $app = $this->createApplication();

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service = $app->getContainer()->get('serviceFactory')->create('Account');

        $e = $service->createEntity((object)['name' => 'Test']);
    }

    public function testUserAccessCreateNo2()
    {
        $this->prepareTestUser();
        $this->auth('test');
        $app = $this->createApplication();

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service = $app->getContainer()->get('serviceFactory')->create('Lead');

        $e = $service->createEntity((object)['lastName' => 'Test']);
    }

    public function testUserAccessAclStrictCreateNo()
    {
        $app = $this->createApplication();
        $config = $app->getContainer()->get('config');
        $config->set('aclStrictMode', true);
        $config->save();

        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication(true);

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service = $app->getContainer()->get('serviceFactory')->create('Case');

        $e = $service->createEntity((object)['name' => 'Test']);
    }

    public function testUserAccessAclStrictCreateYes()
    {
        $app = $this->createApplication();
        $config = $app->getContainer()->get('config');
        $config->set('aclStrictMode', true);
        $config->save();

        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication(true);

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $e = $service->createEntity((object) [
            'name' => 'Test',
            'assignedUserId' => 'testUserId',
            'dateStart' => '2019-01-01 00:00:00',
            'dateEnd' => '2019-01-01 00:01:00',
        ]);

        $this->assertNotNull($e);
    }

    public function testUserAccessCreateAssignedPermissionNo1()
    {
        $this->prepareTestUser();

        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Meeting', [
            'assignedUser' => [
                'required' => false
            ]
        ]);

        $this->auth('test');
        $app = $this->createApplication();

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service->createEntity((object) [
            'name' => 'Test',
            'dateStart' => '2019-01-01 00:00:00',
            'dateEnd' => '2019-01-01 00:01:00',
        ]);
    }

    public function testUserAccessCreateAssignedPermissionNo2()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service->createEntity((object)[
            'name' => 'Test',
            'assignedUserId' => 'testUserId',
            'teamsIds' => ['testOtherTeamId'],
            'dateStart' => '2019-01-01 00:00:00',
            'dateEnd' => '2019-01-01 00:01:00',
        ]);
    }

    public function testUserAccessCreateAssignedPermissionYes()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $e = $service->createEntity((object)[
            'name' => 'Test',
            'assignedUserId' => 'testUserId',
            'teamsIds' => ['testTeamId'],
            'dateStart' => '2019-01-01 00:00:00',
            'dateEnd' => '2019-01-01 00:01:00',
        ]);

        $this->assertNotNull($e);
    }

    public function testUserAccessReadNo1()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $lead = $entityManager->getEntity('Lead');
        $lead->set([
            'id' => 'testLeadId'
        ]);
        $entityManager->saveEntity($lead);

        $service = $app->getContainer()->get('serviceFactory')->create('Lead');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service->getEntity('testLeadId');
    }

    public function testUserAccessReadNo2()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $meeting = $entityManager->getEntity('Meeting');
        $meeting->set([
            'id' => 'testMeetingId',
            'teamsIds' => ['testOtherTeamId']
        ]);
        $entityManager->saveEntity($meeting);

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service->getEntity('testMeetingId');
    }

    public function testUserAccessReadYes1()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $lead = $entityManager->getEntity('Lead');
        $lead->set([
            'id' => 'testLeadId',
            'assignedUserId' => 'testUserId'
        ]);
        $entityManager->saveEntity($lead);

        $service = $app->getContainer()->get('serviceFactory')->create('Lead');

        $e = $service->getEntity('testLeadId');

        $this->assertNotNull($e);
    }

    public function testUserAccessReadYes2()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $meeting = $entityManager->getEntity('Meeting');
        $meeting->set([
            'id' => 'testMeetingId',
            'teamsIds' => ['testTeamId']
        ]);
        $entityManager->saveEntity($meeting);

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $e = $service->getEntity('testMeetingId');

        $this->assertNotNull($e);
    }

    public function testUserAccessEditNo1()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $meeting = $entityManager->createEntity('Meeting', [
            'id' => 'testMeetingId',
            'teamsIds' => ['testTeamId']
        ]);

        $service = $app->getContainer()->get('serviceFactory')->create('Meeting');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $service->updateEntity('testMeetingId', (object) []);
    }

    public function testUserAccessSearchByInternalField()
    {
        $this->prepareTestUser();

        $this->auth('test');
        $app = $this->createApplication();

        $service = $app->getContainer()->get('serviceFactory')->create('User');

        $this->expectException(\Espo\Core\Exceptions\Forbidden::class);

        $e = $service->find([
            'where' => [
                [
                    'type' => 'isNull',
                    'attribute' => 'password',
                ]
            ]
        ]);
    }
}
