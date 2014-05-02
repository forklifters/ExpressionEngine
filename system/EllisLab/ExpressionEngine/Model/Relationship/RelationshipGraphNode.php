<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

use EllisLab\ExpressionEngine\Model\Relationship\Types\OneToOne;
use EllisLab\ExpressionEngine\Model\Relationship\Types\OneToMany;
use EllisLab\ExpressionEngine\Model\Relationship\Types\ManyToOne;
use EllisLab\ExpressionEngine\Model\Relationship\Types\ManyToMany;

// Graph node that holds the relationship info for this node.

// A node can return its edges. So if a template has a lastAuthor, then
// lastAuthor is an edge. Member, the model that lastAuthor points to is
// the node on the other end.

class RelationshipGraphNode {

	protected $relationship_infos = array();

	public function __construct($model_class, $alias_service)
	{
		$this->alias_service = $alias_service;
		$this->model_class = $model_class;
	}

	/**
	 * Get an edge by name.
	 *
	 * @param String  $name  Name of the relationship.
	 * @return AbstractRelationship  relationship information
	 */
	public function getEdgeByName($name)
	{
		if ( ! isset($this->cached[$name]))
		{
			$this->cached[$name] = $this->createEdge($name);
		}

		return $this->cached[$name];
	}

	/**
	 * Get all edges regardless of direction.
	 *
	 * @return Array[AbstractRelationship]
	 */
	public function getAllEdges()
	{
		$all = array();
		$class = $this->model_class;
		$data = $class::getMetaData('relationships');

		foreach ($data as $name => $value)
		{
			$all[$name] = $this->getEdgeByName($name);
		}

		return $all;
	}

	/**
	 * Incoming edges are those where we are on the many side.
	 * This equates to a `belongsTo` relationship, so we are not
	 * the parent.
	 *
	 * @return Array[AbstractRelationship]
	 */
	public function getAllIncomingEdges()
	{
		$all = $this->getAllEdges();

		return array_filter($all, function($rel)
		{
			return ! $rel->is_parent;
		});
	}

	/**
	 * Outgoing edges are those where we are on the one side.
	 * This equates to a `has` relationship, so we are the
	 * parent.
	 *
	 * @return Array[AbstractRelationship]
	 */
	public function getAllOutgoingEdges()
	{
		$all = $this->getAllEdges();

		return array_filter($all, function($rel)
		{
			return $rel->is_parent;
		});
	}

	/**
	 * Use the relationship name to lazily fetch all of the related
	 * metadata for this edge. Currently an edge is drawn from the reference
	 * point of either model. This is not ideal, but it is working for now
	 * and avoids matching relationships when we don't have to.
	 *
	 * @return AbstractRelationship
	 */
	private function createEdge($name)
	{
		$from_class = $this->model_class;

		$relationships = $from_class::getMetaData('relationships');
		$relationship = $relationships[$name];

		$model = isset($relationship['model']) ? $relationship['model'] : $name;
		$to_class = $this->alias_service->getRegisteredClass($model);

		switch ($relationship['type'])
		{
			case 'one_to_one':
				return new OneToOne($from_class, $to_class, $name);
			case 'one_to_many':
				return new OneToMany($from_class, $to_class, $name);
			case 'many_to_one':
				return new ManyToOne($from_class, $to_class, $name);
			case 'many_to_many':
				return new ManyToMany($from_class, $to_class, $name);
		}

		throw new \Exception('Invalid or Missing Relationship Type for "'. $name. '" in '.$from_class.'.');
	}
}