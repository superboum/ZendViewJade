<?php

class Zend_View_Jade implements Zend_View_Interface {
  protected $_jade;
  protected $_params;
  protected $_compiler_path;
  protected $_jade_template;
  protected $_jade_template_basename;
  protected $_cache_dir;
  protected $_data_cache;
  protected $_data_cache_md5;
  protected $_template_jade_tmp;
  protected $_template_cache_html;

  public function __construct($compiler = null) {
    $this->_compiler_path = $compiler;
    $this->_params = [];
  }

  public function getEngine() {
    return $this->_jade;
  }

  public function setScriptPath($path) {
    error_log("Not yet implemented set script path for jade layout");
  }

  public function setBasePath($path, $prefix = 'Zend_View') {
    error_log("Not yet implemented set base path for jade layout");
  }

  public function addBasePath($path, $prefix = 'Zend_View') {
    error_log("Not yet implemented add base path for jade layout");
  }

  public function getScriptPaths() {
    error_log("Not yet implemented get script path for jade layout");
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
    $this->_init_cache();
    if ($this->_need_regen()) {
      $this->_regen();
    }
    require $this->_template_cache_html;
  }

  public function navigation() {
    error_log("Not yet implemented navigation() for jade layout");
  }

  /*********************************
   * PROTECTED OR PRIVATE FUNCTIONS
   *********************************/

  /**
   * Init cache
   *
   * Init cache if necessary
   * @return void
   */
  protected function _init_cache() {
    $this->_jade_template_basename = basename($this->_jade_template);
    $this->_cache_dir = dirname($this->_jade_template)."/.jadecache";
    $this->_data_cache = $this->_cache_dir."/$this->_jade_template_basename.data";
    $this->_data_cache_md5 = $this->_cache_dir."/$this->_jade_template_basename.data.md5";
    $this->_template_jade_tmp = $this->_cache_dir."/$this->_jade_template_basename.tmp";
    $this->_template_cache_html = $this->_cache_dir."/$this->_jade_template_basename.html";

    if(!is_readable($this->_cache_dir)){
      error_log($this->_cache_dir);
      $cache_dir_created = mkdir($this->_cache_dir);
      if (!$cache_dir_created) throw new \Exception("Error, unable to create cache");
    }
  }

  /**
   * Need regen
   *
   * Check if our template need regeneration
   * @return boolean
   */
  protected function _need_regen() {
    //check if the data is really changed
    $data_json = json_encode($this->_params);

    $data_need_regen = true; 
    if(is_readable($this->_data_cache_md5)){
      $data_cache_md5_content = file_get_contents($this->_data_cache_md5); 
      if($data_cache_md5_content == md5($data_json)){
        $data_need_regen = false; 
      }
    }

    if($data_need_regen){
      file_put_contents($this->_data_cache_md5, md5($data_json));
      file_put_contents($this->_data_cache, $data_json);
    }

    $jade_template_mtime = filemtime($this->_jade_template);
    $jade_template_html_mtime = FALSE;
    if(is_readable($this->_template_cache_html)){
      $jade_template_html_mtime = filemtime($this->_template_cache_html);
    }

    return $data_need_regen || ($jade_template_mtime > $jade_template_html_mtime);
  }

  /**
   * Regen
   *
   * Regen cache if necessary
   * @return void
   */
  protected function _regen() {
    if(!isset($this->_compiler_path)){
      $this->_guess_compiler_path();
    }

    if( basename($this->_compiler_path) !== 'jade'){ //provide protection against arbitary command execution
      throw new \Exception("Security Error, try to execute a different program from Jade");
    }

    $jade_template_content = file_get_contents($this->_jade_template);        
    $jade_template_content = $this->_get_var_definitions() . $jade_template_content;

    file_put_contents($this->_template_jade_tmp, $jade_template_content);

    system("{$this->_compiler_path} -P < {$this->_template_jade_tmp} > {$this->_template_cache_html} 2>&1");
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

  /**
   * Get var definitions
   *
   * Parse data cache and loop over json data to generate parameters vor jade command line
   * @return String
   */
  protected function _get_var_definitions() {
    $var_definitions = "";
    $data = json_decode(file_get_contents($this->_data_cache),true); //decode json as an associate array

    if(is_array($data) && count($data) > 0){
      foreach($data as $var_name => $value){
        if(is_array($value)){
          $value = json_encode($value);
        }
        else if(is_string($value)){
          $value = '"'.addslashes($value).'"';
        }
        $var_definitions .= "- var $var_name = $value\n";
      }
    }
    return $var_definitions;
  }
}
