<?php

include 'header.php';

use BODM\ActiveModel;

/*
 * WORKING WITH REFERENCES AND COMPRESSION * 
 */

class Post extends ActiveModel
{
    /*
     * Make it known that the author attribute is a reference using the
     * reference field and putting the name in an array
     * 
     * Multiple attributes can be references!
     */
    protected $references = ['author'];
}

class Author extends ActiveModel
{
    /*
     * The compressed field is used to express what attributes should be
     * retained when the object is compressed into a reference by an ActiveModel
     * 
     * Attribute names without a string key are general attributes to be used 
     * by any object while those with a class key are used by the class with
     * that class key
     */
    protected $compressed = ['name', 'Post' => ['reputation'], 'Bar' => ['reputation', '__not' => ['name']]];
}


$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03']);

$post = new Post(['author' => $author, 'content' => 'Hey']);


/*
 * Model::bsonSerialize uses the Model::getAttributesWithReferences() method
 * to compress the reference objects
 */

echo json_encode($post->getAttributesWithReferences(), JSON_PRETTY_PRINT).PHP_EOL;


/*
 * Output: 
{
    "author": {
        "name": "Mike",
        "reputation": 111
    },
    "content": "Hey"
}
 * 
 */

//Notice dateJoined is not included
//When we show the post object, it will be included

echo $post.PHP_EOL;

/*
 * Output:
{
    "author": {
        "name": "Mike",
        "reputation": 111,
        "dateJoined": "2016-03-03"
    },
    "content": "Hey"
}
 * 
 */

class Foo extends ActiveModel
{
    protected $references = ['author'];
}

$foo = new Foo(['author' => $author]);

//Notice when compress with this Foo object, only general attributes are included
echo json_encode($foo->getAttributesWithReferences(), JSON_PRETTY_PRINT).PHP_EOL;


/*
 * Output:
{
    "author": {
        "name": "Mike"
    }
}
 * 
 */


class Bar extends ActiveModel
{
    protected $references = ['author'];
}

/*
 * Notice when compress with this Bar object, the __not parameter takes effect
 * and the name is not included
 */
$bar = new Bar(['author' => $author]);

echo json_encode($bar->getAttributesWithReferences(), JSON_PRETTY_PRINT).PHP_EOL;


/*
 * Output:
{
    "author": {
        "reputation": 111
    }
}
 * 
 */



/*
 * Note: If an _id attribute is present it will automatically be injected. 
 */

$author->_id = 4;

echo json_encode($bar->getAttributesWithReferences(), JSON_PRETTY_PRINT).PHP_EOL;

/*
 * Output:
{
    "author": {
        "reputation": 111,
        "_id": 4
    }
}
 * 
 */
