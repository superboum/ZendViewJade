<?php

class PHPJade_View implements Zend_View_Interface {
  protected $_jade;
  protected $_params;
  protected $_compiler_path;
  protected $_jade_template;

  public function __construct($compiler = null) {
    $this->_compiler_path = $compiler;
    $this->_params = [];
  }

  public function getEngine() {
    return $this->_jade;
  }

  public function setScriptPath($path) {
  }

  public function setBasePath($path, $prefix = 'Zend_View') {
  }

  public function addBasePath($path, $prefix = 'Zend_View') {
  }

  public function getScriptPaths() {
    return [];
  }

  public function __set($key, $value) {
    $this->_params[$key] = $value;
  }

  public function __isset($key) {
    return isset($this->_params[$key]);
  }

  public function __unset($key) {
    unset($this->_params[$key]);
  }

  public function assign($spec, $value = null) {
    if (is_array($spec)) { $this->_params = $spec; }
    else { $this->_params[$spec] = $value; }
  }

  public function clearVars() {
    $this->_params = [];
  }

  public function render($name) {
    $this->_jade_template = $name;
    return $this->_gen();
  }

  public function navigation() {
  }

  /*********************************
   * PROTECTED OR PRIVATE FUNCTIONS
   *********************************/

  /**
   * Regen
   *
   * Regen cache if necessary
   * @return void
   */
  protected function _gen() {
    if(!isset($this->_compiler_path)){
      $this->_guess_compiler_path();
    }

    if( basename($this->_compiler_path) !== 'jade'){ //provide protection against arbitary command execution
      throw new \Exception("Security Error, try to execute a different program from Jade");
    }
    $json_data = json_encode($this->_params, JSON_HEX_QUOT|JSON_PRETTY_PRINT);
    $error = json_last_error();
    if ($error && $error != 'No error') {
         throw new \LogicException(sprintf("Failed to serialize locals, error: %s", $error));
    }
    $tmpfname = tempnam(sys_get_temp_dir(), 'PP'); 
    file_put_contents($tmpfname, $json_data);

    $o = array();
    $rv = 0;
    $cmd = "{$this->_compiler_path} -P -p {$this->_jade_template} -O \"".$tmpfname."\" < {$this->_jade_template}";
    exec($cmd, $o, $rv);
    return implode("\n",$o);
  }

  /**
   * Guess compiler path
   *
   * Use UNIX command which to obtain jade path
   * @return void
   */
  protected function _guess_compiler_path() {
    ob_start();
    $compiler_path = system("which jade 2>&1", $compiler_path); //if the compiler path is not set yet, try to find a default one
    ob_end_clean();
    $this->_compiler_path = $compiler_path;
  }
}
