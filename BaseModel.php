<?php namespace App\Models;

use CodeIgniter\Model;


class BaseModel extends Model
{

  /*
  * Specify the columns to be search from the derived class ($table) only.
  * if none is specified, the allowed fields is used instead.
  */
  protected $searchable_columns = [];

  /**
  *   Used by the getList function to join tables.
  *   Description of each key:
  *   table_name: Table to be joined
  *       primary_key: The primary key of table
  *       condition: The foreign key from the derived class
  *       direction: Left and right join
  *       select: Queried results. Always use aliases to avoid conflict with other columns
  *       like: Used during search. specify which column should be looked for.
  *             similar to @see $searchable_columns but for this table only.
  *             there's no need to add an alias here, the program will do the work.
  *
  *
  *   @syntax
  *    [
  *     'table_name' => [
  *      'primary_key' => string,
  *      'condition' => string,
  *      'direction' => string,
  *      'select' => array,
  *      'like' => array
  *    ]
  *    ...additional tables
  *   ]
  *
  *   @sample
  *     'general_settings' => [
  *      'primary_key' => 'id',
  *      'condition' => 'parent_id',
  *      'direction' => 'left',
  *      'select' => ['name as parent_name','description as parent_description'],
  *      'like' => ['name','description']
  *    ]
  *
  *    Default alias per table: join0_herotable,join1_magictable, join2_example.
  *    Adding tables with the same name is no issue as it will only result into:
  *    join0_herotable, join1_herotable
  *    Notice the integer incrementing.
  *
  *
  *
  */
  protected $joinTable = [];

  /*
  * the default prefix of joined-table aliases.
  */
  protected $joinAliasPrefix = "join";

  protected $db;
  public function __construct(){
    parent::__construct();
    $db = \Config\Database::connect();

    if(empty($this->searchable_columns)){
      $this->searchable_columns = $this->allowedFields;
    }
  }


  /**
  * This function returns the queried list
  * @param integer $offset - the starting row to be returned
  * @param integer $limit -  the number of rows to be returned
  * @param string|empty_string $search - what to look for
  * @param mixed $post_argument - for additional arguments, @see postArguments()
  *
  * @return array the queried result (data),
  *               the total record count(recordsTotal), and the filtered result count (recordsFiltered).
  *               The recordsFiltered is the total number of search results.
  */
  public function getList($offset,$limit,$search ="",$post_argument = null){

    $data = $this->getModifiedQuery(true,$search,$post_argument)->findAll($limit,$offset);

    $records_total = $this->getModifiedQuery(false,$search,$post_argument);
    if(!empty($this->deletedField)){
      $records_total = $records_total->where("{$this->table}.{$this->deletedField}",NULL);
    }
    $records_total = $records_total->countAllResults();

    $records_filtered = $this->getModifiedQuery(true,$search,$post_argument)->countAllResults();


    return array(
      "data" => $this->modifyQueryResult($data),
      "recordsTotal" => $records_total,
      "recordsFiltered" => $records_filtered
    );
  }

  /**
  *  To be overridden by subclass
  *  Triggered before the query.
  *  Additional arguments are added here
  *  @param object $query - an instance of predefined query builder
  *  @param mixed $post_argument - additional argument
  */
  protected function postArguments($query,$post_argument){

  }

  /**
  * To be overridden by subclass
  * Triggered after the query
  * @param array $data - query result
  */
  protected function modifyQueryResult($data){
    return $data;
  }

  protected function getSearchField($search, $searchable_columns = null){
    $like = [];

    if(empty($searchable_columns)){
      $searchable_columns = $this->searchable_columns;
    }

    foreach($searchable_columns as $key ){
      $like["{$this->table}.$key"] = $search;
    }

    return $like;
  }

  protected function getJoinSelect($join_table = null){
    $return_val = "";

    $counter = 0;
    foreach($join_table as $row => $key){
      foreach($key["select"] as $select){
        $delimiter = !empty($return_val) ? ", " : "";
        $return_val .=  "{$delimiter}{$this->joinAliasPrefix}{$counter}_{$row}.{$select}";
      }

      $counter++;
    }
    return $return_val;
  }

  protected function setJoin($join_table,$query){
    $counter = 0;
    foreach($join_table as $row => $key){
      $alias = "{$this->joinAliasPrefix}{$counter}_{$row}";
      $query->join("$row as $alias","$alias.{$key['primary_key']} = {$this->table}.{$key['condition']}",$key["direction"]);

      $counter++;
    }
  }

  protected function getLikeJoin($join_table, $return_val = [],$search = ""){
    $counter = 0;
    foreach($join_table as $row => $key){
      foreach($key["like"] as $like){
        $return_val["{$this->joinAliasPrefix}{$counter}_{$row}.{$like}"] = $search ;
      }

      $counter++;
    }
    return $return_val;
  }

  protected function getModifiedQuery($include_conditions, $search, $post_argument){

    $like = $this->getSearchField($search);
    $join_select = $this->getJoinSelect($this->joinTable);
    $query = $this->select("{$this->table}.*,{$join_select}");

    $this->setJoin($this->joinTable, $query);

    if($include_conditions){
      $like = $this->getLikeJoin($this->joinTable,$like,$search);
      $query->groupStart()->orLike($like)->groupEnd();
    }

    $this->postArguments($query,$post_argument);

    return $query;
  }


}
