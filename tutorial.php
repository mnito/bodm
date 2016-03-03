<?php

include 'vendor/autoload.php';

use BODM\ActiveModel;
/*
 * 
//----------------------------------------------------------

//How to create ActiveModel
class Post extends ActiveModel
{    
}

//Basic instantiation
$post = new Post(['content' => 'Hey', 'author' => 'Mike']);

echo $post;

/*
 * Output: 
{
    "content": "Hey",
    "author": "Mike"
}
 * 
 */

//-----------------------------------------------------------

//How to create ActiveModel with reference

class Author extends ActiveModel
{
    protected $compressed = ['name', 'Post' => ['reputation']];
}

class Post extends ActiveModel
{
    protected $references = ['author'];
}

$author = new Author(['name' => 'Mike', 'reputation' => 111, 'dateJoined' => '2016-03-03']);

$post = new Post(['author' => $author, 'content' => 'Hey']);

echo json_encode($post->getAttributesWithReferences(), JSON_PRETTY_PRINT);

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

