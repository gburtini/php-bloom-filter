<?php

class Bloom {
   protected $hashFunction = "crc32";

   protected $bitArray;
   protected $hashCount;
   protected $count;


   function __construct($size, $hashCount=3, $hashes=null) {
      if(function_exists("murmurhash3"))
         $this->hashFunction = "murmurhash3";

      if(function_exists("murmurhash"))
         $this->hashFunction = "murmurhash";

      if($hashes !== null) {
         $this->hashFunction = $hashes;
      }

      $this->bitArray = new SplFixedArray($size);
      $this->hashCount = $hashCount;
   }

   public function add($key) {
      if(is_array($key))
      {
         foreach($key as $k) { $this->add($k); }
         return;
      }

      $this->count++;

      foreach($this->bits($key) as $bit)
         $this->bitArray[$bit] = 1;
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

   public function has($key) {
      foreach($this->bits($key) as $bit)
      {
         if(!$this->bitArray[$bit])
            return false;
      }
      return true; // possibly true.
   }

   private function computeHash($string) {
      if(!is_array($this->hashFunction))
      {
         $function = $this->hashFunction;
         return $function($string);
      }

      $currentFunction = $this->hashFunction[$this->hashIndex++];
      $this->hashIndex = $this->hashIndex % count($this->hashFunction);
      return $currentFunction($string);
   }

}
?>