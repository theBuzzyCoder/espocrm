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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;

class LanguageTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $cacheFile = 'tests/unit/testData/cache/application/languages/{language}.php';

    protected $paths = [
        'corePath' => 'tests/unit/testData/Utils/I18n/Espo/Resources/i18n/{language}',
        'modulePath' => 'tests/unit/testData/Utils/I18n/Espo/Modules/{*}/Resources/i18n/{language}',
        'customPath' => 'tests/unit/testData/Utils/I18n/Espo/Custom/Resources/i18n/{language}',
    ];

    protected function setUp()
    {
        $this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();


        $this->objects['metadata'] = $this->getMockBuilder('\Espo\Core\Utils\Metadata')->disableOriginalConstructor()->getMock();
        $this->objects['metadata']->expects($this->any())
             ->method('getModuleList')
             ->will($this->returnValue(
                [
                  'Crm',
                ]
             ));

        $this->object = new \Espo\Core\Utils\Language(null, $this->objects['fileManager'], $this->objects['metadata'], false);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('cacheFile', $this->cacheFile);
        $this->reflection->setProperty('paths', $this->paths);
        $this->reflection->setProperty('currentLanguage', 'en_US');
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    public function testLanguage()
    {
        $this->assertEquals('en_US', $this->object->getLanguage());

        $originalLang = $this->object->getLanguage();
        $this->object->setLanguage('lang_TEST');
        $this->assertEquals('lang_TEST', $this->object->getLanguage());

        $this->object->setLanguage($originalLang);
    }

    public function testGetLangCacheFile()
    {
        $cacheFile = $this->cacheFile;

        $result = str_replace('{language}', 'en_US', $cacheFile);
        $this->assertEquals($result, $this->reflection->invokeMethod('getCacheFile'));

        $originalLang = $this->object->getLanguage();
        $this->object->setLanguage('lang_TEST');
        $result = str_replace('{language}', 'lang_TEST', $cacheFile);
        $this->assertEquals($result, $this->reflection->invokeMethod('getCacheFile'));

        $this->object->setLanguage($originalLang);
    }

    public function testGetData()
    {
        $result = [
            'User' => [
              'fields' => [
                'name' => 'User',
                'label' => 'Core',
                'source' => 'Core',
              ],
            ],
            'Account' => [
                'fields' => [
                    'name' => 'Account',
                    'label' => 'Custom',
                    'source' => 'Crm Module',
                ],
            ],
            'Contact' => [
                'fields' => [
                    'name' => 'Contact',
                    'label' => 'Custom',
                    'source' => 'Crm Module',
                ],
            ],
            'Global' => [
                'options' => [
                    'language' => [
                      'en_US' => 'English (United States)',
                    ]
                ],
                'testHtml' => '&lt;a href=&quot;javascript: alert(1)&quot;&gt;test&lt;/a&gt;',
            ],
        ];

        $this->assertEquals($result, $this->reflection->invokeMethod('getData', []));
    }

    public function testGet()
    {
        $result = array (
            'fields' =>
            array(
                'name' => 'User',
                'label' => 'Core',
                'source' => 'Core',
            ),
        );
        $this->assertEquals($result, $this->object->get('User'));

        $result = 'User';
        $this->assertEquals($result, $this->object->get('User.fields.name'));
    }

    public function testTranslate()
    {
        $this->assertEquals('Core', $this->object->translate('label', 'fields', 'User'));

        $input = array(
            'name',
            'label',
        );
        $result = array(
            'name' => 'User',
            'label' => 'Core',
        );
        $this->assertEquals($result, $this->object->translate($input, 'fields', 'User'));
    }

    public function testTranslateTestGlobal()
    {
        $result = array(
            'en_US' => 'English (United States)',
        );
        $this->assertEquals($result, $this->object->translate('language', 'options', 'User'));
    }

    public function testTranslateOption()
    {
        $result = array(
            'en_US' => 'English (United States)',
        );
        $this->assertEquals($result, $this->object->translate('language', 'options'));
    }

    public function testTranslateOptionWithRequiredOptions()
    {
        $result = array(
            'en_US' => 'English (United States)',
            'de_DE' => 'de_DE',
        );
        $requiredOptions = array(
            'en_US',
            'de_DE',
        );

        $this->assertEquals($result, $this->object->translate('language', 'options', 'Global', $requiredOptions));
    }

    public function testTranslateArray()
    {
        $input = array(
            'name',
            'label',
        );
        $result = array(
            'name' => 'User',
            'label' => 'Core',
        );
        $this->assertEquals($result, $this->object->translate($input, 'fields', 'User'));
    }

    public function testTranslateSubLabels()
    {
        $result = 'English (United States)';
        $this->assertEquals($result, $this->object->translate('language.en_US', 'options'));
    }

    public function testSet()
    {
        $label = 'TEST';
        $this->object->set('User', 'fields', 'label', $label);
        $this->assertEquals($label, $this->object->translate('label', 'fields', 'User'));

        $result = array(
            'User' => array(
                'fields' => array(
                    'label' => 'TEST',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('changedData'));

        $label2 = 'TEST2';
        $this->object->set('User', 'fields', 'name', $label2);
        $this->assertEquals($label2, $this->object->translate('name', 'fields', 'User'));

        $result = array(
            'User' => array(
                'fields' => array(
                    'label' => 'TEST',
                    'name' => 'TEST2',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('changedData'));

        $label3 = 'TEST3';
        $this->object->set('Account', 'fields', 'name', $label3);
        $this->assertEquals($label3, $this->object->translate('name', 'fields', 'Account'));

        $result = array(
            'User' => array(
                'fields' => array(
                    'label' => 'TEST',
                    'name' => 'TEST2',
                ),
            ),
            'Account' => array(
                'fields' => array(
                    'name' => 'TEST3',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('changedData'));

        $this->object->clearChanges();

        $this->assertEquals(array(), $this->reflection->getProperty('changedData'));
        $this->assertNotEquals('TEST', $this->object->get('User', 'fields', 'label'));
    }

    public function testDelete()
    {
        $this->object->delete('User', 'fields', 'label');
        $this->assertNull($this->object->get('User.fields.label'));

        $result = array(
            'User' => array(
                'fields' => array(
                    'label',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $this->object->delete('User', 'fields', 'name');
        $this->assertNull($this->object->get('User.fields.name'));

        $result = array(
            'User' => array(
                'fields' => array(
                    'label',
                    'name',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $this->object->clearChanges();

        $this->assertNotNull($this->object->get('User.fields.label'));
        $this->assertNotNull($this->object->get('User.fields.name'));

        $this->assertEquals(array(), $this->reflection->getProperty('deletedData'));
    }

    public function testUndelete()
    {
        $this->object->delete('User', 'fields', 'label');
        $this->assertNull($this->object->get('User.fields.label'));

        $this->object->delete('User', 'fields', 'name');
        $this->assertNull($this->object->get('User.fields.name'));

        $label = 'TEST';
        $this->object->set('User', 'fields', 'label', $label);
        $this->assertEquals($label, $this->object->translate('label', 'fields', 'User'));

        $result = array(
            'User' => array(
                'fields' => array(
                    1 => 'name',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $label2 = 'TEST2';
        $this->object->set('User', 'fields', 'name', $label2);
        $this->assertEquals($label2, $this->object->translate('name', 'fields', 'User'));

        $result = array(
            'User' => array(
                'fields' => array(
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));
    }

}
