<?php

class Bloom {
   protected $hashFunction = "crc32";

   protected $bitArray;
   protected $hashCount;
   protected $count = 0;

   function constructorElements($elementsCount) {
      $predictSize = $this->desirableSize($elementsCount);
      $predictHashes = $this->desirableHashCount($predictSize, $elementsCount);

      return $this->constructorSize($predictSize, $predictHashes);
   }

   function constructorSize($size, $hashCount, $hash=null) {
      if($hash !== null) {
         $this->hashFunction = $hash;
      } else {
         if(function_exists("murmurhash3"))
            $this->hashFunction = "murmurhash3";

         if(function_exists("murmurhash"))
            $this->hashFunction = "murmurhash";
      }

      $this->bitArray = new SplFixedArray($size);
      $this->hashCount = $hashCount;
   }

   function __construct() {   // should maybe use __call to do this overloading.
      $count = func_num_args();
      if($count == 0)
         return;  // for load.

      if($count == 1)
         return $this->constructorElements (func_get_arg(0));

      $hash = $size = $hashCount = null;

      $size = func_get_arg(0);
      $hashCount = func_get_arg(1);
      if($count == 3)
         $hash = func_get_arg(2);

      return $this->constructorSize($size, $hashCount, $hash);
   }

   /*
    * desirable* static functions care of http://0pointer.de/blog/projects/bloom.html
    *
    * these compute estimates of the desirable size and hash count of your bloom filter.
    */

   //m = -n*ln(p)/(ln(2)^2) // probability = desired probability of a false positive.
   public static function desirableSize($elements, $probability=0.05) {
      return ceil(-$elements * log($probability) / (pow(log(2), 2)));
   }
   //k = 0.7*m/n
   public static function desirableHashCount($size, $elements) {
      return ceil(0.7 * ($size / $elements));
   }

   public function add($key) {
      if(is_array($key))
      {
         foreach($key as $k) { $this->add($k); }
         return true;
      }

      $this->count++;
      $bits = $this->bits($key);

      foreach($bits as $bit) {
         $this->bitArray[$bit] = true;
      }
      return true;
   }

   public function has($key) {
      if(is_array($key)) {
         foreach($key as $k) {
            if(!$this->has($k))
               return false;
         }
         return true;
      }

      foreach($this->bits($key) as $bit)
      {
         if(!$this->bitArray[$bit])
            return false;
      }
      return true; // possibly true.
   }


   private function bits($key) {
      /*
       * We can trust mt_ to produce uniform values and to produce
       * fixed values for a given hash. Using the random number
       * generator instead of repeated (more complex) hashing is a known technique
       * to reduce the computational resources necessary to Bloom.
       */
      mt_srand($this->computeHash($key));
      $size = $this->bitArray->getSize();


      $return = new SplFixedArray($this->hashCount);
      for($i=0; $i<$this->hashCount; $i++) {
         $return[$i] = mt_rand(0, $size-1);
      }

      return $return;
   }

   private function computeHash($string) {
      $function = $this->hashFunction;
      return $function($string);
   }

   public function save() {
      // definitely needs a better save function. serialize sucks.
      return serialize(array('bitArray'=>$this->bitArray->toArray(), 'count'=>$this->count));
   }

   public function load($data) {
      $data = unserialize($data);
      $this->bitArray = SplFixedArray::fromArray($data['bitArray']);
      $this->count = $data['count'];
      return true;
   }

   public function __toString() {
      $size = $this->bitArray->getSize();

      $string = "Size " . $size . " bloom filter currently holding " . $this->count . " elements.\n";

      if($size < 10000) {
         for($i=0; $i<$size; $i++) {
            $string .= ($this->bitArray[$i] == 1) ? "1" : "0";
         }
      }
      $string .= "\n";
      return $string;
   }
}
?>