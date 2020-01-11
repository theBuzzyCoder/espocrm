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

namespace tests\unit\Espo\Core\Formula;

use \Espo\ORM\Entity;

class EvaluatorTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->evaluator = new \Espo\Core\Formula\Evaluator();
    }

    protected function tearDown() : void
    {
        $this->evaluator = null;
    }

    function testEvaluateMathExpression1()
    {
        $expression = "5 - (2 + 1)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(2, $actual);
    }

    function testEvaluateList1()
    {
        $expression = "list()";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals([], $actual);
    }

    function testEvaluateList2()
    {
        $expression = "list(1)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals([1], $actual);
    }

    function testEvaluateEmpty()
    {
        $expression = '';
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(null, $actual);
    }

    function testNotEqualsNull()
    {
        $expression = "5 != null";
        $actual = $this->evaluator->process($expression);
        $this->assertTrue($actual);
    }

    function testSummationOfMultipleIfThenElse()
    {
        $expression = "
            ifThenElse(
                true,
                (1 + 0 + 1) - 1 * 0.5,
                0
            )
            +
            ifThenElse(
                true,
                (1 - 0) * 0.5,
                0
            )
            +
            ifThenElse(
                true,
                (1 - 0) * 0.5,
                0
            )
        ";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(2.5, $actual);
    }

    function testStringPad()
    {
        $expression = "string\\pad('1', 3, '0')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('100', $actual);

        $expression = "string\\pad('1', 3)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('1  ', $actual);

        $expression = "string\\pad('11', 4, '0', 'left')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('0011', $actual);

        $expression = "string\\pad('11', 4, '0', 'both')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('0110', $actual);
    }
}
