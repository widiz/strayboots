<?php

use \Phalcon\Mvc\Model\Relation;

class PointTypes extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $name;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('id', 'Points', 'type_id', [
            'alias' => 'Points',
            'foreignKey' => [
                'action' => Relation::ACTION_RESTRICT,
                'message' => 'This type has points'
            ]
        ]);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'point_types';
    }

    /**
     * Validations and business logic
     *
     * @return boolean
     */
    public function validation()
    {
        $validator = new Phalcon\Validation();

        $validator->add('name', new Phalcon\Validation\Validator\StringLength([
            'max' => 100,
            'min' => 2,
            'messageMaximum' => 'Name is too long',
            'messageMinimum' => 'Name is too short'
        ]));
        $validator->add('name', new Phalcon\Validation\Validator\Uniqueness([
            'message'   => 'Point type already exists'
        ]));

        return $this->validate($validator);
    }

}
