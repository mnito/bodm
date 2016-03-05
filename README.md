# BODM (BSON Object Document Mapper)

BODM is an object document mapper (ODM) for use with MongoDB and the MongoDB PHP Library. BODM supports embedded documents and complex document referencing, with a simple way of defining such references.


## Basics

BODM basic features:

 * Document referencing with automatic reference compression
 * Robust deep saving of objects and their references (and the references of the references!)
 * Manual or automatic reference expansion


To define references:

```php
class Post extends ActiveModel
{
    protected $references = ['author'];
}
```

To define which attributes should be compressed in general and for respective references:

```php
class Author extends ActiveModel
{
    protected $compressed = [
    	'name', 
        'Post' => ['reputation'], 
        'Bar' => ['reputation', '__not' => ['name']]];
    ];
}
```

BODM can easily handle complex saves which includes inserts and updates at all levels of the object and its references.

To learn more about what BODM can do, see the tutorials folder.

## Installation

BODM requires PHP 7.0.0 or greater. The MongoDB extension (>= v 1.1.2) is also required along with the MongoDB PHP Library (>= 1.0.0).

Composer is the preferred way to get the required dependencies.

Composer Installation Example:

* Install Composer if not installed already.
* Download BODM's files.
* Navigate to the BODM's directory using the command line.
* Use Composer to create the autoloader and download dependencies. (Run the following command within the directory.)

```cmd
composer install
```

Composer Dependencies:

* php: >=7.0.0
* ext-mongodb: ^1.1.2
* mongodb/mongodb: ^1.0.0

Otherwise, you will need to manually acquire the dependencies and create an autoloader.

## License

BODM is released under the Apache License, Version 2.0.

## Author

Michael P. Nitowski <[mpnitowski@gmail.com](mailto:mpnitowski@gmail.com)> (Twitter: [@mikenitowski](https://twitter.com/mikenitowski))

## Contributing

Contributing and forking is encouraged. Bug reports and feature requests are helpful along with direct contributions.

Contribution Ideas:
* "Referenced By" Feature: With this feature, when an object that is referenced by other objects is updated outside the context of other objects, the changes are automatically pushed to the reference objects. (This is an admittedly complex task with different ways of handling it, hence why it was not handled in version 1. By nature of BODM, in version 1, if the references are expanded within the containing object, the changes will be updated if a save is called on the containing object.)
* Unit tests
* Enhanced documentation
* Anything thinkable!
