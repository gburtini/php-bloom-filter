<?php
require_once "../bloom.php";
// memory limit is necessary because SplFixedArray appears to grow
// regardless... don't know why.
ini_set("memory_limit", "1024M");

class SpellCheck {
   private $bloom;

   function __construct($wordsList=null) {
      if($wordsList === null)
         return;

      $words = file_get_contents($wordsList);
      $words = explode("\n", $words);
      $this->bloom = new Bloom(count($words));
      foreach($words as $word) {
         $this->bloom->add($word);
      }
   }

   public function load($file) {
      $file = file_get_contents($file);
      $this->bloom = new Bloom();
      $this->bloom->load($file);
   }

   public function save($file) {
      $data = $this->bloom->save();
      return (file_put_contents($file, $data));
   }

   public function checkSpelling($word) {
      return $this->bloom->has($word);
   }
}

//$test = new SpellCheck("/usr/share/dict/words");
//$test->save("spelldb");


$test = new SpellCheck();
$test->load("spelldb");
var_dump($test->checkSpelling("asdfaweiruw"));  // false
var_dump($test->checkSpelling("real"));         // true
var_dump($test->checkSpelling("word"));         // true

?>