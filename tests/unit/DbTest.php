<?php

namespace WeiTest;

use PDO;

/**
 * @property \Wei\Db db
 * @method \Wei\Record db($table = null)
 */
class DbTest extends TestCase
{
    protected function createTable()
    {
        $db = $this->db;
        $db->query("CREATE TABLE prefix_member_group (id INTEGER NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))");
        $db->query("CREATE TABLE prefix_member (id INTEGER NOT NULL, group_id INTEGER NOT NULL, name VARCHAR(50) NOT NULL, address VARCHAR(256) NOT NULL, PRIMARY KEY(id))");
        $db->query("CREATE TABLE prefix_post (id INTEGER NOT NULL, member_id INTEGER NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))");
        $db->query("CREATE TABLE prefix_tag (id INTEGER NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))");
        $db->query("CREATE TABLE prefix_post_tag (post_id INTEGER NOT NULL, tag_id INTEGER NOT NULL)");
    }

    protected function dropTable()
    {
        $db = $this->db;
        $db->query('DROP TABLE IF EXISTS prefix_member_group');
        $db->query('DROP TABLE IF EXISTS prefix_member');
        $db->query('DROP TABLE IF EXISTS prefix_post');
        $db->query('DROP TABLE IF EXISTS prefix_tag');
        $db->query('DROP TABLE IF EXISTS prefix_post_tag');
    }

    public function initFixtures()
    {
        $db = $this->db;

        $db->setOption('tablePrefix', 'prefix_');

        $this->dropTable();
        $this->createTable();

        $db->insert('member_group', array(
            'id' => '1',
            'name' => 'vip'
        ));

        $db->insert('member', array(
            'group_id' => '1',
            'name' => 'twin',
            'address' => 'test'
        ));

        $db->insert('member', array(
            'group_id' => '1',
            'name' => 'test',
            'address' => 'test'
        ));

        $db->insert('post', array(
            'member_id' => '1',
            'name' => 'my first post',
        ));

        $db->insert('post', array(
            'member_id' => '1',
            'name' => 'my second post',
        ));

        $db->insert('tag', array(
            'id' => '1',
            'name' => 'database'
        ));

        $db->insert('tag', array(
            'id' => '2',
            'name' => 'PHP'
        ));

        $db->insert('post_tag', array(
            'post_id' => '1',
            'tag_id' => '1',
        ));

        $db->insert('post_tag', array(
            'post_id' => '1',
            'tag_id' => '2',
        ));

        $db->insert('post_tag', array(
            'post_id' => '2',
            'tag_id' => '1',
        ));
    }

    public function testIsConnected()
    {
        $db = $this->db;

        $db->connect();
        $this->assertTrue($db->isConnected());

        $db->close();
        $this->assertFalse($db->isConnected());
    }

    public function testGetRecord()
    {
        $this->initFixtures();
        $this->assertInstanceOf('\Wei\Record', $this->db->init('member'));
    }

    public function testRelation()
    {
        $this->initFixtures();

        $db = $this->db;

        $db->setOption('recordNamespace', 'WeiTest\Db');

        /** @var $member \WeiTest\Db\Member */
        $member = $this->db('member')->find('1');

        $this->assertInstanceOf('\Wei\Record', $member);

        $this->assertEquals('1', $member['id']);
        $this->assertEquals('twin', $member['name']);
        $this->assertEquals('test', $member['address']);
        $this->assertEquals('1', $member['group_id']);

        // Relation one-to-one
        $post = $member->getPost();

        $this->assertInstanceOf('\Wei\Record', $post);

        $this->assertEquals('1', $post['id']);
        $this->assertEquals('my first post', $post['name']);
        $this->assertEquals('1', $post['member_id']);

        // Relation belong-to
        $group = $member->getGroup();

        $this->assertInstanceOf('\Wei\Record', $group);

        $this->assertEquals('1', $group['id']);
        $this->assertEquals('vip', $group['name']);

        // Relation one-to-many
        $posts = $member->getPosts();

        $this->assertInstanceOf('\Wei\Record', $posts);

        $firstPost = $posts[0];
        $this->assertInstanceOf('\Wei\Record', $firstPost);

        $this->assertEquals('1', $firstPost['id']);
        $this->assertEquals('my first post', $firstPost['name']);
        $this->assertEquals('1', $firstPost['member_id']);
    }

    public function testSet()
    {
        $this->initFixtures();

        $member = $this->db('member')->find('1');

        $this->assertEquals('1', $member['id']);

        $member['id'] = 2;

        $this->assertEquals('2', $member['id']);
    }

    public function testGetRelation()
    {
        $this->initFixtures();

        $member = $this->db('member')->find('1');

        $post = $member->post = $this->db->find('post', array('member_id' => $member['id']));

        $this->assertInstanceOf('\Wei\Record', $post);

        $this->assertEquals('1', $post['id']);
        $this->assertEquals('my first post', $post['name']);
        $this->assertEquals('1', $post['member_id']);
    }

    public function testUpdate()
    {
        $this->initFixtures();

        $this->db->update('member', array('name' => 'hello'), array('id' => '1'));

        $member = $this->db->find('member', '1');

        $this->assertEquals('hello', $member['name']);
    }

    public function testDelete()
    {
        $this->initFixtures();

        $this->db->delete('member', array('id' => '1'));

        $member = $this->db->find('member', 1);

        $this->assertFalse($member);
    }

    public function testFind()
    {
        $this->initFixtures();

        $member = $this->db->find('member', '1');

        $this->assertEquals('1', $member['id']);
    }

    public function testFindOrInit()
    {
        $this->initFixtures();

        $member = $this->db->find('member', '3');
        $this->assertFalse($member);

        // Not found and create new object
        $member = $this->db->findOrInit('member', '3', array(
            'name' => 'name'
        ));
        $this->assertEquals('name', $member['name']);
        $this->assertEquals('3', $member['id']);

        // Found
        $member = $this->db->findOrInit('member', '2');

        $this->assertEquals('2', $member['id']);

        $member = $this->db->findOrInit('member', '3', array(
            'id' => '1', // Would be overwrite
            'name' => 'twin'
        ));

        $this->assertNotEquals('1', $member['id']);
        $this->assertEquals('3', $member['id']);

        $member = $this->db->findOrInit('member', array(
            'group_id' => '1',
            'name' => 'twin2',
        ));

        $this->assertEquals('1', $member['group_id']);
        $this->assertEquals('twin2', $member['name']);
    }

