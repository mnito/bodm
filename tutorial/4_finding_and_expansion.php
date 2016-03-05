<?php

include 'header.php';

use BODM\ActiveModel;

/*
 * FINDING AND EXPANSION * 
 */

/*
 * Note: The autoExpand property is set to false by default, meaning that
 * accessing and reference from the root object will not cause it to expand
 * automatically. To enable this behavior, set it to true. This behavior can
 * also be modified dynamically. Auto expanding may not make sense in some cases
 * where you want the references to save database calls or to prevent loading
 * a large object, which is a large part of the point of references.
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


/* 
 * When we save, the Post object is stored in its own collection and the
 * Author object is stored in its own collection.
 */

echo $post.PHP_EOL;

$postId = $post->_id;
$authorId = $author->_id;

echo $post->_id.PHP_EOL;
echo $author->_id.PHP_EOL;

$foundPost = Post::findOneById($postId);

$foundAuthor = Author::findOneById($authorId);


echo $foundPost.PHP_EOL;


/*
 *Output: 
{
    "_id": "56db1a2f719580143000101e",
    "author": {
        "name": "Mike",
        "reputation": 111,
        "_id": "56db1a2f719580143000101d"
    },
    "content": "Hey"
}
 * 
 */

echo $foundAuthor.PHP_EOL;

/*
 * Output:
{
    "_id": "56db1a2f719580143000101d",
    "name": "Mike",
    "reputation": 111,
    "dateJoined": "2016-03-03"
}
 */

/*
 * Note: We do not need to load the author object separately for expansion 
 * features. It was featured to show that both objects are stored in their
 * own respective collections.
 */

//The found Post object has an author reference. Now when we expand it...

$foundPost->author = $foundPost->author->expand();

echo $foundPost.PHP_EOL;

/*
 * Output:
{
    "_id": "56db1b007195801430001022",
    "author": {
        "_id": "56db1b007195801430001021",
        "name": "Mike",
        "reputation": 111,
        "dateJoined": "2016-03-03"
    },
    "content": "Hey"
}
 */

//If autoExpand is on, we do not need to worry about setting the property

unset($foundPost);

$foundPost = Post::findOneById($postId);
$foundPost->setAutoExpand(true);
$foundPost->author->name = 'Mike N.';

echo $foundPost.PHP_EOL;

/*
 * The reference is lazily loaded and will only be expanded if referenced.
 * This also means var_dump-ing the object without fullDebug and getting the
 * the JSON for an object will expand all references and keep them expanded.
 */

//Updating is as normal

$foundPost->save();

//Or $foundPost->update();

unset($foundPost);
unset($foundAuthor);

$foundPost = Post::findOneById($postId);
echo $foundPost.PHP_EOL;

/*
 * Output:
{
    "_id": "56db1df2719580143000102c",
    "author": {
        "name": "Mike N.",
        "reputation": 111,
        "_id": "56db1df2719580143000102b"
    },
    "content": "Hey"
}
 */

//Note the new name in the reference.

$foundAuthor = Author::findOneById($authorId);

echo $foundAuthor.PHP_EOL;

/*
 * Output:
{
    "_id": "56db1f847195801430001039",
    "name": "Mike N.",
    "reputation": 111,
    "dateJoined": "2016-03-03"
}
 */

//Note the new name in the author object as well.


//General findOne:

//To findOne: (Finding method parameters same as MongoDb PHP Library);

echo Post::findOne(['author.name' => 'Mike N.']).PHP_EOL;

/*
 * Output:
{
    "_id": "56db1da6719580143000102a",
    "author": {
        "name": "Mike N.",
        "reputation": 111,
        "_id": "56db1da67195801430001029"
    },
    "content": "Hey"
}
 */

//General find:

//To find: (Finding method parameters same as MongoDb PHP Library);

$posts = Post::find(['author.name' => 'Mike']);

foreach($posts as $post) {
    echo $post.PHP_EOL;
}

/**
 * Output (if relevant records):
...
{
    "_id": "56d86bbc7195801660001377",
    "author": {
        "name": "Mike",
        "reputation": 111,
        "_id": "56d86bbc7195801660001376"
    },
    "content": "Hey"
}
{
    "_id": "56d86c6a7195801660001379",
    "author": {
        "name": "Mike",
        "reputation": 111,
        "_id": "56d86c6a7195801660001378"
    },
    "content": "Hey"
}
...
 * 
 */
