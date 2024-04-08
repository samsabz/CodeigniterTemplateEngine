# Codeigniter Template Parser Engine

## Installation
1. [Download this project](https://github.com/samsabz/CodeigniterTemplateEngine/archive/refs/heads/main.zip)
2. Copy libraries/Theme.php to your application/libraries/ folder
3. Copy config/theme.php to your application/config/ folder
4. Create the folder if not exists: application/cach
5. Set application/cache writable.

## Initialization
Like other libraries in CodeIgniter, the Theme library class is initialized in your controller using the
``$this->load->library()`` method:
``$this->load->library('theme');``
Or you can autoload the library in autoload.php
Once loaded, the Theme library object will be available using: ``$this->theme``

## Parsing Views/Templates
You can use the parse() method to parse (or render) your views or templates. The syntax is:
``$this->theme->parse( $view, $data = array(), $return = FALSE );``
The first parameter contains the name of the view file, the second parameter contains an associative array
of data to be made available in the template, and the third parameter specify whether to return the parsed
string.
Example:
```
$data = array(
    'products' => array(
        array( 'title' => 'Shirts', 'link' => '/shirts' ),
        array( 'title' => 'Trousers', 'link' => '/trousers' ),
        array( 'title' => 'Shoes', 'link' => '/shoes' ),
        array( 'title' => 'Belts', 'link' => '/belts' ),
    ),
);
```
``$this->theme->parse( 'products/list', $data );``
There is no need to “echo” or do something with the data returned by ``$this->theme->parse()``. It is
automatically passed to the output class to be sent to the browser. However, if you do want the data
returned instead of sent to the output class you can pass TRUE (boolean) as the third parameter:
```$string = $this->theme->parse('products/list', $data, TRUE);```

## Example
foreach:
```From the above example, we are creating a template file at products/list.php:```
``
<ul>
{foreach products as product}
<li><a href="{product[link]}">{product[title]}</a></li>
{/foreach}
</ul>
``
The html output will be:
``
<ul>
<li><a href="/shirts">Shirts</a></li>
<li><a href="/trousers">Trousers</a></li>
<li><a href="/shoes">Shoes</a></li>
<li><a href="/belts">Belts</a></li>
</ul>
``
To achieve the same output by php code:
``
<ul>
<?php foreach ( $products as $product ) : ?>
<li><a href="<?php echo $product['link'] ; ?>"><?php echo $product['title'] ; ?></a></li>
<?php endforeach ; ?>
</ul>
``
Use foreach for associative array
``
<ul>
{foreach options as item = value}
<li>{item} => {value}</li>
{/foreach}
</ul>
``

if/elseif/else:
``
{if product[active]}
... do something ...
{elif product[published]}
... some other thing ...
{/if}
``
echo:
All variables and methods will be automatically preceded by echo:
``
{somevar}
<?php echo $somevar ; ?>
{some_array[assoc_key]}
<?php echo $some_array['assoc_key'] ; ?>
{another_array[$key]}
<?php echo $another_array[$key] ; ?>
{date('Y-m-d H:i:s', now)}
<?php echo date ( 'Y-m-d H:i:s' , $now ) ; ?>
{time()}
<?php echo time ( ) ; ?>
{fname . lname}
<?php echo $fname . $lname ; ?>
{books->get_by_author(author)->first()->title}
<?php echo $books -> get_by_author( $author ) -> first() -> title ; ?>
``
constants:
To use constants, precede the constant with #:
``
{if defined('APP_VERSION') && #APP_VERSION > 2.0}
... do something ...
{/if}
``
