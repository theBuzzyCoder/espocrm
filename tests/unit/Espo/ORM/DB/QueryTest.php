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

use Espo\ORM\DB\Query\Mysql as Query;
use Espo\ORM\EntityFactory;

use Espo\Entities\Post;
use Espo\Entities\Comment;
use Espo\Entities\Tag;
use Espo\Entities\Note;

require_once 'tests/unit/testData/DB/Entities.php';
require_once 'tests/unit/testData/DB/MockPDO.php';
require_once 'tests/unit/testData/DB/MockDBResult.php';

class QueryTest extends \PHPUnit\Framework\TestCase
{
    protected $query;

    protected $pdo;

    protected $entityFactory;

    protected function setUp() : void
    {
        $this->pdo = $this->createMock('MockPDO');
        $this->pdo
                ->expects($this->any())
                ->method('quote')
                ->will($this->returnCallback(function() {
                    $args = func_get_args();
                    return "'" . $args[0] . "'";
                }));


        $this->entityFactory = $this->getMockBuilder('\\Espo\\ORM\\EntityFactory')->disableOriginalConstructor()->getMock();
        $this->entityFactory->expects($this->any())
                            ->method('create')
                            ->will($this->returnCallback(function() {
                                $args = func_get_args();
                                $className = "\\Espo\\Entities\\" . $args[0];
                                  return new $className();
                            }));

        $this->metadata = $this->getMockBuilder('\\Espo\\ORM\\Metadata')->disableOriginalConstructor()->getMock();

        $this->query = new Query($this->pdo, $this->entityFactory, $this->metadata);

        $this->post = new \Espo\Entities\Post();
        $this->comment = new \Espo\Entities\Comment();
        $this->tag = new \Espo\Entities\Tag();
        $this->note = new \Espo\Entities\Note();

        $this->contact = new \Espo\Entities\Contact();
        $this->account = new \Espo\Entities\Account();
    }

    protected function tearDown() : void
    {
        unset($this->query);
        unset($this->pdo);
        unset($this->post);
        unset($this->tag);
        unset($this->note);
        unset($this->contact);
        unset($this->account);
    }

