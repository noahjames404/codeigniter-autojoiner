<?php namespace App\Models;

use CodeIgniter\Model;
/*
  @author Noah James C. Yanga

  THIS IS ONLY AN EXAMPLE
*/
class InventoryLogModel extends BaseModel
{

      protected $table      = 'inventory_log';
      protected $primaryKey = 'id';

      protected $returnType     = 'array';
      protected $useSoftDeletes = true;

      protected $allowedFields = ['item_id','quantity', 'type'];

      protected $createdField  = 'created_at';
      protected $updatedField  = 'updated_at';
      protected $deletedField  = 'deleted_at';

      protected $joinTable = [
        'item' => [
          'primary_key' => 'id',
          'condition' => 'item_id',
          'direction' => 'left',
          'select' => ['barcode as barcode','name as item'],
          'like' => ['barcode','name']
        ]
      ];



}
