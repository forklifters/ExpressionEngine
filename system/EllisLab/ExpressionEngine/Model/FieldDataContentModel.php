<?php

namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Service\Model\Model as Model;
use EllisLab\ExpressionEngine\Service\Model\Interfaces\Content\Content as Content;

// TODO This desperately needs a better name, but I got nothing!
abstract class FieldDataContentModel extends Model implements Content {

	protected $_fields = array();

	/**
	 *
	 */
	/*
	public function getFields()
	{
		if ( empty($this->fields) && $this->getId() !== NULL)
		{
			$field_structures = $this->getContentStructure()
				->getFieldStructures();

			$field_content_class = $this->alias_service->getRegisteredClass(
				static::getMetaData('field_content_class')
			);

			foreach ($field_structures as $field_structure)
			{
				$fields[$field_structure->field_id] = new $field_content_class(
					$field_structure,
					$this->gateways[static::getMetaData('field_content_gateway')]
				);
			}
		}

		return $this->fields;
	}*/

}