    public function testSelectAllColumns()
    {
        $sql = $this->query->createSelectQuery('Account', array(
            'orderBy' => 'name',
            'order' => 'ASC',
            'offset' => 10,
            'limit' => 20
        ));

        $expectedSql =
            "SELECT account.id AS `id`, account.name AS `name`, account.deleted AS `deleted` FROM `account` " .
            "WHERE account.deleted = '0' ORDER BY account.name ASC LIMIT 10, 20";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectSkipTextColumns()
    {
        $sql = $this->query->createSelectQuery('Article', array(
            'orderBy' => 'name',
            'order' => 'ASC',
            'offset' => 10,
            'limit' => 20,
            'skipTextColumns' => true
        ));

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name`, article.deleted AS `deleted` FROM `article` " .
            "WHERE article.deleted = '0' ORDER BY article.name ASC LIMIT 10, 20";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithBelongsToJoin()
    {
        $sql = $this->query->createSelectQuery('Comment', array(

        ));

        $expectedSql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `postName`, comment.name AS `name`, comment.deleted AS `deleted` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSpecifiedColumns()
    {
        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'name')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
            "WHERE comment.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'name', 'postName')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name`, post.name AS `postName` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'name', 'postName'),
            'leftJoins' => array('post')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name`, post.name AS `postName` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'name'),
            'leftJoins' => array('post')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, comment.name AS `name` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWithSpecifiedFunction()
    {
        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'postId', 'post.name', 'COUNT:id'),
            'leftJoins' => array('post'),
            'groupBy' => array('postId', 'post.name')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, comment.post_id AS `postId`, post.name AS `post.name`, COUNT(comment.id) AS `COUNT:id` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY comment.post_id, post.name";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('id', 'COUNT:id', 'MONTH:post.createdAt'),
            'leftJoins' => array('post'),
            'groupBy' => array('MONTH:post.createdAt')
        ));
        $expectedSql =
            "SELECT comment.id AS `id`, COUNT(comment.id) AS `COUNT:id`, DATE_FORMAT(post.created_at, '%Y-%m') AS `MONTH:post.createdAt` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY DATE_FORMAT(post.created_at, '%Y-%m')";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithJoinChildren()
    {
        $sql = $this->query->createSelectQuery('Post', array(
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft']]
        ));

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' AND notesLeft.deleted = '0' " .
            "WHERE post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions1()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft', ['notesLeft.name!=' => null]]]
        ]);

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' AND notesLeft.deleted = '0' AND notesLeft.name IS NOT NULL " .
            "WHERE post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions2()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id', 'name'],
            'leftJoins' => [['notes', 'notesLeft', ['notesLeft.name=:' => 'post.name']]]
        ]);

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note` AS `notesLeft` ON post.id = notesLeft.parent_id AND notesLeft.parent_type = 'Post' AND notesLeft.deleted = '0' AND notesLeft.name = post.name " .
            "WHERE post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions3()
    {
        $sql = $this->query->createSelectQuery('Note', [
            'select' => ['id'],
            'leftJoins' => [['post', 'post', [
                'OR' => [
                    ['name' => 'test'],
                    ['post.name' => null],
                ]
            ]]],
            'withDeleted' => true,
        ]);

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ON (post.name = 'test' OR post.name IS NULL)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinConditions4()
    {
        $sql = $this->query->createSelectQuery('Note', [
            'select' => ['id'],
            'leftJoins' => [['post', 'post', [
                'name' => null,
                'OR' => [
                    ['name' => 'test'],
                    ['post.name' => null],
                ]
            ]]],
            'withDeleted' => true,
        ]);

        $expectedSql = "SELECT note.id AS `id` FROM `note` LEFT JOIN `post` AS `post` ON post.name IS NULL AND (post.name = 'test' OR post.name IS NULL)";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testJoinTable()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id', 'name'],
            'leftJoins' => [['NoteTable', 'note', ['note.parentId=:' => 'post.id', 'note.parentType' => 'Post']]]
        ]);

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "LEFT JOIN `note_table` AS `note` ON note.parent_id = post.id AND note.parent_type = 'Post' " .
            "WHERE post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }


    public function testJoinOnlyMiddle()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id'],
            'leftJoins' => [['tags', null, null, ['onlyMiddle' => true]]]
        ]);

        $expectedSql =
            "SELECT post.id AS `id` FROM `post` " .
            "LEFT JOIN `post_tag` AS `tagsMiddle` ON post.id = tagsMiddle.post_id AND tagsMiddle.deleted = '0' " .
            "WHERE post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereNotValue1()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'name!=:' => 'post.id'
            ]
        ]);

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "WHERE post.name <> post.id AND post.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhereNotValue2()
    {
        $sql = $this->query->createSelectQuery('Post', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'name:' => null
            ],
            'withDeleted' => true
        ]);

        $expectedSql =
            "SELECT post.id AS `id`, post.name AS `name` FROM `post` " .
            "WHERE post.name";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testSelectWithSubquery()
    {
        $sql = $this->query->createSelectQuery('Post', array(
            'select' => ['id', 'name'],
            'whereClause' => array(
                'post.id=s' => array(
                    'entityType' => 'Post',
                    'selectParams' => array(
                        'select' => ['id'],
                        'whereClause' => array(
                            'name' => 'test'
                        )
                    )
                )
            )
        ));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` WHERE post.id IN (SELECT post.id AS `id` FROM `post` WHERE post.name = 'test' AND post.deleted = '0') AND post.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Post', array(
            'select' => ['id', 'name'],
            'whereClause' => array(
                'post.id!=s' => array(
                    'entityType' => 'Post',
                    'selectParams' => array(
                        'select' => ['id'],
                        'whereClause' => array(
                            'name' => 'test'
                        )
                    )
                )
            )
        ));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` WHERE post.id NOT IN (SELECT post.id AS `id` FROM `post` WHERE post.name = 'test' AND post.deleted = '0') AND post.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->createSelectQuery('Post', array(
            'select' => ['id', 'name'],
            'whereClause' => array(
                'NOT'=> array(
                    'name' => 'test',
                    'post.createdById' => '1'
                )
            )
        ));

        $expectedSql = "SELECT post.id AS `id`, post.name AS `name` FROM `post` WHERE post.id NOT IN (SELECT post.id AS `id` FROM `post` WHERE post.name = 'test' AND post.created_by_id = '1' AND post.deleted = '0') AND post.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testGroupBy()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['COUNT:id', 'QUARTER:comment.createdAt'],
            'groupBy' => ['QUARTER:comment.createdAt']
        ]);
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CONCAT(YEAR(comment.created_at), '_', QUARTER(comment.created_at)) AS `QUARTER:comment.createdAt` FROM `comment` " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY CONCAT(YEAR(comment.created_at), '_', QUARTER(comment.created_at))";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['COUNT:id', 'YEAR_5:comment.createdAt'],
            'groupBy' => ['YEAR_5:comment.createdAt']
        ]);
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CASE WHEN MONTH(comment.created_at) >= 6 THEN YEAR(comment.created_at) ELSE YEAR(comment.created_at) - 1 END AS `YEAR_5:comment.createdAt` FROM `comment` " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY CASE WHEN MONTH(comment.created_at) >= 6 THEN YEAR(comment.created_at) ELSE YEAR(comment.created_at) - 1 END";
        $this->assertEquals($expectedSql, $sql);


        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['COUNT:id', 'QUARTER_4:comment.createdAt'],
            'groupBy' => ['QUARTER_4:comment.createdAt']
        ]);

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, CASE WHEN MONTH(comment.created_at) >= 5 THEN CONCAT(YEAR(comment.created_at), '_', FLOOR((MONTH(comment.created_at) - 5) / 3) + 1) ELSE CONCAT(YEAR(comment.created_at) - 1, '_', CEIL((MONTH(comment.created_at) + 7) / 3)) END AS `QUARTER_4:comment.createdAt` FROM `comment` " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY CASE WHEN MONTH(comment.created_at) >= 5 THEN CONCAT(YEAR(comment.created_at), '_', FLOOR((MONTH(comment.created_at) - 5) / 3) + 1) ELSE CONCAT(YEAR(comment.created_at) - 1, '_', CEIL((MONTH(comment.created_at) + 7) / 3)) END";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testOrderBy()
    {
        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('COUNT:id', 'YEAR:post.createdAt'),
            'leftJoins' => array('post'),
            'groupBy' => array('YEAR:post.createdAt'),
            'orderBy' => 2
        ));
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY YEAR(post.created_at) ".
            "ORDER BY 2 ASC";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('COUNT:id', 'post.name'),
            'leftJoins' => array('post'),
            'groupBy' => array('post.name'),
            'orderBy' => 'LIST:post.name:Test,Hello',
        ));

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, post.name AS `post.name` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY post.name ".
            "ORDER BY FIELD(post.name, 'Hello', 'Test') DESC";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('COUNT:id', 'YEAR:post.createdAt', 'post.name'),
            'leftJoins' => array('post'),
            'groupBy' => array('YEAR:post.createdAt', 'post.name'),
            'orderBy' => array(
                array(2, 'DESC'),
                array('LIST:post.name:Test,Hello')
            )
        ));
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:id`, YEAR(post.created_at) AS `YEAR:post.createdAt`, post.name AS `post.name` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE comment.deleted = '0' " .
            "GROUP BY YEAR(post.created_at), post.name ".
            "ORDER BY 2 DESC, FIELD(post.name, 'Hello', 'Test') DESC";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testForeign()
    {
        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => array('COUNT:comment.id', 'postId', 'postName'),
            'leftJoins' => array('post'),
            'groupBy' => array('postId'),
            'whereClause' => array(
                'post.createdById' => 'id_1'
            ),
        ));
        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:comment.id`, comment.post_id AS `postId`, post.name AS `postName` FROM `comment` " .
            "LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE post.created_by_id = 'id_1' AND comment.deleted = '0' " .
            "GROUP BY comment.post_id";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testInArray()
    {
        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => ['id'],
            'whereClause' => array(
                'id' => ['id_1']
            ),
        ));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.id IN ('id_1') AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => ['id'],
            'whereClause' => array(
                'id!=' => ['id_1']
            ),
        ));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.id NOT IN ('id_1') AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => ['id'],
            'whereClause' => array(
                'id' => []
            ),
        ));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE 0 AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);

        $sql = $this->query->createSelectQuery('Comment', array(
            'select' => ['id'],
            'whereClause' => array(
                'name' => 'Test',
                'id!=' => []
            ),
        ));
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE comment.name = 'Test' AND 1 AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction1()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id'],
            'whereClause' => [
                'MONTH_NUMBER:comment.created_at' => 2
            ]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE MONTH(comment.created_at) = '2' AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction2()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id'],
            'whereClause' => [
                'WEEK_NUMBER_1:createdAt' => 2
            ]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE WEEK(comment.created_at, 3) = '2' AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction3()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id'],
            'whereClause' => [
                'MONTH_NUMBER:(comment.created_at)' => 2
            ]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE MONTH(comment.created_at) = '2' AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction4()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id'],
            'whereClause' => [
                "CONCAT:(MONTH:comment.created_at,' ',CONCAT:(comment.name,'+'))" => 'Test Hello'
            ]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id` FROM `comment` " .
            "WHERE CONCAT(DATE_FORMAT(comment.created_at, '%Y-%m'), ' ', CONCAT(comment.name, '+')) = 'Test Hello' AND comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction5()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', ['FLOOR:3.5', 'FLOOR:3.5']],
            'whereClause' => [
            ]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, FLOOR('3.5') AS `FLOOR:3.5` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction6()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', ['ROUND:3.5,1', 'ROUND:3.5,1']],
            'whereClause' => []
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, ROUND('3.5', '1') AS `ROUND:3.5,1` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction7()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', 'ROUND:3.5,1'],
            'whereClause' => []
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, ROUND('3.5', '1') AS `ROUND:3.5,1` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction8()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', ["CONCAT:(',test',\"+\",'\"', \"'\")", 'value']]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, CONCAT(',test', '+', '\"', ''') AS `value` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction9()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', ["COALESCE:(name,FALSE,true,null)", 'value']]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, COALESCE(comment.name, FALSE, TRUE, NULL) AS `value` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction10()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', ["IF:(LIKE:(name,'%test%'),'1','0')", 'value']]
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, IF(comment.name LIKE '%test%', '1', '0') AS `value` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction11()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => [["IS_NULL:(name)", 'value1'], ["IS_NOT_NULL:(name)", 'value2']],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT comment.name IS NULL AS `value1`, comment.name IS NOT NULL AS `value2` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction12()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ["IF:(OR:('1','0'),'1',' ')"],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT IF('1' OR '0', '1', ' ') AS `IF:(OR:('1','0'),'1',' ')` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction13()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ["IN:(name,'1','0')"],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT comment.name IN ('1', '0') AS `IN:(name,'1','0')` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction14()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ["NOT:(name)"],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT NOT comment.name AS `NOT:(name)` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction15()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ["MUL:(2,2.5,SUB:(3,1))"],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT ('2' * '2.5' * ('3' - '1')) AS `MUL:(2,2.5,SUB:(3,1))` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction16()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ["NOW:()"],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT NOW() AS `NOW:()` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunction17()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => [["TIMESTAMPDIFF_YEAR:('2016-10-10', '2018-10-10')", 'test']],
            'withDeleted' => true
        ]);
        $expectedSql =
            "SELECT TIMESTAMPDIFF(YEAR, '2016-10-10', '2018-10-10') AS `test` FROM `comment`";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionTZ1()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', "MONTH_NUMBER:TZ:(comment.created_at,-3.5)"],
            'whereClause' => []
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, MONTH(CONVERT_TZ(comment.created_at, '+00:00', '-03:30')) AS `MONTH_NUMBER:TZ:(comment.created_at,-3.5)` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testFunctionTZ2()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id', "MONTH_NUMBER:TZ:(comment.created_at,0)"],
            'whereClause' => []
        ]);
        $expectedSql =
            "SELECT comment.id AS `id`, MONTH(CONVERT_TZ(comment.created_at, '+00:00', '+00:00')) AS `MONTH_NUMBER:TZ:(comment.created_at,0)` FROM `comment` " .
            "WHERE comment.deleted = '0'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testHaving()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['COUNT:comment.id', 'postId', 'postName'],
            'leftJoins' => ['post'],
            'groupBy' => ['postId'],
            'whereClause' => [
                'post.createdById' => 'id_1'
            ],
            'havingClause' => [
                'COUNT:comment.id>' => 1
            ]
        ]);

        $expectedSql =
            "SELECT COUNT(comment.id) AS `COUNT:comment.id`, comment.post_id AS `postId`, post.name AS `postName` " .
            "FROM `comment` LEFT JOIN `post` AS `post` ON comment.post_id = post.id " .
            "WHERE post.created_by_id = 'id_1' AND comment.deleted = '0' " .
            "GROUP BY comment.post_id " .
            "HAVING COUNT(comment.id) > '1'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testWhere1()
    {
        $sql = $this->query->createSelectQuery('Comment', [
            'select' => ['id'],
            'whereClause' => [
                'post.createdById<=' => '1'
            ],
            'withDeleted' => true
        ]);

        $expectedSql =
            "SELECT comment.id AS `id` " .
            "FROM `comment` " .
            "WHERE post.created_by_id <= '1'";
        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch1()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_BOOLEAN:name,description:test +hello',
                'id!=' => null
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.name,article.description) AGAINST ('test +hello' IN BOOLEAN MODE) AND article.id IS NOT NULL AND article.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch2()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:description:"test hello"'
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('\"test hello\"' IN NATURAL LANGUAGE MODE) AND article.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch3()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', 'MATCH_BOOLEAN:description:test'],
            'whereClause' => [
                'MATCH_BOOLEAN:description:test'
            ],
            'orderBy' => [
                [2, 'DESC']
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AS `MATCH_BOOLEAN:description:test` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AND article.deleted = '0' " .
            "ORDER BY 2 DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch4()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', ['MATCH_BOOLEAN:description:test', 'relevance']],
            'whereClause' => [
                'MATCH_BOOLEAN:description:test'
            ],
            'orderBy' => [
                [2, 'DESC']
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AS `relevance` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN BOOLEAN MODE) AND article.deleted = '0' " .
            "ORDER BY 2 DESC";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch5()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:description:test>' => 1
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN NATURAL LANGUAGE MODE) > '1' AND article.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testMatch6()
    {
        $sql = $this->query->createSelectQuery('Article', [
            'select' => ['id', 'name'],
            'whereClause' => [
                'MATCH_NATURAL_LANGUAGE:(description,test)'
            ]
        ]);

        $expectedSql =
            "SELECT article.id AS `id`, article.name AS `name` FROM `article` " .
            "WHERE MATCH (article.description) AGAINST ('test' IN NATURAL LANGUAGE MODE) AND article.deleted = '0'";

        $this->assertEquals($expectedSql, $sql);
    }

    public function testGetAllAttributesFromComplexExpression()
    {
        $expression = "CONCAT:(MONTH:comment.created_at,' ',CONCAT:(comment.name,'+'))";

        $list = $this->query::getAllAttributesFromComplexExpression($expression);

        $this->assertTrue(in_array('comment.created_at', $list));
        $this->assertTrue(in_array('comment.name', $list));
    }

    public function testGetAllAttributesFromComplexExpression1()
    {
        $expression = "test";
        $list = $this->query::getAllAttributesFromComplexExpression($expression);
        $this->assertTrue(in_array('test', $list));
    }

    public function testGetAllAttributesFromComplexExpression2()
    {
        $expression = "comment.test";
        $list = $this->query::getAllAttributesFromComplexExpression($expression);
        $this->assertTrue(in_array('comment.test', $list));
    }
}
