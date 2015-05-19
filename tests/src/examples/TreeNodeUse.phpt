<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityDao;
use Knp\DoctrineBehaviors\Model\Tree\NodeInterface;
use Nette\DI\Container;
use Test\Examples\Model\Entity\TreeNode;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Tree Node use
 *
 * @testCase
 * @phpVersion 5.4
 */
class TreeNodeUseTest extends BaseUse
{

	/** @var EntityDao */
	private $treeDao;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->treeDao = $this->em->getDao(TreeNode::getClassName());
	}

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->dropSchema();
	}

	/** @return TreeNode */
	protected function buildNode(array $values = [])
	{
		$node = new TreeNode;
		foreach ($values as $method => $value) {
			$node->$method($value);
		}

		return $node;
	}

	private function buildTree()
	{
		$item = $this->buildNode();
		$item->setMaterializedPath('');
		$item->setId(1);

		$childItem = $this->buildNode();
		$childItem->setMaterializedPath('/1');
		$childItem->setId(2);
		$childItem->setChildNodeOf($item);

		$secondChildItem = $this->buildNode();
		$secondChildItem->setMaterializedPath('/1');
		$secondChildItem->setId(3);
		$secondChildItem->setChildNodeOf($item);

		$childChildItem = $this->buildNode();
		$childChildItem->setId(4);
		$childChildItem->setMaterializedPath('/1/2');
		$childChildItem->setChildNodeOf($childItem);

		$childChildChildItem = $this->buildNode();
		$childChildChildItem->setId(5);
		$childChildChildItem->setMaterializedPath('/1/2/4');
		$childChildChildItem->setChildNodeOf($childChildItem);

		return $item;
	}

	private function treeToArray()
	{
		$expected = [
			1 => [
				'node' => '',
				'children' => [
					2 => [
						'node' => '',
						'children' => [
							4 => [
								'node' => '',
								'children' => [
									5 => [
										'node' => '',
										'children' => [],
									],
								],
							],
						],
					],
					3 => [
						'node' => '',
						'children' => [],
					],
				],
			],
		];
		return $expected;
	}

	public function testBuildTree()
	{
		$root = $this->buildNode(['setMaterializedPath' => '', 'setName' => 'root', 'setId' => 1]);
		$flatTree = [
			$this->buildNode(['setMaterializedPath' => '/1', 'setName' => 'Villes', 'setId' => 2]),
			$this->buildNode(['setMaterializedPath' => '/1/2', 'setName' => 'Nantes', 'setId' => 3]),
			$this->buildNode(['setMaterializedPath' => '/1/2/3', 'setName' => 'Nantes Est', 'setId' => 4]),
			$this->buildNode(['setMaterializedPath' => '/1/2/3', 'setName' => 'Nantes Nord', 'setId' => 5]),
			$this->buildNode(['setMaterializedPath' => '/1/2/3/5', 'setName' => 'St-Mihiel', 'setId' => 6]),
		];

		$root->buildTree($flatTree);
		Assert::count(1, $root->getChildNodes());

		Assert::count(1, $root->getChildNodes()->first()->getChildNodes());
		Assert::count(2, $root->getChildNodes()->first()->getChildNodes()->first()->getChildNodes());

		Assert::same(1, $root->getNodeLevel());
		Assert::same(4, $root->getChildNodes()->first()->getChildNodes()->first()->getChildNodes()->first()->getNodeLevel());
	}

	public function testIsRoot()
	{
		$tree = $this->buildTree();

		Assert::true($tree->getRootNode()->isRootNode());
		Assert::true($tree->isRootNode());
	}

	public function testIsLeaf()
	{
		$tree = $this->buildTree();

		Assert::true($tree[0][0][0]->isLeafNode());
		Assert::true($tree[1]->isLeafNode());
	}

	public function testGetRoot()
	{
		$tree = $this->buildTree();

		Assert::same($tree, $tree->getRootNode());
		Assert::null($tree->getRootNode()->getParentNode());

		Assert::same($tree, $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getRootNode());
	}

	/**
	 * @dataProvider provideisChildNodeOf
	 */
	public function testIsChildNodeOf(NodeInterface $child, NodeInterface $parent, $expected)
	{
		Assert::same($expected, $child->isChildNodeOf($parent));
	}

	public function provideisChildNodeOf()
	{
		$tree = $this->buildTree();
		return [
			[$tree[0][0], $tree[0], TRUE],
			[$tree[0][0][0], $tree[0][0], TRUE],
			[$tree[0][0][0], $tree[0], FALSE],
			[$tree[0][0][0], $tree[0][0][0], FALSE],
		];
	}

	public function testToArray()
	{
		$expected = $this->treeToArray();
		$tree = $this->buildTree();
		Assert::same($expected, $tree->toArray());
	}

	public function testToJson()
	{
		$expected = $this->treeToArray();
		$tree = $this->buildTree();
		Assert::same(json_encode($expected), $tree->toJson());
	}

	public function testToFlatArray()
	{
		$tree = $this->buildTree();

		$expected = [
			1 => '',
			2 => '----',
			4 => '------',
			5 => '--------',
			3 => '----',
		];

		Assert::same($expected, $tree->toFlatArray());
	}

	public function testArrayAccess()
	{
		$tree = $this->buildTree();

		$tree[] = $this->buildNode(['setId' => 45]);
		$tree[] = $this->buildNode(['setId' => 46]);
		Assert::same(4, $tree->getChildNodes()->count());

		$tree[2][] = $this->buildNode(['setId' => 47]);
		$tree[2][] = $this->buildNode(['setId' => 48]);
		Assert::same(2, $tree[2]->getChildNodes()->count());

		Assert::true(isset($tree[2][1]));
		Assert::false(isset($tree[2][1][2]));

		unset($tree[2][1]);
		Assert::false(isset($tree[2][1]));
	}

	public function testSetChildNodeOfWithoutId()
	{
		Assert::exception(function() {
			$this->buildNode(['setMaterializedPath' => '/0/1'])
					->setChildNodeOf(
							$this->buildNode(['setMaterializedPath' => '/0'])
			);
		}, '\LogicException', 'You must provide an id for this node if you want it to be part of a tree.');
	}

	public function testChildrenCount()
	{
		$tree = $this->buildTree();

		Assert::same(2, $tree->getChildNodes()->count());
		Assert::same(1, $tree->getChildNodes()->get(0)->getChildNodes()->count());
	}

	public function testGetPath()
	{
		$tree = $this->buildTree();

		Assert::same('/1', $tree->getRealMaterializedPath());
		Assert::same('/1/2', $tree->getChildNodes()->get(0)->getRealMaterializedPath());
		Assert::same('/1/2/4', $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getRealMaterializedPath());
		Assert::same('/1/2/4/5', $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0)->getRealMaterializedPath());

		$childChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0);
		$childChildChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0);
		$childChildItem->setChildNodeOf($tree);

		Assert::same('/1/4', $childChildItem->getRealMaterializedPath()); // The path has been updated to the node
		Assert::same('/1/4/5', $childChildChildItem->getRealMaterializedPath()); // The path has been updated to the node and all its descendants
		Assert::true($tree->getChildNodes()->contains($childChildItem)); // The children collection has been updated to reference the moved node
	}

	public function testMoveChildren()
	{
		$tree = $this->buildTree();

		$childChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0);
		$childChildChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0);
		Assert::same(4, $childChildChildItem->getNodeLevel(), 'The level is well calcuated');

		$childChildItem->setChildNodeOf($tree);
		Assert::same('/1/4', $childChildItem->getRealMaterializedPath()); // The path has been updated fo the node
		Assert::same('/1/4/5', $childChildChildItem->getRealMaterializedPath()); // The path has been updated fo the node and all its descendants
		Assert::true($tree->getChildNodes()->contains($childChildItem)); // The children collection has been updated to reference the moved node

		Assert::same(3, $childChildChildItem->getNodeLevel()); // The level has been updated
	}

	public function testGetTree()
	{
		$entity = new TreeNode(1);
		$entity[0] = new TreeNode(2);
		$entity[0][0] = new TreeNode(3);

		$this->em->persist($entity);
		$this->em->persist($entity[0]);
		$this->em->persist($entity[0][0]);
		$this->em->flush();

		$treeNodeDao = $this->em->getDao(TreeNode::getClassName());
		$root = $treeNodeDao->getTree();

		Assert::same($root[0][0], $entity[0][0]);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(TreeNode::getClassName()),
		];
	}

}

$test = new TreeNodeUseTest($container);
$test->run();
