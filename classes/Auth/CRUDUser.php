<?php
namespace Auth;
class CRUDUser{
  function __construct(){
    $this->collection = \db_cursor("users");
    $this->validation = new \Auth\UserAccountValidation();
  }

  function validate_all(){
    $mutant = [];
    foreach($this->user_info as $key => $value){
      $mutant[$key] = $this->validate_field($key,$value);
      // try{
      // } catch(\Exceptions\HTTP\MethodNotAllowed $e){
      //     continue;
      // }
    }
    if(count($mutant) === 0) throw new \Exception("No valid data submitted.");
    return $mutant;
  }

  function validate_field($field,$value){
    $field_valid_method = "validate_$field";
    if(!\method_exists($this->validation,$field_valid_method)) throw new \Exceptions\HTTP\MethodNotAllowed("Not a valid field.");
    $result = $this->validation->$field_valid_method($value,$field,$this->user_info,$this->collection);
    return $result;
  }

  function add_user($data){
    $this->user_info = $data;
    $validated_user = $this->validate_all();

    /** Add default fields if they aren't specified */
    $validated_user = array_merge(\json_decode(file_get_contents(__DIR__ . "/new_user_schema.json"),true),$validated_user);

    /** Add a timestamp */
    $validated_user['since'] = new \MongoDB\BSON\UTCDateTime();

    $result = $this->collection->insertOne($validated_user);
    if($this->getInsertedCount() !== 1) throw new \Exception("Failed to add user to database.");
    $validated_user['_id'] = $this->getInsertedId();
    return $validated_user;
  }

  function update_user($user_id,$data){
    if(empty($user_id)) throw new \Exception("Missing _id from request.");
    try{
      $uid = new \MongoDB\BSON\ObjectId($user_id);
    } catch(Exception $e){
      throw new \Exception("The _id is malformed.");
    }
    $this->user_info = $data;
    $valid = $this->validate_all($data);
    $result = $this->collection->updateOne(
      ['_id' => $uid],
      ['$set' => $valid]
    );
    if($result->getModifiedCount() !== 1) throw new \Exception("Failed to update the user entry.");
    return $result;
  }
}