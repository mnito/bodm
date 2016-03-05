<?php

include 'header.php';

use BODM\ActiveModel;

/*
 * BASICS * 
 */

class Post extends ActiveModel
{    
}

//Basic instantiation
$post = new Post(['content' => 'Hey', 'author' => 'Mike']);

echo $post.PHP_EOL;

/*
 * Output: 
{
    "content": "Hey",
    "author": "Mike"
}
 * 
 */
