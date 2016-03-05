<?php

include 'header.php';

use BODM\ActiveModel;

/*
 * ETC *
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

//To switch to fullDebug mode

$post->switchFullDebug();

var_dump($post);

/*
 * Output:
object(Post)#8 (14) {
  ["references"]=>
  array(1) {
    [0]=>
    string(6) "author"
  }
  ["dbName"]=>
  string(7) "default"
  ["collectionName"]=>
  string(4) "post"
  ["embedded"]=>
  bool(false)
  ["autoExpand"]=>
  bool(false)
  ["graceful"]=>
  bool(true)
  ["collection"]=>
  object(MongoDB\Collection)#9 (7) {
    ["collectionName"]=>
    string(4) "post"
    ["databaseName"]=>
    string(7) "default"
    ["manager"]=>
    object(MongoDB\Driver\Manager)#3 (3) {
      ["request_id"]=>
      int(11766)
      ["uri"]=>
      string(25) "mongodb://localhost:27017"
      ["cluster"]=>
      array(1) {
        [0]=>
        array(11) {
          ["host"]=>
          string(9) "localhost"
          ["port"]=>
          int(27017)
          ["type"]=>
          int(0)
          ["is_primary"]=>
          bool(false)
          ["is_secondary"]=>
          bool(false)
          ["is_arbiter"]=>
          bool(false)
          ["is_hidden"]=>
          bool(false)
          ["is_passive"]=>
          bool(false)
          ["tags"]=>
          array(0) {
          }
          ["last_is_master"]=>
          array(0) {
          }
          ["round_trip_time"]=>
          int(-1)
        }
      }
    }
    ["readConcern"]=>
    object(MongoDB\Driver\ReadConcern)#10 (1) {
      ["level"]=>
      NULL
    }
    ["readPreference"]=>
    object(MongoDB\Driver\ReadPreference)#11 (2) {
      ["mode"]=>
      int(1)
      ["tags"]=>
      array(0) {
      }
    }
    ["typeMap"]=>
    array(3) {
      ["array"]=>
      string(23) "MongoDB\Model\BSONArray"
      ["document"]=>
      string(26) "MongoDB\Model\BSONDocument"
      ["root"]=>
      string(26) "MongoDB\Model\BSONDocument"
    }
    ["writeConcern"]=>
    object(MongoDB\Driver\WriteConcern)#12 (4) {
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
  ["lastResult"]=>
  NULL
  ["compressed"]=>
  array(0) {
  }
  ["referenceObjects"]=>
  array(0) {
  }
  ["attributes"]=>
  array(2) {
    ["author"]=>
    object(Author)#2 (3) {
      ["name"]=>
      string(4) "Mike"
      ["reputation"]=>
      int(111)
      ["dateJoined"]=>
      string(10) "2016-03-03"
    }
    ["content"]=>
    string(3) "Hey"
  }
  ["original"]=>
  array(2) {
    ["author"]=>
    object(Author)#2 (3) {
      ["name"]=>
      string(4) "Mike"
      ["reputation"]=>
      int(111)
      ["dateJoined"]=>
      string(10) "2016-03-03"
    }
    ["content"]=>
    string(3) "Hey"
  }
  ["updates"]=>
  array(0) {
  }
  ["fullDebug"]=>
  bool(true)
}
 */


//Suggested constructing method


class Foo extends ActiveModel
{
    public static function create(int $bar, array $baz)
    {
        return parent::construct(['bar' => $bar, 'baz' => $baz]);
    }
}

$foo = Foo::create(2, ['foo', 'bar', 'baz']);

echo $foo.PHP_EOL;

/*
 * Output:
{
    "bar": 2,
    "baz": [
        "foo",
        "bar",
        "baz"
    ]
}
 */
