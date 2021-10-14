# Codeigniter Template Parser Engine

## Installation
1. Download this project [a link](https://github.com/samsabz/CodeigniterTemplateEngine/archive/refs/heads/main.zip)
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