    public function testFindOrInitAndStatusIsNew()
    {
        $this->initFixtures();

        $member = $this->db->findOrInit('member', '3', array(
            'name' => 'name'
        ));
        $this->assertTrue($member->isNew());
        $this->assertFalse($member->isDestroyed());
    }

    public function testRecordSave()
    {
        $this->initFixtures();

        $db = $this->db;

        // Existing member
        $member = $db('member')->find(1);
        $member->address = 'address';
        $result = $member->save();

        $this->assertSame($result, $member);
        $this->assertEquals('1', $member['id']);

        // New member save with data
        $member = $db->init('member');
        $this->assertTrue($member->isNew());
        $member->fromArray(array(
            'group_id' => '1',
            'name' => 'save',
            'address' => 'save'
        ));
        $result = $member->save();

        $this->assertFalse($member->isNew());
        $this->assertSame($result, $member);
        $this->assertEquals('3', $member['id']);
        $this->assertEquals('save', $member['name']);

        // Save again
        $member->address = 'address3';
        $result = $member->save();
        $this->assertSame($result, $member);
        $this->assertEquals('3', $member['id']);
    }

    public function testRecordIsLoaded()
    {
        $this->initFixtures();

        $member = $this->db('member');

        $this->assertFalse($member->isLoaded());

        $member->find('1');

        $this->assertTrue($member->isLoaded());
    }

    public function testSelect()
    {
        $this->initFixtures();

        $data = $this->db->select('member', 1);
        $this->assertEquals('twin', $data['name']);

        // Empty array as conditions
        $data = $this->db->select('member', array());
        $this->assertArrayHasKey('name', $data);
    }

    public function testSelectWithField()
    {
        $this->initFixtures();

        $data = $this->db->select('member', 1, 'id, name');

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('group_id', $data);
    }

    public function testSelectAll()
    {
        $this->initFixtures();

        $data = $this->db->selectAll('member', array('name' => 'twin'));

        $this->assertCount(1, $data);

        $data = $this->db->selectAll('member');

        $this->assertCount(2, $data);
    }

    public function testFetch()
    {
        $this->initFixtures();

        $data = $this->db->fetch("SELECT * FROM prefix_member WHERE name = ?", 'twin');
        $this->assertInternalType('array', $data);
        $this->assertEquals('twin', $data['name']);
        $this->assertEquals("SELECT * FROM prefix_member WHERE name = ?", $this->db->getLastQuery());

        $data = $this->db->fetch("SELECT * FROM prefix_member WHERE name = ?", 'notFound');
        $this->assertFalse($data);
        $this->assertEquals("SELECT * FROM prefix_member WHERE name = ?", $this->db->getLastQuery());

        $data = $this->db->fetch("SELECT * FROM prefix_member WHERE name = :name", array('name' => 'twin'));
        $this->assertInternalType('array', $data);
        $this->assertEquals('twin', $data['name']);

        $data = $this->db->fetch("SELECT * FROM prefix_member WHERE name = :name", array(':name' => 'twin'));
        $this->assertInternalType('array', $data);
        $this->assertEquals('twin', $data['name']);
    }

    public function testFetchAll()
    {
        $this->initFixtures();

        $data = $this->db->fetchAll("SELECT * FROM prefix_member WHERE group_id = ?", '1');

        $this->assertInternalType('array', $data);
        $this->assertEquals('1', $data[0]['group_id']);
    }

    public function testQueryFetch()
    {
        $this->initFixtures();

        $data = $this->db('member')->where('id = 1')->fetch();
        $this->assertInternalType('array', $data);
        $this->assertEquals('1', $data['id']);
    }

    public function testQueryFetchAll()
    {
        $this->initFixtures();

        $data = $this->db('member')->fetchAll();

        $this->assertInternalType('array', $data);
        $this->assertEquals('1', $data[0]['group_id']);
    }

    public function testGetRecordClass()
    {
        $db = $this->db;

        $db->setOption('recordNamespace', 'WeiTest\Db');

        $this->assertEquals('WeiTest\Db\Member', $db->getRecordClass('member'));
        $this->assertEquals('WeiTest\Db\Member', $db->getRecordClass('member'));
        $this->assertEquals('WeiTest\Db\MemberGroup', $db->getRecordClass('member_group'));
        $this->assertEquals('WeiTest\Db\MemberGroup', $db->getRecordClass('memberGroup'));
        $this->assertEquals('WeiTest\Db\MemberGroup', $db->getRecordClass('member_Group'));
    }

    /**
     * @link http://edgeguides.rubyonrails.org/active_record_querying.html#conditions
     */
    public function testQuery()
    {
        $this->initFixtures();

        // Pure string conditions
        $query = $this->db('member')->where("name = 'twin'");
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE name = 'twin' LIMIT 1", $query->getSql());
        $this->assertEquals('twin', $member['name']);

        // ? conditions
        $query = $this->db('member')->where('name = ?', 'twin');
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE name = ? LIMIT 1", $query->getSql());
        $this->assertEquals('twin', $member['name']);

        $query = $this->db('member')->where('group_id = ? AND name = ?', array('1', 'twin'));
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id = ? AND name = ? LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);
        $this->assertEquals('twin', $member['name']);

