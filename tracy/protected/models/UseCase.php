<?php

function useCaseCompare($a, $b) {
    $a_ids = explode('.', $a->id_numer());
    $b_ids = explode('.', $b->id_numer());

    for ($i = 0; $i < max(count($a_ids), count($b_ids)); $i++) {
        if (!isset($a_ids[$i]) || $a_ids[$i] < $b_ids[$i])
            return -1;
        if (!isset($b_ids[$i]) || $b_ids[$i] < $a_ids[$i])
            return +1;
    }
    return 0;
}

/**
 * This is the model class for table "use_case".
 *
 * The followings are the available columns in table 'use_case':
 * @property integer $id_use_case
 * @property string $title
 * @property integer $parent
 * @property integer $gparent
 * @property string $actors
 * @property string $description
 * @property string $pre
 * @property string $post
 *
 * The followings are the available model relations:
 * @property Source $idUseCase
 * @property UseCase $parent0
 * @property UseCase $gparent0
 * @property UseCase[] $useCases
 * @property UseCase[] $specializations
 * @property UseCaseEvent[] $useCaseEvents
 * @property UseCaseEvent[] $useCaseEvents1
 */
class UseCase extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UseCase the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'use_case';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, actors, description, pre, post', 'required'),
			array('parent', 'numerical', 'integerOnly'=>true),
			array('gparent', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id_use_case, title, actors, parent, gparent, description, pre, post', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'idUseCase' => array(self::BELONGS_TO, 'Source', 'id_use_case'),
			'parent0' => array(self::BELONGS_TO, 'UseCase', 'parent'),
			'gparent0' => array(self::BELONGS_TO, 'UseCase', 'gparent'),
			'useCases' => array(self::HAS_MANY, 'UseCase', 'parent'),
			'specializations' => array(self::HAS_MANY, 'UseCase', 'gparent'),
			'useCaseEvents' => array(self::HAS_MANY, 'UseCaseEvent', 'use_case'),
			'useCaseEvents1' => array(self::HAS_MANY, 'UseCaseEvent', 'refers_to'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id_use_case' => 'Id Use Case',
            'title' => 'Title',
            'actors' => 'Actors',
			'parent' => 'Parent',
			'gparent' => 'Generalization parent',
			'description' => 'Description',
			'pre' => 'Pre',
			'post' => 'Post',
		);
	}

    /*  Returns a list of IDS representing the public ID.
     *  For example with: UC1.2.4 it would return [1, 2, 4].
     *  This is mainly used to get the order of UCs right. */
    public function getSplitCode() {
        return explode('.', $this->id_numer());
    }

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id_use_case',$this->id_use_case);
		$criteria->compare('title',$this->title,true);
        $criteria->compare('actors', $this->actors, true);
		$criteria->compare('parent',$this->parent);
		$criteria->compare('gparent',$this->parent);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('pre',$this->pre,true);
		$criteria->compare('post',$this->post,true);

		$dataProvider = new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
            'pagination'=>false
		));

        $data = $dataProvider->getData();
        @usort($data, 'useCaseCompare');
        $sortedDataProvider = new CArrayDataProvider($data, array(
            'keyField' => 'id_use_case',
            'pagination' => false
        ));

        return $sortedDataProvider;
	}
    
    
    function public_id()
    {
        return "UC".$this->id_numer();
    }
    
    function id_numer()
    {
        $this->with('parent0');
        if ( isset($this->parent0) )
        {
            return  $this->parent0->id_numer() . "." .
                    ( $this->count('t.parent=:p and t.id_use_case < :id',
                       array('p'=>$this->parent0->id_use_case,
                             'id'=>$this->id_use_case) ) + 1 );
        }

        return $this->count('
            t.parent is null and (
                (case :act
                    when "Ospite" then false
                    when "Studente" then t.actors in ("Ospite")
                    when "Docente" then t.actors in ("Ospite", "Studente")
                    when "Amministratore" then t.actors in ("Ospite", "Studente", "Docente")
                    when "Proprietario" then t.actors in ("Ospite", "Studente", "Docente", "Amministratore")
                    else false
                end) or (
                    t.actors = :act and
                    ifnull(t.gparent, t.id_use_case) < ifnull(:gp, :id) or (
                        ifnull(t.gparent, t.id_use_case) = ifnull(:gp, :id)
                        and t.id_use_case < :id)))',
            array(
                'id' => $this->id_use_case,
                'gp' => $this->gparent,
                'act' => $this->actors
            )
        ) + 1;
    }
    
    function actors()
    {
        $actors = array();
        $this->with('useCaseEvents');
        foreach($this->useCaseEvents as $event)
        {
            $event->with('primaryActor');
            array_push($actors,$event->primaryActor->description);
        }
        return array_unique($actors);
    }

    // Returns the list of actors
    // as an array of strings.
    function actors_list() {
        return array_map('trim', explode(',', $this->actors));
    }

    // Function that returns the
    // children of the use case.
    // Children are those use cases which are referred to
    // in the main scenario of this one.
    function children() {
        $children = array();
        foreach ($this->useCaseEvents as $uce)
            if (!empty($uce->refersTo) 
                    && $uce->category == 1 
                    && $uce->refersTo->parent == $this->id_use_case)
                $children[] = $uce->refersTo;
        $ret = array_unique($children);
        sort($ret, SORT_STRING);
        return $ret;
    }

    // Returns the list of use cases referred
    // to in the extend scenarios.
    function extend_list() {
        $ret = array();
        foreach ($this->useCaseEvents as $uce)
            if (!empty($uce->refersTo) 
                    && $uce->category == 3)
                    $ret[] = $uce->refersTo;
        $ret = array_unique($ret);
        sort($ret, SORT_STRING);
        return $ret;
    }

    // Returns the list of use cases referred
    // to in the include scenarios.
    function include_list() {
        $ret = array();
        foreach ($this->useCaseEvents as $uce)
            if (!empty($uce->refersTo) 
                    && $uce->category == 4)
                    $ret[] = $uce->refersTo;
        $ret = array_unique($ret);
        sort($ret, SORT_STRING);
        return $ret;
    }

    // Returns the actor set from the children
    // use cases.
    function children_actors_list() {
        $children = $this->children();
        $all_actors = array();
        foreach ($children as $c) {
            $all_actors = array_merge($c->actors_list(), $all_actors);
        }
        return array_unique($all_actors);
    }

    // This is only used for `array_unique`.
    // It's a hack I know. If you find a better way
    // let me know.
    public function __toString() {
        return $this->public_id();
    }
}
