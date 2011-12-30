<?php

class Bloom {
   protected $hashFunction = "crc32";

   protected $bitArray;
   protected $hashCount;
   protected $count;

   function constructorElements($elementsCount) {
      $predictSize = $this->desirableSize($elementsCount);
      $predictHashes = $this->desirableHashCount($predictSize, $elementsCount);
      return $this->__construct($predictSize, $predictHashes);
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
         return trigger_error("Not enough arguments passed to constructor for Bloom.");

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
      return (-$elements * log($probability) / (pow(log(2), 2)));
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

      foreach($this->bits($key) as $bit)
         $this->bitArray[$bit] = 1;

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
         $return[] = mt_rand(0, $size);
      }
      return $return;
   }

   private function computeHash($string) {
      $function = $this->hashFunction;
      return $function($string);
   }

   public function __toString() {
      $string = "Size " . $this->bitArray->getSize() . " bloom filter currently holding " . $count . " elements.\n";
      for($i=0; $i<$this->bitArray->getSize(); $i++) {
         $string .= ($this->bitArray[$i] == 1) ? "1" : "0";
      }
      $string .= "\n";
      return $string;
   }
}
?>