<?php

namespace EllisLab\ExpressionEngine\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamVocabulary extends Model {

	protected static $_primary_key = 'vocabulary_id';

	protected static $_relationships = array(
		'Kernel' => array(
			'type' => 'belongsTo',
			'model' => 'SpamKernel',
			'to_key' => 'kernel_id'
		)
	);

	protected $vocabulary_id;
	protected $kernel_id;
	protected $term;
	protected $count;

}
