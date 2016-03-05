<?php

include 'header.php';

use BODM\ActiveModel;

/*
 * SAVING AND REMOVING * 
 */

/*
 * General:
 * 
 * Insert and update are implemented using the Save command. The object
 * is traversed by its references and it is decided whether each object needs 
 * updating, inserting, or no action.
 * 
 * The insert and update methods of ActiveModel are identical to save other
 * than an additional check for an id which will make or break the respective
 * operation at the origin.
 * 
 */

class Post extends ActiveModel
{
    protected $references = ['author'];
}

class Author extends ActiveModel
{
    protected $compressed = ['name', 'Post' => ['reputation']];
}


$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03']);
$post = new Post(['author' => $author, 'content' => 'Hey']);

$post->save();

echo $post.PHP_EOL;

/*
 * Output:
{
    "author": {
        "name": "Mike",
        "reputation": 111,
        "dateJoined": "2016-03-03",
        "_id": "56d86bbc7195801660001376"
    },
    "content": "Hey",
    "_id": "56d86bbc7195801660001377"
}
 */

/*
 * Notice both author and post were inserted because author was referenced and
 * its _id was empty
 */

$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03']);
$post = new Post(['author' => $author, 'content' => 'Hey']);

$author->_id = 4;

$post->save();

var_dump($post->getLastResult());


/*
 * Output:
array(2) {
  [0]=>
  object(MongoDB\UpdateResult)#19 (2) {
    ["writeResult":"MongoDB\UpdateResult":private]=>
    object(MongoDB\Driver\WriteResult)#20 (9) {
      ["nInserted"]=>
      int(0)
      ["nMatched"]=>
      int(0)
      ["nModified"]=>
      int(0)
      ["nRemoved"]=>
      int(0)
      ["nUpserted"]=>
      int(0)
      ["upsertedIds"]=>
      array(0) {
      }
      ["writeErrors"]=>
      array(0) {
      }
      ["writeConcernError"]=>
      NULL
      ["writeConcern"]=>
      array(4) {
        ["w"]=>
        NULL
        ["wmajority"]=>
        bool(false)
        ["wtimeout"]=>
        int(0)
        ["journal"]=>
        NULL
      }
    }
    ["isAcknowledged":"MongoDB\UpdateResult":private]=>
    bool(true)
  }
  [1]=>
  object(MongoDB\InsertOneResult)#18 (3) {
    ["writeResult":"MongoDB\InsertOneResult":private]=>
    object(MongoDB\Driver\WriteResult)#16 (9) {
      ["nInserted"]=>
      int(1)
      ["nMatched"]=>
      int(0)
      ["nModified"]=>
      int(0)
      ["nRemoved"]=>
      int(0)
      ["nUpserted"]=>
      int(0)
      ["upsertedIds"]=>
      array(0) {
      }
      ["writeErrors"]=>
      array(0) {
      }
      ["writeConcernError"]=>
      NULL
      ["writeConcern"]=>
      array(4) {
        ["w"]=>
        NULL
        ["wmajority"]=>
        bool(false)
        ["wtimeout"]=>
        int(0)
        ["journal"]=>
        NULL
      }
    }
    ["insertedId":"MongoDB\InsertOneResult":private]=>
    object(MongoDB\BSON\ObjectID)#10 (1) {
      ["oid"]=>
      string(24) "56d86c987195801660001384"
    }
    ["isAcknowledged":"MongoDB\InsertOneResult":private]=>
    bool(true)
  }
}
 */

/*
 * Notice an update was attempted because we dynamically added an _id to the
 * author. It did not succeed because there is no corresponding _id and upsert
 * is not set.
 * 
 */

echo $post.PHP_EOL;

/*
 * Output:
{
    "author": {
        "name": "Mike",
        "reputation": 111,
        "dateJoined": "2016-03-03",
        "_id": 4
    },
    "content": "Hey",
    "_id": "56d86c987195801660001384"
}
 */

//A new _id field is not present because an insert did not occur


/* Note: The update operation was only attempted because we dynamically added
 * the _id property. Any property that is added after the initial fill of the
 * object will be recorded as an update and will be used when an update is performed.
 * 
 * If no updates to the object occurred after the initial fill, nothing would
 * happen.
 * 
 */

//Example:

$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03', '_id' => 4]);
$post = new Post(['author' => $author, 'content' => 'Hey']);

$post->save();

var_dump($post->getLastResult());

/*
 * Output:
array(1) {
  [0]=>
  object(MongoDB\InsertOneResult)#26 (3) {
    ["writeResult":"MongoDB\InsertOneResult":private]=>
    object(MongoDB\Driver\WriteResult)#9 (9) {
      ["nInserted"]=>
      int(1)
      ["nMatched"]=>
      int(0)
      ["nModified"]=>
      int(0)
      ["nRemoved"]=>
      int(0)
      ["nUpserted"]=>
      int(0)
      ["upsertedIds"]=>
      array(0) {
      }
      ["writeErrors"]=>
      array(0) {
      }
      ["writeConcernError"]=>
      NULL
      ["writeConcern"]=>
      array(4) {
        ["w"]=>
        NULL
        ["wmajority"]=>
        bool(false)
        ["wtimeout"]=>
        int(0)
        ["journal"]=>
        NULL
      }
    }
    ["insertedId":"MongoDB\InsertOneResult":private]=>
    object(MongoDB\BSON\ObjectID)#27 (1) {
      ["oid"]=>
      string(24) "56d86fd0719580166000138b"
    }
    ["isAcknowledged":"MongoDB\InsertOneResult":private]=>
    bool(true)
  }
 */

//Only an insert was attempted.

/*
 * Updating is a complex operation with compressed references involved.
 * An update occurs at every level of the object where it is warranted.
 * References are not uncompressed in the database when an update occurs and
 * only available attributes are updated for each level.
 */

//For example:

$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03', '_id' => 4]);
$post = new Post(['author' => $author, 'content' => 'Hey']);

$author->postsAuthored = 5;

var_dump($author->getUpdates());

/*
 * Output:
 array(1) {
  ["postsAuthored"]=>
  int(5)
}
 */

var_dump($post->getUpdates());

/*
 * Output:
array(0) {
} 
 */

//But if we update the name attribute which is referenced...

$author->name = 'Mikey';

var_dump($post->getUpdates());

/*
 * Output:
array(1) {
  ["author.name"]=>
  string(5) "Mikey"
}
 */


/*
 * To remove an object, use the remove method.
 * This method will only remove the base object and not any references
 */
$post->remove();
