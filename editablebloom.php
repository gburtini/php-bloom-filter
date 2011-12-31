<?php

/*
 * A bloom filter that can have items removed. Remove works by storing a second
 * bloom filter with the deleted items in it. You can consolidate everything down
 * to the single bloom filter by calling EditableBloom::consolidate (which wipes
 * the deleted filter and reconstructs the new bloom filter from scratch).
 *
 * Also adds a confirm option to the has() function, which checks the stored list
 * for the value directly. This is useful if most of your requests are false, as
 * the bloom filter should still be faster than the in_array check.
 */

class EditableBloom extends Bloom {
   private $data;
   private $deleted;

   public function add($key) {
      if(is_array($key)) {
         foreach($key as $k) {
            $this->add($k);
         }
         return true;
      }

      $this->data[] = $key;
      parent::add($key);
   }

   public function remove($key, $consolidate=false) {
      if(is_array($key)) {
         foreach($key as $k) {
            $this->remove($k);
         }
         return true;
      }

      if(false !== ($index = array_search($key, $this->data)))
         unset($this->data[$index]);
      else
         return false;

      if($consolidate) {
         $this->consolidate();
      } else {
         $class = __CLASS__;
         if(!is_object($this->deleted))
            $this->deleted = new $class($this->bitArray->getSize(), $this->hashCount);

         return $this->deleted->add($key);
      }
   }

   private function confirm($key) {
      return in_array($key, $this->data);
   }
   public function has($key, $confirm=false) {
      if(parent::has($key)) {
         $stillExists = !$this->deleted->has($key);
         if(!$confirm)
            return $stillExists;

         if($stillExists) {
            return $this->confirm($key);
         } else {
            return $this->deleted->confirm($key);
         }
      } else {
         return false;
      }
   }

   public function rebuild() { $this->consolidate(); }
   public function consolidate() {
      for($i=0;$i<$this->bitArray->getSize();$i++) {
         $this->bitArray[$i] = 0;
      }

      $data = $this->data;
      $this->data = array();
      $this->deleted = null;

      return $this->add($data);
   }

   public function save() {
      $this->consolidate();

      $data = parent::save();
      $data .= "[--DATA--]";
      $data .= gzcompress(serialize($this->data), 9);
      return $data;
   }

   public function load($data){
      $data = explode("[--DATA--]", $data, 2);
      $this->loadBloomData($data[0]);
      $this->data = unserialize(gzuncompress($data[1]));
   }

   public function toNoneditableBloom() {
      $return = new Bloom($this->bitArray->getSize(), $this->hashCount, $this->hashFunction);

      foreach($this->data as $datum) {
         $return->add($datum);
      }

      return $return;
   }
}

?>
