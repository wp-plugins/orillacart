<?php

class carrierTable extends table {

    public $method_id = null;
    public $name = null;
    public $method_order = 0;
    public $class = null;
    public $type = 'shipping';
    public $params = null;

    public function __construct() {

        parent::__construct('method_id', '#_shop_methods');
    }

    public function load($id) {

        parent::load($id);

        if (empty($this->class))
            $this->class = 'standart_shipping';

        if (!$this->pk()) {

            $db = Factory::getDBO();
            $db->setQuery("SELECT MAX(method_order)+1 FROM #_shop_methods WHERE type='shipping'");
            if (!$db->getResource()) {
                throw new Exception($db->getErrorString());
            }
            $this->method_order = (int) $db->loadResult();
        }

        $this->params = new Registry(stripslashes($this->params));



        return $this;
    }

    public function store() {

        if ($this->class == 'standart_shipping')
            $this->class = null;

        if (empty($this->name)) {
            Factory::getApplication('shop')->addError(__('Enter carrier label', 'com_shop'));
            return false;
        }



        if ($this->params instanceof registry) {
            $this->params = $this->params->toString();
        } else {
            $this->params = new Registry($this->params);
            $this->params = $this->params->toString();
        }
        return parent::store();
    }

}