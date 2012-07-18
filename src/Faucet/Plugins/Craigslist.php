<?php

namespace Faucet\Plugins;

class Craigslist extends AbstractPlugin implements PluginInterface
{
	/**
	 * Whether or not the URL is a posting
	 * 
	 * @return boolean
	 */
	public function isPost()
	{
		return  $this->select('span.postingidtext')->length === 1;
	}

	/**
	 * Parses the post and returns the data
	 * 
	 * @return array An array of data from the post
	 */
	public function getPost()
	{
		$post = array();
		$post['id'] = preg_replace('/[^0-9]/', '', $this->getParser()->getUrl());
		$post['title'] = $this->findOne('/html/head/title')->getText();
		$post['url'] = $this->getParser()->getUrl();
		$post['date'] = $this->selectOne('span.postingdate')->extractText('/ (\d.*)/');
		$post['date'] = new \DateTime((str_replace(', ', '', $post['date'])));

		foreach ($this->select('ul.blurbs li') as $i => $blurb) {
			if (false !== strpos($blurb->getText(), ':')) {
				list($name, $value) = explode(':', $blurb->getText(), 2);
			} else {
				$name = $i;
				$value = $blurb->getText();
			}

			$post['blurbs'][trim($name)] = trim($value);
		}

		// Now remove the blurbs so they don't show up in the post body
		$this->select('ul.blurbs')->remove();

		$post['email'] = $this->selectOne('span.returnemail a')->getText();
		$post['body'] = $this->selectOne('div#userbody')->getText();

		return $post;
	}

	/**
	 * Whether or not the URL is a directory page
	 * 
	 * @return boolean
	 */
	public function isDirectory()
	{
		return  $this->select('h4.ban')->length > 0;
	}

	/**
	 * Parses out the postings from a craigslist directory page
	 * 
	 * @return array An array of postings
	 */
	public function getPostings($criteria = -1)
	{
		if ($criteria instanceof \DateTime) {
			$elements = $this->find('//p[preceding-sibling::h4[1][. = "' . $criteria->format('D M d') . '"]]');
		} else {
			$elements = $this->select(array('h4.ban', 'p.row'), $criteria);
		}

		$postings = array();
		foreach ($elements as $element) {
			if ($element->getTag() === 'h4') {
				// Section heading
				$date = $this->convertDate($element->getText());
				continue;
			}

			$link = $element->findOne('a');
			$location = trim(str_replace(
				array('(', ')'), 
				'', 
				$element->selectOne('span.itempn')->getText()
			));
			if (empty($location)) {
				$location = null;
			}

			$id = preg_replace('/[^0-9]/', '', $link->href);

			$postings[$id] = array(
				'id' => $id,
				'title' => $link->getText(),
				'url' => $link->href,
				'location' => $location,
				'posted' => $date
			);
		}

		return $postings;
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'craigslist';
	}

	/**
	 * Converts the craigslist date into a DateTime object
	 * 
	 * @param  string $date The craigslist date
	 * 
	 * @return object DateTime
	 */
	private function convertDate($date)
	{
		$date = preg_match('/[a-z]+ ([a-z]+) ([0-9]+)/i', $date, $matches);
		$month = $matches[1];
		$day = $matches[2];

		$month = str_replace(
			array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'),
			array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'),
			$month
		);

		return new \DateTime(date('Y') . '-' . $month . '-' . $day);
	}
}