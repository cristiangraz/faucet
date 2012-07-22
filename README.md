Faucet
=========

Faucet is plugin-driven web scraping library written in PHP.

Getting started
---------------

## Install using Composer

Add ``cristiangraz/faucet`` in your ``composer.json`` file:

	{
		"require": {
			"cristiangraz/faucet": "*"
		}
	}

Usage
-----

### Initial setup

```php
<?php

use Faucet\Client;

use Faucet\Plugins\Schema;

require_once(__DIR__ . '/../vendor/autoload.php');

$client = new Client;
$response = $client->get('http://www.example.org/');

if (false === $response->isSuccessful()) {
	echo 'Request Failed. Response: ' . $response->getStatusCode();
	exit;
}

// Scraping code here

```

### Finding elements on the page

```php

// Using xpath: find() or findOne()
$links = $client->find('//div[@id="wrapper"]/a');

// Using css selectors: select() or selectOne()
$links = $client->select('div#wrapper a');

// Links is a Faucet\Dom\Elements object, but it implements the Iterator interface
foreach ($links as $link) {
	// $link is a Faucet\Dom\Element object
	echo '<a href="' . $link->getAttribute('href') . '">' . $link->getText() . '</a>';

	// You can also access attributes as object properties
	echo '<a href="' . $link->href . '">' . $link->getText() . '</a>';
}

```

### Using validators

Validators allow you to validate attributes of the nodes using ``Zend\Validators``

```php

use Zend\Validator\Regex;

// Instantiate client ...


// Faucet\Dom\Elements has a validate() method that takes the attribute and an array of validators
$links = $client->find('//div[@id="wrapper"]/a')
				->validate('href', array(
					new Zend\Validate\Regex('#^https?://#')
				));

// Can also validate text using _text
$links = $client->find('//div[@id="wrapper"]/a')
				->validate('_text', array(
					new Zend\Validate\Regex('#^https?://#')
				));

```

### Using Plugins

Faucet comes with a plugin architecture that makes scraping much faster/easier, and keeps the core library simple. The initial plugins are:

 - ``HTML``: Convenience plugin for accessing links, page title, canonical url, etc
 - ``Schema``: Parses schema.org markup
 - ``SEO``: Provides information on follow/nofollow links, whether or not a page is indexable, etc
 - ``OpenGraph``: Parses Facebook Opengraph tags

 Plugins must contain an alias via a ``getAlias()`` method. You can then grab them like this:

 ```php
 $opengraph = $client->getPlugin('facebook.opengraph');

 echo $opengraph->getTag('og.video.width');
 ```

 Each plugin has access to the ``Faucet\Dom\Parser``, so all of the logic to parse common types of pages/elements can be contained within your plugin. To use your plugin, you have to register it with the Faucet Client using ``registerPlugins()``

The schema plugin:

```php

$client->registerPlugins(array(
	new Schema
));

$client->get('http://example.com/some/recipe.html');

$schema = $client->getPlugin('schema');

print_r($schema->getSchemas());
```


### Site "plugins"

 Sites are a type of plugin, but are different from normal plugins in that they are specific to certain sites only. Here's an example of how you would scrape Craigslist using the craigslist site:

 ```php

client = new Client;

// Register the plugin
$client->registerPlugins(array(
	new Craigslist
));

$client->get('http://phoenix.craigslist.org/cpg/');

$c = $client->getPlugin('craigslist');

$posts = $c->getPosts();

// Or for only yesterday's posts
$posts = $c->getPostings(new \DateTime('yesterday'));

foreach ($posts as $post) {
	$client->get($post['url']);

	// This works because the plugin always has access to Faucet\Dom\Parser object
	$post = $c->getPost();

	print_r($post);
}

 ```

 ## And Selectors

 Sometimes you need to grab section headings and elements, and associate each element with the correct heading that they are after. This is how the Craigslist site scraper works. Here's an example of how to use:

 ```php

// Creates this Xpath: //h4[@class="ban"] | //p[@class="row"]
// Will select BOTH h4.ban nodes and p.row nodes
$elements = $this->select(array('h4.ban', 'p.row'));

$results = array();
foreach ($elements as $element) {
	if ($element->getTag() === 'h4') {
		// It's a heading.
		$key = $element->getText();

		continue;
	}

	// Key now groups your elements by the h4 header
	$results[$key][] = array(
		'text' => $element->getText(),
		'href' => $client->getAbsoluteUrl($element->href)
	);
 }

 ### Using Filters

 If you want to filter out your data as you grab it, you can use Zend\Filters

$elements = $this->select('p.title');

foreach ($elements as $element) {
	echo $element->getAttribute('_text', array(
		new Zend\Filter\StringToLower,
		new Zend\Filter\StripNewlines
	));
}


### Removing elements from the DOM

If you are scraping a site and want the site's html, but first need to strip out all meta tags (for example)

```php

$client->get('http://www.example.com');

// find() returns Faucet\Dom\Elements object, with access to the parser
// remove() removes the element(s) from the parser object
$client->find('/html/head/meta')->remove();

// Grabs the DOM after any changes
echo $client->getContents();

```