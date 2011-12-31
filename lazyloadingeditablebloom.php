<?php
/*
 * The main advantage here is to check a series of false records without
 * ever gzuncompress/unserializing the data. It could be made more efficient
 * by changing the data loader so that the [--DATA--] portion wasn't even
 * read.
 *
 * For example, if checking IP addresses for banned IPs (where you want to
 * confirm that they're actually banned, via the has function's confirm method)
 * you could use this Bloom implementation. For false matches (guaranteed correct)
 * you'll never have to load the inputCache data. 
 */
class LazyLoadingEditableBloom extends EditableBloom {
   private $inputCache;

   public function load($data){
      $data = explode("[--DATA--]", $data, 2);
      $this->loadBloomData($data[0]);

      $this->inputCache = ($data[1]);
   }

   public function save() {
      $this->loadCachedData();
      return parent::save();
   }

   public function remove($key, $consolidate=false) {
      $this->loadCachedData();
      return parent::remove($key, $consolidate);
   }

   public function has($key, $confirm=false) {
      if($confirm === true) {
         $this->loadCachedData();
      }
      return parent::has($key, $confirm);
   }

   public function consolidate() {
      $this->loadCachedData();
      return parent::consolidate();
   }

   private function loadCachedData() {
      if(is_null($this->inputCache))
         return;

      $dataCache = unserialize(gzuncompress($this->inputCache));
      if(is_array($this->data))
         $this->data = array_merge($dataCache, $this->data);
      else
         $this->data = $dataCache;
      $this->inputCache = null;

   }
}

?>