<?php

namespace Test\Examples;

use Nette\DI\Container;
use Test\Examples\Model\Entity\Asociation\ManyToManyBidirectional;
use Test\Examples\Model\Entity\Asociation\ManyToManySelfReferencing;
use Test\Examples\Model\Entity\Asociation\ManyToManyUnidirectional;
use Test\Examples\Model\Entity\Asociation\ManyToOneUnidirectional;
use Test\Examples\Model\Entity\Asociation\OneToManyBidirectional;
use Test\Examples\Model\Entity\Asociation\OneToManyUnidirectionalWithJoinTable;
use Test\Examples\Model\Entity\Asociation\OneToOneBidirectional;
use Test\Examples\Model\Entity\Asociation\OneToOneSelfReferencing;
use Test\Examples\Model\Entity\Asociation\OneToOneUnidirectional;
use Test\DbTestCase;
use Tester\Assert;
use Tester\Environment;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Doctrine Association
 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html
 *
 * @testCase
 * @phpVersion 5.4
 */
class DoctrineAssociationTest extends DbTestCase
{

	const MAIL = 'mail@domain.com';
	const NAME = 'some name';
	const ADDRESS = 'street 123';

	private $classes = [];

	public function __construct(Container $container)
	{
		parent::__construct($container);
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-one-unidirectional
	 */
	public function testManyToOneUndirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(ManyToOneUnidirectional\User::getClassName()),
			$this->em->getClassMetadata(ManyToOneUnidirectional\Address::getClassName()),
		];
		$this->updateSchema();

		$userDao = $this->em->getDao(ManyToOneUnidirectional\User::getClassName());
		$addressDao = $this->em->getDao(ManyToOneUnidirectional\Address::getClassName());

		// create owner
		$newUser1 = new ManyToOneUnidirectional\User;
		$newUser1->mail = 'mail1';
		$this->em->persist($newUser1);
		$this->em->flush();
		$user1Id = $newUser1->id;

		$userDao->clear();
		$findedUser1o1 = $userDao->find($user1Id);
		Assert::null($findedUser1o1->address);

		// create address
		$newAddress = new ManyToOneUnidirectional\Address;
		$newAddress->name = self::ADDRESS;
		$this->em->persist($newAddress);
		$this->em->flush();
		$addressId = $newAddress->id;

		// add address to user
		$findedAddress = $addressDao->find($addressId);
		$findedUser1o1->address = $findedAddress;
		$this->em->persist($findedUser1o1);
		$this->em->flush();

		$userDao->clear();
		$findedUser1o2 = $userDao->find($user1Id);
		Assert::type(ManyToOneUnidirectional\Address::getClassName(), $findedUser1o2->address);
		Assert::same(self::ADDRESS, $findedUser1o2->address->name);
		Assert::same($addressId, $findedUser1o2->address->id);

		// second user with same address
		$newUser2 = new ManyToOneUnidirectional\User;
		$newUser2->mail = 'mail2';
		$newUser2->address = $findedAddress;
		$this->em->persist($newUser2);
		$this->em->flush();
		$user2Id = $newUser2->id;

		$userDao->clear();
		$findedUser2o1 = $userDao->find($user2Id);
		Assert::same(self::ADDRESS, $findedUser2o1->address->name);

		// change name in address from user (change from owner side)
		$findedUser2o1->address->name = 'changed address';
		$this->em->persist($findedUser2o1);
		$this->em->flush();

		$userDao->clear();
		$findedUser1o3 = $userDao->find($user1Id);
		Assert::same('changed address', $findedUser1o3->address->name);
		$findedUser2o2 = $userDao->find($user2Id);
		Assert::same('changed address', $findedUser2o2->address->name);

		// delete
		Assert::count(2, $userDao->findAll());
		$userDao->delete($findedUser1o3);
		Assert::count(1, $userDao->findAll());
		$userDao->delete($findedUser2o2);
		Assert::count(0, $userDao->findAll());