        // : conditions
        $query = $this->db('member')->where('group_id = :groupId AND name = :name', array(
            'groupId' => '1',
            'name' => 'twin'
        ));
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id = :groupId AND name = :name LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);
        $this->assertEquals('twin', $member['name']);

        $query = $this->db('member')->where('group_id = :groupId AND name = :name', array(
            ':groupId' => '1',
            ':name' => 'twin'
        ));
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id = :groupId AND name = :name LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);
        $this->assertEquals('twin', $member['name']);

        // Range conditions
        $query = $this->db('member')->where('group_id BETWEEN ? AND ?', array('1', '2'));
        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id BETWEEN ? AND ?", $query->getSql());

        $member = $query->find();
        $this->assertGreaterThanOrEqual(1, $member['group_id']);
        $this->assertLessThanOrEqual(2, $member['group_id']);

        // Subset conditions
        $query = $this->db('member')->where(array('group_id' => array('1', '2')));
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id IN (?, ?) LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);

        $query = $this->db('member')->where(array(
            'id' => '1',
            'group_id' => array('1', '2')
        ));
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE id = ? AND group_id IN (?, ?) LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['id']);

        // Overwrite where
        $query = $this
            ->db('member')
            ->where('id = :id')
            ->where('group_id = :groupId')
            ->setParameter('groupId', 1);
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member WHERE group_id = :groupId LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);

        // Where with empty content
        $query = $this->db('member')->where(false);
        $this->assertEquals("SELECT * FROM prefix_member", $query->getSql());

        // Order
        $query = $this->db('member')->orderBy('id', 'ASC');
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member ORDER BY id ASC LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['id']);

        // Add order
        $query = $this->db('member')->orderBy('id', 'ASC')->addOrderBy('group_id', 'ASC');
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member ORDER BY id ASC, group_id ASC LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['id']);

        // Select
        $query = $this->db('member')->select('id, group_id');
        $member = $query->fetch();

        $this->assertEquals("SELECT id, group_id FROM prefix_member LIMIT 1", $query->getSql());
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayNotHasKey('name', $member);

        // Add select
        $query = $this->db('member')->select('id')->addSelect('group_id');
        $member = $query->fetch();

        $this->assertEquals("SELECT id, group_id FROM prefix_member LIMIT 1", $query->getSql());
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayNotHasKey('name', $member);

        // Distinct
        $query = $this->db('member')->select('DISTINCT group_id');
        $member = $query->find();

        $this->assertEquals("SELECT DISTINCT group_id FROM prefix_member LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);

        // Limit
        $query = $this->db('member')->limit(2);
        $member = $query->findAll();

        $this->assertEquals("SELECT * FROM prefix_member LIMIT 2", $query->getSql());
        $this->assertCount(2, $member);

        // Offset
        $query = $this->db('member')->limit(1)->offset(1);
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member LIMIT 1 OFFSET 1", $query->getSql());
        $this->assertEquals(2, $member['id']);

        // Page
        $query = $this->db('member')->page(3);
        $this->assertEquals("SELECT * FROM prefix_member LIMIT 10 OFFSET 20", $query->getSql());

        // Mixed limit and page
        $query = $this->db('member')->limit(3)->page(3);
        $this->assertEquals("SELECT * FROM prefix_member LIMIT 3 OFFSET 6", $query->getSql());

        // Group by
        $query = $this->db('member')->groupBy('id, group_id');
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member GROUP BY id, group_id LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);

        // Having
        $query = $this->db('member')->groupBy('id, group_id')->having('group_id >= ?', '1');
        $member = $query->find();

        $this->assertEquals("SELECT * FROM prefix_member GROUP BY id, group_id HAVING group_id >= ? LIMIT 1", $query->getSql());
        $this->assertEquals('1', $member['group_id']);

        // Join
        $query = $this
            ->db('member')
            ->select('prefix_member.*, prefix_member_group.name AS group_name')
            ->leftJoin('prefix_member_group', 'prefix_member_group.id = prefix_member.group_id');
        $member = $query->fetch();

        $this->assertEquals("SELECT prefix_member.*, prefix_member_group.name AS group_name FROM prefix_member LEFT JOIN prefix_member_group ON prefix_member_group.id = prefix_member.group_id LIMIT 1", $query->getSql());
        $this->assertArrayHasKey('group_name', $member);

        // Join with table alias
        $query = $this
            ->db('member u')
            ->rightJoin('prefix_member_group g', 'g.id = u.group_id');

        $this->assertEquals("SELECT * FROM prefix_member u RIGHT JOIN prefix_member_group g ON g.id = u.group_id", $query->getSql());
    }

    public function testIndexBy()
    {
        $this->initFixtures();

        $members = $this->db('member')
            ->indexBy('name')
            ->fetchAll();

        $this->assertArrayHasKey('twin', $members);
        $this->assertArrayHasKey('test', $members);

        $members = $this->db('member')
            ->indexBy('name')
            ->findAll();

        $this->assertInstanceOf('\Wei\Record', $members['twin']);
        $this->assertInstanceOf('\Wei\Record', $members['test']);

        $members = $members->toArray();

        $this->assertArrayHasKey('twin', $members);
        $this->assertArrayHasKey('test', $members);
    }

    public function testFixUndefinedOffset0WhenFetchEmptyData()
    {
        $this->initFixtures();

        $emptyMembers = $this->db('member')->where(array('group_id' => '3'))->indexBy('id')->fetchAll();
        $this->assertEmpty($emptyMembers);
    }

    public function testIndexByException()
    {
        $this->initFixtures();

        $this->setExpectedException('RuntimeException', 'Index field "test" not found in fetched data');
        $members = $this->db('member')
            ->indexBy('test')
            ->fetchAll();
    }

    public function testRealTimeIndexBy()
    {
        $this->initFixtures();

        $members = $this->db('member')->findAll();

        $members = $members->indexBy('name')->toArray();

        $this->assertArrayHasKey('twin', $members);
        $this->assertArrayHasKey('test', $members);
    }

    public function testIndexByExceptionAfterLoaded()
    {
        $this->initFixtures();

        $members = $this->db('member')->findAll();

        $this->setExpectedException('RuntimeException', 'Index field "test" not found in fetched data');

        $members->indexBy('test');
    }

    public function testQueryUpdate()
    {
        $this->initFixtures();

        $member = $this->db('member')->where('id = 1');
        $result = $member->update("name = 'twin2'");

        $this->assertGreaterThan(0, $result);
        $this->assertEquals("UPDATE prefix_member SET name = 'twin2' WHERE id = 1", $member->getSql());

        $member = $this->db->find('member', 1);
        $this->assertEquals(1, $result);
        $this->assertEquals('twin2', $member['name']);
    }

    public function testBindValue()
    {
        $this->initFixtures();

        // Not array parameter
        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = ?", 1, PDO::PARAM_INT);

        $this->assertEquals('1', $member['id']);

        // Array parameter
        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = ?", array(1), array(PDO::PARAM_INT));

        $this->assertEquals('1', $member['id']);

        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = ? AND group_id = ?", array(1, 1), array(
            PDO::PARAM_INT // (no parameter type for second placeholder)
        ));

        $this->assertEquals('1', $member['id']);
        $this->assertEquals('1', $member['group_id']);

        // Name parameter
        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = :id", array(
            'id' => 1
        ), array(
            'id' => PDO::PARAM_INT
        ));

        $this->assertEquals('1', $member['id']);

        // Name parameter with colon
        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = :id", array(
            'id' => 1
        ), array(
            ':id' => PDO::PARAM_INT
        ));

        $this->assertEquals('1', $member['id']);

        $member = $this->db->fetch("SELECT * FROM prefix_member WHERE id = :id", array(
            'id' => '1'
        ));

        $this->assertEquals('1', $member['id']);
    }

    public function testFetchColumn()
    {
        $this->initFixtures();

        $count = $this->db->fetchColumn("SELECT COUNT(id) FROM prefix_member");
        $this->assertEquals(2, $count);
    }

    public function testRecordNamespace()
    {
        $this->initFixtures();

        $this->db->setOption('recordNamespace', 'WeiTest\Db');

        $member = $this->db->find('member', 1);

        $this->assertEquals('WeiTest\Db\Member', $this->db->getRecordClass('member'));
        $this->assertInstanceOf('WeiTest\Db\Member', $member);
    }

    public function testCustomRecordClass()
    {
        $this->initFixtures();

        $this->db->setOption('recordClasses', array(
            'member' => 'WeiTest\Db\Member'
        ));

        $member = $this->db->find('member', 1);

        $this->assertEquals('WeiTest\Db\Member', $this->db->getRecordClass('member'));
        $this->assertInstanceOf('WeiTest\Db\Member', $member);
    }

    public function testRecordToArray()
    {
        $this->initFixtures();

        $member = $this->db->find('member', 1)->toArray();

        $this->assertInternalType('array', $member);
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayHasKey('name', $member);
        $this->assertArrayHasKey('address', $member);

        $member = $this->db->find('member', 1)->toArray(array('id', 'group_id'));
        $this->assertInternalType('array', $member);
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayNotHasKey('name', $member);
        $this->assertArrayNotHasKey('address', $member);

        $member = $this->db->find('member', 1)->toArray(array('id', 'group_id', 'notExistField'));
        $this->assertInternalType('array', $member);
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayNotHasKey('name', $member);
        $this->assertArrayNotHasKey('address', $member);

        $member = $this->db->init('member')->toArray();
        $this->assertInternalType('array', $member);
        $this->assertArrayHasKey('id', $member);
        $this->assertArrayHasKey('group_id', $member);
        $this->assertArrayHasKey('name', $member);
        $this->assertArrayHasKey('address', $member);
        $this->assertNull($member['id']);
        $this->assertNull($member['group_id']);
        $this->assertNull($member['name']);
        $this->assertNull($member['address']);

        $members = $this->db('member')->findAll()->toArray(array('id', 'group_id'));
        $this->assertInternalType('array', $members);
        $this->assertArrayHasKey(0, $members);
        $this->assertArrayHasKey('id', $members[0]);
        $this->assertArrayHasKey('group_id', $members[0]);
        $this->assertArrayNotHasKey('name', $members[0]);

        $this->db->setOption('recordClasses', array(
            'member' => 'WeiTest\Db\Member'
        ));
    }

    public function testToJson()
    {
        $this->initFixtures();
        $member = $this->db->init('member');
        $this->assertInternalType('string', $member->toJson());
    }

    public function testDestroyRecord()
    {
        $this->initFixtures();

        $member = $this->db->find('member', 1);

        $result = $member->destroy();

        $this->assertInstanceOf('\Wei\Record', $result);

        $member = $this->db->find('member', 1);

        $this->assertFalse($member);
    }

    public function testDestroyByCondition()
    {
        $this->initFixtures();

        $result = $this->db('member')->destroy(2);

        $this->assertFalse($this->db('member')->find(2));
    }

    public function testBeforeAndAfterDestroy()
    {
        $this->initFixtures();

        $member = $this->db->find('member', 1);
        $member->setOption(array(
            'beforeDestroy' => function() {
                echo 'beforeDestroy';
            },
            'afterDestroy' => function() {
                echo 'afterDestroy';
            }
        ));

        $this->expectOutputString('beforeDestroyafterDestroy');
        $member->destroy();
    }

    public function testGetTable()
    {
        $this->initFixtures();

        $member = $this->db('member')->find('1');

        $this->assertEquals('member', $member->getTable());
    }

    public function testFieldNotFound()
    {
        $this->initFixtures();

        $member = $this->db('member')->find('1');

        $this->setExpectedException('\InvalidArgumentException', 'Field "notFound" not found in record class "Wei\Record"');

        $member['notFound'];
    }

    public function testCollection()
    {
        $this->initFixtures();

        $members = $this->db->findAll('member');

        $this->assertInstanceOf('\Wei\Record', $members);

        // ToArray
        $memberArray = $members->toArray();
        $this->assertInternalType('array', $memberArray);
        foreach ($memberArray as $member) {
            $this->assertInternalType('array', $member);
        }

        // Filter
        $firstGroupMembers = $members->filter(function($member){
            if ('1' == $member['group_id']) {
                return true;
            } else {
                return false;
            }
        });

        $this->assertEquals('1', $firstGroupMembers[0]['group_id']);
        $this->assertInstanceOf('\Wei\Record', $firstGroupMembers);
        $this->assertNotSame($members, $firstGroupMembers);
    }

    public function testRecordUnset()
    {
        $this->initFixtures();

        $member = $this->db('member')->find('1');

        $this->assertEquals('twin', $member['name']);
        $this->assertEquals('1', $member['group_id']);

        unset($member['name']);
        $member->remove('group_id');

        $this->assertEquals(null, $member['name']);
        $this->assertEquals(null, $member['group_id']);
    }

    public function testErrorCodeAndInfo()
    {
        $this->db->errorCode();
        $info = $this->db->errorInfo();

        $this->assertArrayHasKey(0, $info);
        $this->assertArrayHasKey(1, $info);
        $this->assertArrayHasKey(1, $info);
    }

    public function testBeforeAndAfterQuery()
    {
        $this->initFixtures();

        $this->expectOutputRegex('/beforeQueryafterQuery/');

        $this->db->setOption(array(
            'beforeQuery' => function(){
                echo 'beforeQuery';
            },
            'afterQuery' => function(){
                echo 'afterQuery';
            }
        ));

        $this->db->find('member', 1);
    }

    public function testBeforeAndAfterQueryForUpdate()
    {
        $this->initFixtures();

        $this->expectOutputString('beforeQueryafterQuery');

        $this->db->setOption(array(
            'beforeQuery' => function(){
                echo 'beforeQuery';
            },
            'afterQuery' => function(){
                echo 'afterQuery';
            }
        ));

        $this->db->executeUpdate("UPDATE prefix_member SET name = 'twin2' WHERE id = 1");

        $this->assertEquals("UPDATE prefix_member SET name = 'twin2' WHERE id = 1", $this->db->getLastQuery());
    }

    public function testException()
    {
        $this->setExpectedException('PDOException');

        $this->db->query("SELECT * FROM noThis table");
    }

    public function testExceptionWithParams()
    {
        $this->setExpectedException('PDOException', 'An exception occurred while executing "SELECT * FROM noThis table WHERE id = ?"');

        $this->db->query("SELECT * FROM noThis table WHERE id = ?", array(1));
    }

    public function testUpdateWithoutParameters()
    {
        $this->initFixtures();

        $result = $this->db->executeUpdate("UPDATE prefix_member SET name = 'twin2' WHERE id = 1");

        $this->assertEquals(1, $result);
    }

    public function testCount()
    {
        $this->initFixtures();

        $count = $this->db('member')->count();

        $this->assertInternalType('int', $count);
        $this->assertEquals(2, $count);

        $count = $this->db('member')->select('id, name')->limit(1)->offset(2)->count();

        $this->assertInternalType('int', $count);
        $this->assertEquals(2, $count);
    }

    public function testCountBySubQuery()
    {
        $this->initFixtures();

        $count = $this->db('member')->countBySubQuery();

        $this->assertInternalType('int', $count);
        $this->assertEquals(2, $count);

        $count = $this->db('member')->select('id, name')->limit(1)->offset(2)->countBySubQuery();

        $this->assertInternalType('int', $count);
        $this->assertEquals(2, $count);
    }

    public function testCountWithCondition()
    {
        $this->initFixtures();

        $count = $this->db('member')->count(1);
        $this->assertInternalType('int', $count);
        $this->assertEquals(1, $count);

        $count = $this->db('member')->count(array('id' => 1));
        $this->assertInternalType('int', $count);
        $this->assertEquals(1, $count);
    }

    public function testParameters()
    {
        $this->initFixtures();

        $db = $this->db;

        $query = $db('member')
            ->where('id = :id AND group_id = :groupId')
            ->setParameters(array(
                'id' => 1,
                'groupId' => 1
            ), array(
                PDO::PARAM_INT,
                PDO::PARAM_INT
            ));
        $member = $query->find();

        $this->assertEquals(array(
            'id' => 1,
            'groupId' => 1
        ), $query->getParameters());

        $this->assertEquals(1, $query->getParameter('id'));
        $this->assertNull($query->getParameter('no'));

        $this->assertEquals(1, $member['id']);
        $this->assertEquals(1, $member['group_id']);

        // Set parameter
        $query->setParameter('id', 1, PDO::PARAM_STR);
        $member = $query->find();
        $this->assertEquals(1, $member['id']);

        $query->setParameter('id', 10);
        $member = $query->find();
        $this->assertFalse($member);

        $query = $this
            ->db('member')
            ->andWhere('id = ?', '1', PDO::PARAM_INT);

        $member = $query->find();
        $this->assertEquals('1', $member['id']);
    }

    /**
     * @dataProvider providerForParameterValue
     */
    public function testParameterValue($value)
    {
        $this->initFixtures();

        $query = $this
            ->db('member')
            ->where('id = ?', $value)
            ->andWhere('id = ?', $value)
            ->andWhere('id = ?', $value)
            ->orWhere('id = ?', $value)
            ->orWhere('id = ?', $value)
            ->groupBy('id')
            ->having('id = ?', $value)
            ->andHaving('id = ?', $value)
            ->andHaving('id = ?', $value)
            ->orHaving('id = ?', $value)
            ->orHaving('id = ?', $value);

        // No error raise
        $array = $query->fetchAll();
        $this->assertInternalType('array', $array);
    }

    public function providerForParameterValue()
    {
        return array(
            array('0'),
            array(0),
            array(null),
            array(true),
            array(array(null))
        );
    }

    public function testGetAndResetAllSqlParts()
    {
        $query = $this->db('member')->offset(1)->limit(1);

        $this->assertEquals(1, $query->getSqlPart('offset'));
        $this->assertEquals(1, $query->getSqlPart('limit'));

        $queryParts = $query->getSqlParts();
        $this->assertArrayHasKey('offset', $queryParts);
        $this->assertArrayHasKey('limit', $queryParts);

        $query->resetSqlParts();

        $this->assertEquals(null, $query->getSqlPart('offset'));
        $this->assertEquals(null, $query->getSqlPart('limit'));
    }

    public function testGetTableFromQueryBuilder()
    {
        $qb = $this->db('member');
        $this->assertEquals('member', $qb->getTable());

        $qb->from('member m');
        $this->assertEquals('member', $qb->getTable());

        $qb->from('member m');
        $this->assertEquals('member', $qb->getTable());

        $qb->from('member AS m');
        $this->assertEquals('member', $qb->getTable());
    }

    public function testDbCount()
    {
        $this->initFixtures();

        $db = $this->db;

        $count = $db->count('member');
        $this->assertInternalType('int', $count);
        $this->assertEquals(2, $count);

        $count = $db->count('member', array('id' => '1'));
        $this->assertInternalType('int', $count);
        $this->assertEquals(1, $count);

        $count = $db->count('member', array('id' => '1'));
        $this->assertInternalType('int', $count);
        $this->assertEquals(1, $count);

        $count = $db->count('member', array('id' => '123'));
        $this->assertInternalType('int', $count);
        $this->assertEquals(0, $count);
    }

    public function testTablePrefix()
    {
        $this->initFixtures();

        $db = $this->db;

        $db->setOption('tablePrefix', 'tbl_');
        $this->assertEquals('tbl_member', $db->getTable('member'));

        $db->setOption('tablePrefix', 'prefix_post_');
        $this->assertEquals(3, $db->count('tag'));
    }

    public function testConnectFails()
    {
        $this->setExpectedException('\PDOException');
        $test = &$this;
        $db = new \Wei\Db(array(
            'wei' => $this->wei,
            'driver' => 'mysql',
            'host'   => '255.255.255.255',
            'dbname' => 'test',
            'connectFails' => function($db, $exception) use($test) {
                $test->assertTrue(true);
                $test->assertInstanceOf('PDOException', $exception);
            }
        ));
        $db->connect();
    }

    public function testGlobalOption()
    {
        $cb = function(){};
        $this->wei->setConfig(array(
            // sqlite
            'db' => array(
                'beforeConnect' => $cb,
            ),
            'mysql.db' => array(
                'beforeConnect' => $cb,
            ),
            'pgsql.db' => array(
                'beforeConnect' => $cb,
            ),
            'cb.db' => array(
                'db' => $this->db,
                'global' => true
            )
        ));

        $this->assertSame($cb, $this->db->getOption('beforeConnect'));
        $this->assertSame($cb, $this->cbDb->getOption('beforeConnect'));

        // Remove all relation configuration
        unset($this->cbDb);
        $this->wei->remove('cbDb');
        $this->wei->setConfig('cb.db', array(
            'db' => null
        ));
    }

    public function testUnsupportedDriver()
    {
        $this->setExpectedException('\RuntimeException', 'Unsupported database driver: abc');

        $db = new \Wei\Db(array(
            'wei' => $this->wei,
            'driver' => 'abc'
        ));

        $db->query("SELECT MAX(1, 2)");
    }

    public function testCustomDsn()
    {
        $db = new \Wei\Db(array(
            'wei' => $this->wei,
            'dsn' => 'sqlite::memory:'
        ));

        $this->assertEquals('sqlite::memory:', $db->getDsn());
    }

    public function testInsertBatch()
    {
        $this->initFixtures();

        $result = $this->db->insertBatch('member', array(
            array(
                'group_id' => '1',
                'name' => 'twin',
                'address' => 'test'
            ),
            array(
                'group_id' => '1',
                'name' => 'test',
                'address' => 'test'
            )
        ));

        $this->assertEquals(2, $result);
    }

    public function testSlaveDb()
    {
        // Generate slave db configuration name
        $driver = $this->db->getDriver();
        $configName = $driver . 'Slave.db';

        // Set configuration for slave db
        $options = $this->wei->getConfig('db');
        $this->wei->setConfig($configName, $options);

        $this->db->setOption('slaveDb', $configName);

        $query = "SELECT 1 + 2";
        $this->db->query($query);

        // Receives the slave db wei
        /** @var $slaveDb \Wei\Db */
        $slaveDb = $this->wei->get($configName);

        // Test that the query is execute by slave db, not the master db
        $this->assertNotContains($query, $this->db->getQueries());
        $this->assertContains($query, $slaveDb->getQueries());
    }

    public function testReload()
    {
        $this->db->setOption('recordNamespace', 'WeiTest\Db');
        $this->initFixtures();

        /** @var $member2 \WeiTest\Db\Member */
        $member = $this->db->find('member', 1);
        /** @var $member2 \WeiTest\Db\Member */
        $member2 = $this->db->find('member', 1);

        $member['group_id'] = 2;
        $member->save();

        $this->assertNotEquals($member['group_id'], $member2['group_id']);
        $this->assertEquals(1, $member->getLoadTimes());

        $member2->reload();
        $this->assertEquals($member['group_id'], $member2['group_id']);
        $this->assertEquals(2, $member2->getLoadTimes());
    }

    public function testFindOne()
    {
        $this->initFixtures();

        $record = $this->db->findOne('member', 1);
        $this->assertInstanceOf('\Wei\Record', $record);
    }

    public function testFindOneWithException()
    {
        $this->initFixtures();

        $this->setExpectedException('Exception', 'Record not found', 404);

        $this->db->findOne('member', 999);
    }

    public function testisChanged()
    {
        $this->initFixtures();

        $member = $this->db->init('member');
        $this->assertFalse($member->isChanged());

        $member['name'] = 'tt';
        $member['group_id'] = '1';
        $member['address'] = 'address';
        $this->assertFalse($member->isChanged('id'));
        $this->assertTrue($member->isChanged('name'));
        $this->assertTrue($member->isChanged());

        $this->assertNull($member->getChangedData('name'));

        $member['name'] = 'aa';
        $this->assertTrue($member->isChanged());
        $this->assertEquals('tt', $member->getChangedData('name'));

        $member->save();
        $this->assertFalse($member->isChanged());
        $this->assertEmpty($member->getChangedData());
    }

    public function testReconnect()
    {
        $this->db->connect();
        $pdo = $this->db->getOption('pdo');

        $this->db->reconnect();
        $newPdo = $this->db->getOption('pdo');

        $this->assertEquals($pdo, $newPdo);
        $this->assertNotSame($pdo, $newPdo);
    }

    public function testGetter()
    {
        wei(array(
            'test.db' => array(
                'user' => 'user',
                'password' => 'password',
                'host' => 'host',
                'port' => 'port',
                'dbname' => 'dbname'
            )
        ));

        /** @var $testDb \Wei\Db */
        $testDb = $this->testDb;

        $this->assertEquals('user', $testDb->getUser());
        $this->assertEquals('password', $testDb->getPassword());
        $this->assertEquals('host', $testDb->getHost());
        $this->assertEquals('port', $testDb->getPort());
        $this->assertEquals('dbname', $testDb->getDbname());
    }

    public function testQueryBuilderForEach()
    {
        $this->initFixtures();

        $members = $this->db('member')->where('group_id = 1');
        foreach ($members as $member) {
            $this->assertEquals(1, $member['group_id']);
        }
    }

    public function testInsertWithSqlObject()
    {
        $this->initFixtures();

        $this->db->insert('member', array(
            'group_id' => '1',
            'name' => (object)'1 + 1',
            'address' => 'test'
        ));

        $id = $this->db->lastInsertId('prefix_member_id_seq');
        $member = $this->db->select('member', $id);

        $this->assertNotEquals('1 + 1', $member['name']);
        $this->assertEquals('2', $member['name']);
    }

    public function testUpdateWithSqlObject()
    {
        $this->initFixtures();

        $this->db->update('member', array('group_id' => (object)'group_id + 1'), array('id' => (object)'0.5 + 0.5'));

        $member = $this->db->select('member', 1);

        $this->assertEquals('2', $member['group_id']);
    }

    public function testDeleteWithSqlObject()
    {
        $this->initFixtures();

        $result = $this->db->delete('member', array('id' => (object)'0.5 + 0.5'));

        $this->assertEquals(1, $result);
        $this->assertFalse($this->db->select('member', 1));
    }

    public function testRecordWithSqlObject()
    {
        $this->initFixtures();

        $member = $this->db->find('member', 1);
        $groupId = $member['group_id'];

        $member['group_id'] = (object)'group_id + 1';
        $member->save();
        $member->reload();

        $this->assertEquals($groupId + 1, $member['group_id']);
    }

    public function testGetTableFieldsButTableNotExists()
    {
        $this->setExpectedException('PDOException');
        $this->db->getTableFields('notExists');
    }

    public function testNewRecord()
    {
        $this->initFixtures();

        // Use record as array
        $member = $this->db('member')->where('id = 1');
        $this->assertEquals('1', $member['id']);

        // Use record as 2d array
        $members = $this->db('member')->where('group_id = 1');
        foreach ($members as $member) {
            $this->assertEquals(1, $member['group_id']);
        }

        $member1 = $this->db('member');
        $member2 = $this->db('member');
        $this->assertEquals($member1, $member2);
        $this->assertNotSame($member1, $member2);
    }

    public function testCreateRecord()
    {
        $this->initFixtures();

        $member = $this->db('member');

        $data = $member->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('group_id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('address', $data);

        $member->fromArray(array(
            'group_id' => 1,
            'name' => 'John',
            'address' => 'xx street'
        ));
        $result = $member->save();

        $this->assertSame($result, $member);
    }

    public function testBeforeAndAfterCreateCallbacks()
    {
        $this->initFixtures();

        $member = $this->db('member')->fromArray(array(
            'group_id' => 1,
            'name' => 'twin',
            'address' => 'xx street',
        ));

        $member->setOption(array(
            'beforeCreate' => function(){
                echo 'beforeCreate->';
            },
            'afterCreate' => function() {
                echo 'afterCreate->';
            },
            'beforeSave' => function() {
                echo 'beforeSave->';
            },
            'afterSave' => function() {
                echo 'afterSave';
            },
        ));

        $this->expectOutputString('beforeSave->beforeCreate->afterCreate->afterSave');

        $member->save();
    }

    public function testCreateCollection()
    {
        $this->initFixtures();

        $members = $this->db('member');

        $members->fromArray(array(
            array(
                'group_id' => 1,
                'name' => 'John',
                'address' => 'xx street'
            ),
            array(
                'group_id' => 2,
                'name' => 'Tome',
                'address' => 'xx street'
            )
        ));
    }

    public function testFindRecordAndDestroy()
    {
        $this->initFixtures();

        $member = $this->db('member')->find(array('id' => 1));
        $result = $member->destroy();

        $this->assertInstanceOf('\Wei\Record', $result);

        $member = $this->db('member')->find(array('id' => 1));
        $this->assertFalse($member);
    }

    public function testDeleteRecordByQueryBuilder()
    {
        $this->initFixtures();

        $result = $this->db('member')->where('group_id = ?', 1)->delete();
        $this->assertEquals(2, $result);

        $result = $this->db('member')->delete(array('group_id' => 1));
        $this->assertEquals(0, $result);
    }

    public function testFindCollectionAndDestroy()
    {
        $this->initFixtures();

        $members = $this->db('member')->where('group_id = 1');
        $members->destroy();

        $members = $this->db('member')->where('group_id = 1');
        $this->assertEquals(0, count($members));
    }

    public function testFindRecordAndUpdate()
    {
        $this->initFixtures();

        $member = $this->db('member')->find(array('id' => 1));
        $member['name'] = 'William';
        $result = $member->save();
        $this->assertSame($result, $member);

        $member = $this->db('member')->find(array('id' => 1));
        $this->assertEquals('William', $member['name']);
    }

    public function testFindCollectionAndUpdate()
    {
        $this->initFixtures();

        $members = $this->db('member')->where('group_id = 1');
        $number = $members->length();
        $this->assertEquals(2, $number);

        foreach ($members as $member) {
            $member['group_id'] = 2;
        }
        $members->save();

        $members = $this->db('member')->where('group_id = 2');
        $this->assertEquals(2, $members->length());
    }

    public function testCreateCollectionAndSave()
    {
        $this->initFixtures();

        // Creates a member collection
        $members = $this->db('member');

        $john = $this->db('member')->fromArray(array(
            'group_id' => 2,
            'name' => 'John',
            'address' => 'xx street',
        ));

        $larry = $this->db('member')->fromArray(array(
            'group_id' => 3,
            'name' => 'Larry',
            'address' => 'xx street',
        ));

        // Adds record to collection
        $members->fromArray(array(
            $john
        ));

        // Or adds by [] operator
        $members[] = $larry;

        /** @var $members \Wei\Record */
        $result = $members->save();

        $this->assertSame($result, $members);

        // Find out member by id
        $members = $this->db('member')->indexBy('id')->where(array('id' => array($john['id'], $larry['id'])));

        $this->assertEquals('John', $members[$john['id']]['name']);
        $this->assertEquals('Larry', $members[$larry['id']]['name']);
    }

    public function testDestroyRecordAndFindAgainReturnFalse()
    {
        $this->initFixtures();

        $member = $this->db('member');
        $result = $member->find(array('id' => 1))->destroy();

        $this->assertInstanceOf('\Wei\Record', $result);

        $member = $this->db('member')->find(array('id' => 1));
        $this->assertFalse($member);
    }

    public function testSaveOnNoFiledChanged()
    {
        $this->initFixtures();
        $record = $this->db->init('member', array('id' => 1), false);
        $record = $record->save();

        $this->assertInstanceOf('\Wei\Record', $record);
    }

    public function testPrimaryKey()
    {
        $this->initFixtures();

        $record = $this->db->init('member');
        $this->assertEquals('id', $record->getPrimaryKey());

        $record->setPrimaryKey('testId');
        $this->assertEquals('testId', $record->getPrimaryKey());
    }

    public function testIsNew()
    {
        $this->initFixtures();

        $record = $this->db->init('member', array('id' => 1), true);
        $this->assertTrue($record->isNew());

        $record = $this->db->init('member', array('id' => 1), false);
        $this->assertFalse($record->isNew());
    }

    public function testFindByPrimaryKey()
    {
        $this->initFixtures();

        $record = $this->db('member')->find(1);
        $this->assertEquals(1, $record['id']);

        $record = $this->db('member')->find('1');
        $this->assertEquals(1, $record['id']);
    }

    public function testInvalidLimit()
    {
        $this->initFixtures();
        $member = $this->db('member');

        $member->limit(-1);
        $this->assertEquals(1, $member->getSqlPart('limit'));

        $member->limit(0);
        $this->assertEquals(1, $member->getSqlPart('limit'));

        $member->limit('string');
        $this->assertEquals(1, $member->getSqlPart('limit'));
    }

    public function testInvalidOffset()
    {
        $this->initFixtures();
        $member = $this->db('member');

        $member->offset(-1);
        $this->assertEquals(0, $member->getSqlPart('offset'));

        $member->offset(-1.1);
        $this->assertEquals(0, $member->getSqlPart('offset'));

        $member->offset('string');
        $this->assertEquals(0, $member->getSqlPart('offset'));
    }

    public function testMax()
    {
        $this->initFixtures();

        $result = $this->db->max('member', 'id');
        $this->assertInternalType('float', $result);
        $this->assertEquals(2, $result);
    }

    public function testMin()
    {
        $this->initFixtures();

        $result = $this->db->min('member', 'id');
        $this->assertInternalType('float', $result);
        $this->assertEquals(1, $result);
    }

    public function testAvg()
    {
        $this->initFixtures();

        $result = $this->db->avg('member', 'id');
        $this->assertInternalType('float', $result);
        $this->assertEquals(1.5, $result);
    }

    public function testSaveDestroyRecord()
    {
        $this->initFixtures();

        $member = $this->db->find('member', 1);
        $member->destroy();

        $member->save();

        $member = $this->db->find('member', 1);
        $this->assertFalse($member);
    }

    public function testSaveWithNullPrimaryKey()
    {
        $this->initFixtures();

        $member = $this->db('member');
        $member->save(array(
            'id' => null,
            'group_id' => '1',
            'name' => 'twin',
            'address' => 'test'
        ));

        $this->assertNotNull($member['id']);

        $member = $this->db('member');
        $member->save(array(
            'id' => '',
            'group_id' => '1',
            'name' => 'twin',
            'address' => 'test'
        ));

        $this->assertNotNull($member['id']);
    }

    public function testNullAsCollectionKey()
    {
        $this->initFixtures();

        $members = $this->db('member');

        $members[] = $this->db('member');
        $members[] = $this->db('member');
        $members[] = $this->db('member');
        $members[] = $this->db('member');

        $this->assertEquals(4, $members->length());
    }

    public function testSetDataWithProperty()
    {
        $this->initFixtures();

        $member = $this->db('member');

        $member['table'] = 234;

        $this->assertNotEquals(234, $member->getTable());
        $this->assertEquals('member', $member->getTable());
    }

    public function testAddNotRecordToCollection()
    {
        $this->initFixtures();

        $members = $this->db('member');
        $member = $this->db('member');

        // Make sure $members is a collection
        $members[] = $member;

        $this->setExpectedException('InvalidArgumentException', 'Value for collection must be an instance of Wei\Record');

        // Assign non record value to raise an exception
        $members[] = 234;
    }

    public function testGetPdo()
    {
        $this->assertInstanceOf('PDO', $this->db->getPdo());
    }

    public function testIncrAndDecr()
    {
        $this->initFixtures();

        $member = $this->db('member')->find(1);

        $groupId = $member['group_id'];

        $member->incr('group_id', 2);
        $member->save();
        $member->reload();

        $this->assertEquals($groupId + 2, $member['group_id']);

        $member->decr('group_id', 1);
        $member->save();
        $member->reload();

        $this->assertEquals($groupId + 2 - 1, $member['group_id']);
    }

    public function testCreateOrUpdate()
    {
        $this->initFixtures();

        $id = null;
        $member = $this->db('member')->findOrInit($id, array(
            'group_id' => 2,
            'name' => 'twin',
            'address' => 'xx street'
        ));

        $this->assertTrue($member->isNew());
        $this->assertEquals(2, $member['group_id']);

        $member = $this->db('member')->findOrInit(1, array(
            'group_id' => 2,
            'name' => 'twin',
            'address' => 'xx street'
        ));

        $this->assertFalse($member->isNew());
    }

    public function testDetach()
    {
        $this->initFixtures();

        /** @var $member \Wei\Record */
        $member = $this->db('member')->findById(1);

        $this->assertFalse($member->isDetached());

        $member->detach();

        $this->assertTrue($member->isDetached());

        $member->save();

        $this->assertTrue($member->isDestroyed());

        $newMember = $this->db('member')->findById(1);

        $this->assertFalse($newMember);
    }

    public function testRecordFetchColumn()
    {
        $this->initFixtures();

        $count = $this->db('member')->select('COUNT(id)')->fetchColumn();
        $this->assertEquals(2, $count);

        $count = $this->db('member')->select('COUNT(id)')->fetchColumn(array('id' => 1));
        $this->assertEquals(1, $count);
    }
}


