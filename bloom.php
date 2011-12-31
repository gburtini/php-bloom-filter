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
      $file = $this->count . " " . $this->hashCount . " " . $this->hashFunction . " ";

      for($i=0;$i<$this->bitArray->getSize();$i++)
      {
         $output = "0";
         if($this->bitArray[$i] == 1)
            $output = "1";
         $file .= $output;
      }
      $file = gzcompress($file, 9);

      return $file;
   }

   public function load($data) {
      $data = gzuncompress($data);
      $data = explode(" ", $data, 4);
      $this->count = $data[0];
      $this->hashCount = $data[1];
      $this->hashFunction = $data[2];
      $length = strlen($data[3]);

      $this->bitArray = new SplFixedArray($length);
      for($i=0;$i<$length;$i++) {
         if($data[3][$i] == 1)
            $this->bitArray[$i] = true;
      }

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