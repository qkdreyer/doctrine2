<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1335
 */
class DDC1335Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1335User'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1335Phone'),
            ));
            $this->loadFixture();
        } catch(\Exception $e) {
        }
    }


    public function testDql()
    {
        $dql      = 'SELECT u FROM ' . __NAMESPACE__ . '\DDC1335User u INDEX BY u.id';
        $query    = $this->_em->createQuery($dql);
        $result   = $query->getResult();

        self::assertEquals(sizeof($result), 3);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertArrayHasKey(3, $result);

        $dql      = 'SELECT u, p FROM '.__NAMESPACE__ . '\DDC1335User u INDEX BY u.email INNER JOIN u.phones p INDEX BY p.id';
        $query    = $this->_em->createQuery($dql);
        $result   = $query->getResult();

        self::assertEquals(sizeof($result), 3);
        self::assertArrayHasKey('foo@foo.com', $result);
        self::assertArrayHasKey('bar@bar.com', $result);
        self::assertArrayHasKey('foobar@foobar.com', $result);

        self::assertEquals(sizeof($result['foo@foo.com']->phones), 3);
        self::assertEquals(sizeof($result['bar@bar.com']->phones), 3);
        self::assertEquals(sizeof($result['foobar@foobar.com']->phones), 3);

        $foo = $result['foo@foo.com']->phones->toArray();
        $bar = $result['bar@bar.com']->phones->toArray();
        $foobar = $result['foobar@foobar.com']->phones->toArray();

        self::assertArrayHasKey(1, $foo);
        self::assertArrayHasKey(2, $foo);
        self::assertArrayHasKey(3, $foo);

        self::assertArrayHasKey(4, $bar);
        self::assertArrayHasKey(5, $bar);
        self::assertArrayHasKey(6, $bar);

        self::assertArrayHasKey(7, $foobar);
        self::assertArrayHasKey(8, $foobar);
        self::assertArrayHasKey(9, $foobar);
    }

    public function testTicket()
    {
        $builder = $this->_em->createQueryBuilder();
        $builder->select('u')->from(__NAMESPACE__ . '\DDC1335User', 'u', 'u.id');

        $dql    = $builder->getQuery()->getDQL();
        $result = $builder->getQuery()->getResult();

        self::assertEquals(sizeof($result), 3);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertArrayHasKey(3, $result);
        self::assertEquals('SELECT u FROM ' . __NAMESPACE__ . '\DDC1335User u INDEX BY u.id', $dql);
    }

    public function testIndexByUnique()
    {
        $builder = $this->_em->createQueryBuilder();
        $builder->select('u')->from(__NAMESPACE__ . '\DDC1335User', 'u', 'u.email');

        $dql    = $builder->getQuery()->getDQL();
        $result = $builder->getQuery()->getResult();

        self::assertEquals(sizeof($result), 3);
        self::assertArrayHasKey('foo@foo.com', $result);
        self::assertArrayHasKey('bar@bar.com', $result);
        self::assertArrayHasKey('foobar@foobar.com', $result);
        self::assertEquals('SELECT u FROM ' . __NAMESPACE__ . '\DDC1335User u INDEX BY u.email', $dql);
    }

    public function  testIndexWithJoin()
    {
        $builder = $this->_em->createQueryBuilder();
        $builder->select('u','p')
                ->from(__NAMESPACE__ . '\DDC1335User', 'u', 'u.email')
                ->join('u.phones', 'p', null, null, 'p.id');

        $dql    = $builder->getQuery()->getDQL();
        $result = $builder->getQuery()->getResult();

        self::assertEquals(sizeof($result), 3);
        self::assertArrayHasKey('foo@foo.com', $result);
        self::assertArrayHasKey('bar@bar.com', $result);
        self::assertArrayHasKey('foobar@foobar.com', $result);

        self::assertEquals(sizeof($result['foo@foo.com']->phones), 3);
        self::assertEquals(sizeof($result['bar@bar.com']->phones), 3);
        self::assertEquals(sizeof($result['foobar@foobar.com']->phones), 3);

        self::assertArrayHasKey(1, $result['foo@foo.com']->phones->toArray());
        self::assertArrayHasKey(2, $result['foo@foo.com']->phones->toArray());
        self::assertArrayHasKey(3, $result['foo@foo.com']->phones->toArray());

        self::assertArrayHasKey(4, $result['bar@bar.com']->phones->toArray());
        self::assertArrayHasKey(5, $result['bar@bar.com']->phones->toArray());
        self::assertArrayHasKey(6, $result['bar@bar.com']->phones->toArray());

        self::assertArrayHasKey(7, $result['foobar@foobar.com']->phones->toArray());
        self::assertArrayHasKey(8, $result['foobar@foobar.com']->phones->toArray());
        self::assertArrayHasKey(9, $result['foobar@foobar.com']->phones->toArray());

        self::assertEquals('SELECT u, p FROM '.__NAMESPACE__ . '\DDC1335User u INDEX BY u.email INNER JOIN u.phones p INDEX BY p.id', $dql);
    }

    private function loadFixture()
    {
        $p1 = array('11 xxxx-xxxx','11 yyyy-yyyy','11 zzzz-zzzz');
        $p2 = array('22 xxxx-xxxx','22 yyyy-yyyy','22 zzzz-zzzz');
        $p3 = array('33 xxxx-xxxx','33 yyyy-yyyy','33 zzzz-zzzz');

        $u1 = new DDC1335User("foo@foo.com", "Foo",$p1);
        $u2 = new DDC1335User("bar@bar.com", "Bar",$p2);
        $u3 = new DDC1335User("foobar@foobar.com", "Foo Bar",$p3);

        $this->_em->persist($u1);
        $this->_em->persist($u2);
        $this->_em->persist($u3);
        $this->_em->flush();
        $this->_em->clear();
    }

}

/**
 * @Entity
 */
class DDC1335User
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", unique=true)
     */
    public $email;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="DDC1335Phone", mappedBy="user", cascade={"persist", "remove"})
     */
    public $phones;

    public function __construct($email, $name, array $numbers = array())
    {
        $this->name   = $name;
        $this->email  = $email;
        $this->phones = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($numbers as $number) {
            $this->phones->add(new DDC1335Phone($this,$number));
        }
    }
}

/**
 * @Entity
 */
class DDC1335Phone
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(name="numericalValue", type="string", nullable = false)
     */
    public $numericalValue;

    /**
     * @ManyToOne(targetEntity="DDC1335User", inversedBy="phones")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable = false)
     */
    public $user;

    public function __construct($user, $number)
    {
        $this->user     = $user;
        $this->numericalValue   = $number;
    }
}
