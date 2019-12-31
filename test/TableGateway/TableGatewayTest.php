<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Db\TableGateway;

use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-03-01 at 21:02:22.
 */
class TableGatewayTest extends \PHPUnit_Framework_TestCase
{
    protected $mockAdapter = null;

    public function setup()
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->getMock('Laminas\Db\Adapter\Driver\ResultInterface');
        $mockStatement = $this->getMock('Laminas\Db\Adapter\Driver\StatementInterface');
        $mockStatement->expects($this->any())->method('execute')->will($this->returnValue($mockResult));
        $mockConnection = $this->getMock('Laminas\Db\Adapter\Driver\ConnectionInterface');
        $mockDriver = $this->getMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $mockDriver->expects($this->any())->method('createStatement')->will($this->returnValue($mockStatement));
        $mockDriver->expects($this->any())->method('getConnection')->will($this->returnValue($mockConnection));

        // setup mock adapter
        $this->mockAdapter = $this->getMock('Laminas\Db\Adapter\Adapter', null, [$mockDriver]);
    }

    /**
     * Beside other tests checks for plain string table identifier
     */
    public function testConstructor()
    {
        // constructor with only required args
        $table = new TableGateway(
            'foo',
            $this->mockAdapter
        );

        $this->assertEquals('foo', $table->getTable());
        $this->assertSame($this->mockAdapter, $table->getAdapter());
        $this->assertInstanceOf('Laminas\Db\TableGateway\Feature\FeatureSet', $table->getFeatureSet());
        $this->assertInstanceOf('Laminas\Db\ResultSet\ResultSet', $table->getResultSetPrototype());
        $this->assertInstanceOf('Laminas\Db\Sql\Sql', $table->getSql());

        // injecting all args
        $table = new TableGateway(
            'foo',
            $this->mockAdapter,
            $featureSet = new Feature\FeatureSet,
            $resultSet = new ResultSet,
            $sql = new Sql($this->mockAdapter, 'foo')
        );

        $this->assertEquals('foo', $table->getTable());
        $this->assertSame($this->mockAdapter, $table->getAdapter());
        $this->assertSame($featureSet, $table->getFeatureSet());
        $this->assertSame($resultSet, $table->getResultSetPrototype());
        $this->assertSame($sql, $table->getSql());

        // constructor expects exception
        $this->setExpectedException(
            'Laminas\Db\TableGateway\Exception\InvalidArgumentException',
            'Table name must be a string or an instance of Laminas\Db\Sql\TableIdentifier'
        );
        new TableGateway(
            null,
            $this->mockAdapter
        );
    }

    /**
     * @group 6726
     * @group 6740
     */
    public function testTableAsString()
    {
        $ti = 'fooTable.barSchema';
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter
        );

        $this->assertEquals($ti, $table->getTable());
    }

    /**
     * @group 6726
     * @group 6740
     */
    public function testTableAsTableIdentifierObject()
    {
        $ti = new TableIdentifier('fooTable', 'barSchema');
        // constructor with only required args
        $table = new TableGateway(
            $ti,
            $this->mockAdapter
        );

        $this->assertEquals($ti, $table->getTable());
    }

    /**
     * @group 6726
     * @group 6740
     */
    public function testTableAsAliasedTableIdentifierObject()
    {
        $aliasedTI = ['foo' => new TableIdentifier('fooTable', 'barSchema')];
        // constructor with only required args
        $table = new TableGateway(
            $aliasedTI,
            $this->mockAdapter
        );

        $this->assertEquals($aliasedTI, $table->getTable());
    }

    public function aliasedTables()
    {
        $identifier = new TableIdentifier('Users');
        return [
            'simple-alias'     => [['U' => 'Users'], 'Users'],
            'identifier-alias' => [['U' => $identifier], $identifier],
        ];
    }

    /**
     * @group 7311
     * @dataProvider aliasedTables
     */
    public function testInsertShouldResetTableToUnaliasedTable($tableValue, $expected)
    {
        $phpunit = $this;

        $insert = new Insert();
        $insert->into($tableValue);

        $result = $this->getMockBuilder('Laminas\Db\Adapter\Driver\ResultInterface')
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->will($this->returnValue(1));

        $statement = $this->getMockBuilder('Laminas\Db\Adapter\Driver\StatementInterface')
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result));

        $statementExpectation = function ($insert) use ($phpunit, $expected, $statement) {
            $state = $insert->getRawState();
            $phpunit->assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder('Laminas\Db\Sql\Sql')
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->atLeastOnce())
            ->method('getTable')
            ->will($this->returnValue($tableValue));
        $sql->expects($this->once())
            ->method('insert')
            ->will($this->returnValue($insert));
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($insert))
            ->will($this->returnCallback($statementExpectation));

        $table = new TableGateway(
            $tableValue,
            $this->mockAdapter,
            null,
            null,
            $sql
        );

        $result = $table->insert([
            'foo' => 'FOO',
        ]);

        $state = $insert->getRawState();
        $this->assertInternalType('array', $state['table']);
        $this->assertEquals(
            $tableValue,
            $state['table']
        );
    }
}