		Assert::count(1, $addressDao->findAll());
		$addressDao->delete($findedAddress);
		Assert::count(0, $addressDao->findAll());

		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-unidirectional
	 */
	public function testOneToOneUnidirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(OneToOneUnidirectional\Product::getClassName()),
			$this->em->getClassMetadata(OneToOneUnidirectional\Shipping::getClassName()),
		];
		$this->updateSchema();

		$productDao = $this->em->getDao(OneToOneUnidirectional\Product::getClassName());
		$shippingDao = $this->em->getDao(OneToOneUnidirectional\Shipping::getClassName());

		// create owner
		$newProduct = new OneToOneUnidirectional\Product;
		$newProduct->name = self::NAME;
		$this->em->persist($newProduct);
		$this->em->flush();
		$productId = $newProduct->id;

		$productDao->clear();
		$findedProduct1 = $productDao->find($productId);
		Assert::same(self::NAME, $findedProduct1->name);
		Assert::null($findedProduct1->shipping);

		// create shipping
		$newShipping = new OneToOneUnidirectional\Shipping;
		$newShipping->name = 'shipping 2';
		$this->em->persist($newShipping);
		$this->em->flush();
		$shippingId = $newShipping->id;

		// add shipping
		$shippingDao->clear();
		$findedShipping1 = $shippingDao->find($shippingId);
		$findedProduct1->shipping = $findedShipping1;
		$this->em->persist($findedProduct1);
		$this->em->flush();

		$productDao->clear();
		$findedProduct2 = $productDao->find($productId);
		Assert::same('shipping 2', $findedProduct2->shipping->name);

		// change shipping name (change from owner side)
		$findedProduct2->shipping->name = 'changed shipping name';
		$this->em->persist($findedProduct2);
		$this->em->flush();

		$productDao->clear();
		$findedProduct3 = $productDao->find($productId);
		Assert::same('changed shipping name', $findedProduct3->shipping->name);

		// delete
		$productDao->clear();
		$shippingDao->clear();
		$findedProduct4 = $productDao->find($productId);
		$findedShipping2 = $shippingDao->find($shippingId);
		Assert::count(1, $productDao->findAll());
		Assert::count(1, $shippingDao->findAll());
		$productDao->delete($findedProduct4);
		Assert::count(0, $productDao->findAll());
		Assert::count(1, $shippingDao->findAll());
		$shippingDao->delete($findedShipping2);
		Assert::count(0, $shippingDao->findAll());

		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-bidirectional
	 */
	public function testOneToOneBidirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(OneToOneBidirectional\Cart::getClassName()),
			$this->em->getClassMetadata(OneToOneBidirectional\Customer::getClassName()),
		];
		$this->updateSchema();

		$cartDao = $this->em->getDao(OneToOneBidirectional\Cart::getClassName());
		$customerDao = $this->em->getDao(OneToOneBidirectional\Customer::getClassName());

		// create owner
		$newCart = new OneToOneBidirectional\Cart;
		$newCart->price = 123.4;
		$this->em->persist($newCart);
		$this->em->flush();
		$cartId = $newCart->id;

		$cartDao->clear();
		$findedCart1 = $cartDao->find($cartId);
		Assert::null($findedCart1->customer);

		// create customer
		$newCustomer = new OneToOneBidirectional\Customer;
		$newCustomer->name = self::NAME;
		$this->em->persist($newCustomer);
		$this->em->flush();
		$customerId = $newCustomer->id;

		// add customer to cart
		$customerDao->clear();
		$findedCustomer1 = $customerDao->find($customerId);
		$findedCart1->customer = $findedCustomer1;
		$this->em->persist($findedCart1);
		$this->em->flush();

		$cartDao->clear();
		$findedCart2 = $cartDao->find($cartId);
		Assert::same(self::NAME, $findedCart2->customer->name);

		// change customer name from cart
		$findedCart2->customer->name = 'changed name';
		$this->em->persist($findedCart2);
		$this->em->flush();

		$cartDao->clear();
		$findedCart3 = $cartDao->find($cartId);
		Assert::same('changed name', $findedCart3->customer->name);

		// use cart from customer
		$customerDao->clear();
		$findedCustomer2 = $customerDao->find($customerId);
		Assert::same(123.4, $findedCustomer2->cart->price);

		// delete
		$cartDao->clear();
		$findedCart4 = $cartDao->find($cartId);
		Assert::count(1, $cartDao->findAll());
		Assert::count(1, $customerDao->findAll());
		$cartDao->delete($findedCart4);
		Assert::count(0, $cartDao->findAll());
		Assert::count(1, $customerDao->findAll());
		
		$customerDao->clear();
		$findedCustomer3 = $customerDao->find($customerId);
		$customerDao->delete($findedCustomer3);
		Assert::count(0, $customerDao->findAll());

		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-one-self-referencing
	 */
	public function testOneToOneSelfReferencing()
	{
		$this->classes = [
			$this->em->getClassMetadata(OneToOneSelfReferencing\Student::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional
	 */
	public function testOneToManyBidirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(OneToManyBidirectional\Product::getClassName()),
			$this->em->getClassMetadata(OneToManyBidirectional\Feature::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
	 */
	public function testOneToManyUnidirectionalWithJoinTable()
	{
		$this->classes = [
			$this->em->getClassMetadata(OneToManyUnidirectionalWithJoinTable\User::getClassName()),
			$this->em->getClassMetadata(OneToManyUnidirectionalWithJoinTable\Phonenumber::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-self-referencing
	 */
	public function testOneToManySelfReferencing()
	{
		$this->classes = [
			$this->em->getClassMetadata(ManyToManyUnidirectional\User::getClassName()),
			$this->em->getClassMetadata(ManyToManyUnidirectional\Group::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-unidirectional
	 */
	public function testManyToManyUnidirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(ManyToManyUnidirectional\User::getClassName()),
			$this->em->getClassMetadata(ManyToManyUnidirectional\Group::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-bidirectional
	 */
	public function testManyToManyBidirectional()
	{
		$this->classes = [
			$this->em->getClassMetadata(ManyToManyBidirectional\User::getClassName()),
			$this->em->getClassMetadata(ManyToManyBidirectional\Group::getClassName()),
		];
		$this->updateSchema();
		
		$userDao = $this->em->getDao(ManyToManyBidirectional\User::getClassName());
		$groupDao = $this->em->getDao(ManyToManyBidirectional\Group::getClassName());
		
		// init groups
		$newGroup1 = new ManyToManyBidirectional\Group;
		$newGroup2 = new ManyToManyBidirectional\Group;
		$newGroup1->name = 'group 1';
		$newGroup2->name = 'group 2';
		$this->em->persist($newGroup1);
		$this->em->persist($newGroup2);
		$this->em->flush();
		$group1Id = $newGroup1->id;
		$group2Id = $newGroup2->id;
		
		$groupDao->clear();
		$findedGroup1 = $groupDao->find($group1Id);
		$findedGroup2 = $groupDao->find($group2Id);
		Assert::count(2, $groupDao->findAll());
		
		// create user with groups
		$newUser = new ManyToManyBidirectional\User;
		$newUser->mail = self::MAIL;
		$newUser->addGroup($findedGroup1);
		$newUser->addGroup($findedGroup2);
		$this->em->persist($newUser);
		$this->em->flush();
		$userId = $newUser->id;
		
		$userDao->clear();
		$findedUser1 = $userDao->find($userId);
		Assert::count(2, $findedUser1->groups);
		
		$this->dropSchema();
	}

	/**
	 * http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#many-to-many-self-referencing
	 */
	public function testManyToManySelfReferencing()
	{
		$this->classes = [
			$this->em->getClassMetadata(ManyToManySelfReferencing\User::getClassName()),
		];
		$this->updateSchema();
		
		Assert::same(TRUE, TRUE);
		
		$this->dropSchema();
	}

	protected function setUp()
	{
		parent::setUp();
		Environment::lock('classes', LOCK_DIR);
	}

	protected function getClasses()
	{
		return $this->classes;
	}

}

$test = new DoctrineAssociationTest($container);
$test->run();
