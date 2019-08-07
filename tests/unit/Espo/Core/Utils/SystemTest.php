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

use Espo\Core\Utils\Util;
use Espo\Core\Utils\System;

class SystemTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $reflection;


    protected function setUp()
    {
        $this->object = new \Espo\Core\Utils\System();
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    public function testGetServerType()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.17 (Ubuntu)';
        $this->assertEquals( 'apache', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'Apache';
        $this->assertEquals( 'apache', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.5.12';
        $this->assertEquals( 'nginx', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/8.0';
        $this->assertEquals( 'microsoft-iis', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'apache/2.4.10 (win32) openssl/1.0.1i php';
        $this->assertEquals( 'apache', $this->object->getServerType());
    }

    public function testGetOS()
    {
        $possibleValues = array(
            'windows',
            'mac',
            'linux',
        );

        $this->assertTrue( in_array($this->object->getOS(), $possibleValues));
    }

    public function testGetRootDir()
    {
        $rootDir = dirname(__FILE__);
        $rootDir = str_replace(Util::fixPath('/tests/unit/Espo/Core/Utils'),'',$rootDir);

        $this->assertEquals($rootDir, $this->object->getRootDir());
    }

    public function testGetPhpBin()
    {
        $phpBin = @exec('which php');

        if (isset($phpBin)) {
            $this->assertEquals($phpBin, $this->object->getPhpBin());
        }
    }

    public function testGetPhpVersion()
    {
        $this->assertTrue( (bool) preg_match('/^[0-9\.]+$/', System::getPhpVersion()) );
    }
}