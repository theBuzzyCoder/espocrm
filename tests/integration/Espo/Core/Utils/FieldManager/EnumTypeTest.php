<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Espo\Core\Utils\FieldManager;

class EnumTypeTest extends \tests\integration\Core\BaseTestCase
{
    private $jsonFieldDefs = '{
        "type":"enum",
        "required":true,
        "dynamicLogicVisible":null,
        "dynamicLogicRequired":null,
        "dynamicLogicReadOnly":null,
        "dynamicLogicOptions":null,
        "name":"testEnum",
        "label":"TestEnum",
        "audited":true,
        "options":["option1","option2","option3"],
        "translatedOptions":{"option1":"option1","option2":"option2","option3":"option3"},
        "default":"option2",
        "tooltipText":"",
        "isPersonalData":false,
        "isSorted":false,
        "readOnly":false,
        "tooltip":false
    }';

    public function testCreate()
    {
        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldDefs = get_object_vars(json_decode($this->jsonFieldDefs));

        $fieldManager->create('Account', 'testEnum', $fieldDefs);
        $this->getContainer()->get('dataManager')->rebuild('Account');

        $app = $this->createApplication();

        $metadata = $app->getContainer()->get('metadata');
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.testEnum');

        $this->assertEquals('enum', $savedFieldDefs['type']);
        $this->assertEquals('option2', $savedFieldDefs['default']);
        $this->assertTrue($savedFieldDefs['required']);
        $this->assertTrue($savedFieldDefs['isCustom']);
        $this->assertTrue($savedFieldDefs['audited']);

        $entityManager = $app->getContainer()->get('entityManager');
        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'Test',
            'testEnum' => 'option1'
        ]);
        $savedId = $entityManager->saveEntity($account);

        $account = $entityManager->getEntity('Account', $savedId);
        $this->assertEquals('option1', $account->get('testEnum'));
    }

    public function testUpdate()
    {
        $this->testCreate();

        $app = $this->createApplication();
        $fieldManager = $app->getContainer()->get('fieldManager');
        $fieldDefs = get_object_vars(json_decode($this->jsonFieldDefs));
        $fieldDefs['required'] = false;
        $fieldDefs['default'] = 'option3';
        $fieldDefs['readOnly'] = true;

        $fieldManager->update('Account', 'testEnum', $fieldDefs);
        $this->getContainer()->get('dataManager')->rebuild('Account');

        $app = $this->createApplication();

        $metadata = $app->getContainer()->get('metadata');
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.testEnum');

        $this->assertFalse($savedFieldDefs['required']);
        $this->assertEquals('option3', $savedFieldDefs['default']);
        $this->assertTrue($savedFieldDefs['audited']);
        $this->assertTrue($savedFieldDefs['readOnly']);

        $entityManager = $app->getContainer()->get('entityManager');
        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'New Test',
        ]);
        $savedId = $entityManager->saveEntity($account);

        $account = $entityManager->getEntity('Account', $savedId);
        $this->assertEquals('option3', $account->get('testEnum'));
    }
}
