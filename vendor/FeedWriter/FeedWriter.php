<?php

define('RSS1', 'RSS 1.0', true);
define('RSS2', 'RSS 2.0', true);
define('ATOM', 'ATOM', true);

/**
* RSS Feeds - Feed Writer class
*
* Genarate RSS 1.0, RSS2.0 and ATOM Feed
*                             
* @package     RSS Feeds PHP
* @author      Anis uddin Ahmad <anisniit@gmail.com>
* @author      Guenther Mair <guenther.mair@hoslo.ch>
* @link        http://www.crowdedplace.com
*
* $Id: FeedWriter.php 6 2012-01-14 11:01:21Z gunny $
*/
class FeedWriter
{
  private $version       = null; 
  private $header        = "Content-type: application/xml; charset=utf-8";

  private $namespaces    = array();  // xml namespaces (used to link to images)
  private $channels      = array();  // collection of channel elements
  private $items         = array();  // collection of FeedItem objects
  private $CDATAEncoding = array();  // tag names to encode in CDATA blocks

  /**
  * Constructor
  * 
  * @param    constant    RSS1/RSS2/ATOM (defaults to RSS2)
  * @return   object      this
  */ 
  public function __construct($version = RSS2) {
    $this->version = $version;
                  
    // set default values for essential channel elements
    $this->setChannelElement('title', $version . ' Feed');
    $this->setChannelElement('link', 'http://www.crowdedplace.com');

    // set default namespaces
    switch ($this->version) {
      case RSS2:
        $this->setNamespace("http://purl.org/rss/1.0/modules/content/", "content");
        $this->setNamespace("http://wellformedweb.org/CommentAPI/", "wfw");
        break;
      case RSS1:
        $this->setNamespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#", "rdf");
        $this->setNamespace("http://purl.org/rss/1.0/");
        $this->setNamespace("http://purl.org/dc/elements/1.1/", "dc");
        break;
      case ATOM:
        $this->setNamespace("http://www.w3.org/2005/Atom");
        break;
    }

    // tag names to encode in CDATA blocks
    $this->setCDATAEncoding('description');
    $this->setCDATAEncoding('content:encoded');
    $this->setCDATAEncoding('summary');

    return $this;
  }

  /**
  * Set a tag to be encoded in CDATA blocks
  *
  * @access   public
  * @param    string      tag
  * @return   object      this
  */
  public function setCDATAEncoding($value) {
    $this->CDATAEncoding[] = $value;
    return $this;
  }

  /**
  * Set a namespace
  *
  * @access   public
  * @param    string      namespace URI
  * @param    string      xmlns short tag (optional)
  * @return   object      this
  */
  public function setNamespace($value, $xmlns = null) {
    $this->namespaces[] = 'xmlns'.(($xmlns !== null) ? ':'.$xmlns : '').'="'.$value.'"';
    return $this;
  }

  /**
  * Set the header
  *
  * @access   public
  * @return   object      this
  */
  public function setHeader($header) {
    $this->header = $header;
    return $this;
  }

  /**
  * Create a channel element
  *
  * @access   public
  * @param    string      name
  * @param    string      content
  * @param    string      attributes (optional)
  * @return   object      this
  */
  public function setChannelElement($name, $content, $attributes = null) {
    $this->channels[$name]['name'] = $name;
    $this->channels[$name]['content'] = $content;
    $this->channels[$name]['attributes'] = $attributes;
    return $this;
  }

  /**
  * Create multiple channel elements (no attributes)
  * 
  * @access   public
  * @param    array       channels in 'channelName' => 'channelContent' notation
  * @return   object      this
  */
  public function setChannelElements($elements) {
    if ( ! is_array($elements))
      return $this;

    foreach ($elements as $name => $content)
      $this->setChannelElement($name, $content);

    return $this;
  }
	
  /**
  * Genarate the actual RSS/ATOM file
  * 
  * @access   public
  * @param    boolean     directly print header and body (defaults to TRUE)
  * @return   array       feed array (header and body)
  */ 
  public function generateFeed($doEcho = TRUE) {
    $output = $this->printXMLHead() .
              $this->printHead() .
              $this->printChannels() .
              $this->printItems() .
              $this->printClosureTag();

    if ($doEcho === TRUE) {
      header($this->header);
      echo $output;
    }

    return array("header" => $this->header, "body" => $output);
  }

  /**
  * Create a new FeedItem
  * 
  * @access   public
  * @return   object      instance of FeedItem class
  */
  public function createNewItem() {
    $Item = new FeedItem($this->version);
    return $Item;
  }
  
  /**
  * Add a FeedItem to the main class
  * 
  * @access   public
  * @param    object      instance of FeedItem class
  * @return   object      this
  */
  public function addItem($feedItem) {
    $this->items[] = $feedItem;    
    return $this;
  }


  // ---------------------------------------------------------------------------

  /**
  * Set the 'title' channel element (wrapper method)
  * 
  * @access   public
  * @param    string      value of 'title' channel tag
  * @return   object      this
  */
  public function setTitle($title) {
    return $this->setChannelElement('title', $title);
  }
	
  /**
  * Set the 'description' channel element (wrapper method)
  * 
  * @access   public
  * @param    string      value of 'description' channel tag
  * @return   object      this
  */
  public function setDescription($desciption) {
    return $this->setChannelElement('description', $desciption);
  }
	
  /**
  * Set the 'link' channel element (wrapper method)
  * 
  * @access   public
  * @param    string      value of 'link' channel tag
  * @return   object      this
  */
  public function setLink($link) {
    return $this->setChannelElement('link', $link);
  }
	
  /**
  * Set the 'image' channel element (wrapper method)
  * 
  * @access   public
  * @param    string      title of image
  * @param    string      link url of the imahe
  * @param    string      path url of the image
  * @return   object      this
  */
  public function setImage($title, $link, $url) {
    return $this->setChannelElement('image', array('title' => $title, 'link' => $link, 'url' => $url));
  }
	
  /**
  * Set the 'about' channel element (RSS 1.0 only)
  * 
  * @access   public
  * @param    string      value of 'about' channel tag
  * @return   object      this
  */
  public function setChannelAbout($url) {
    $this->data['ChannelAbout'] = $url;    
    return $this;
  }
	
  /**
  * Genarates an UUID
  *
  * @access   public
  * @param    string      an optional prefix
  * @return   string      the formated uuid
  */
  public function uuid($key = null, $prefix = '') {
    $chars = md5(($key == null) ? uniqid(rand()) : $key);
    return $prefix . substr($chars,0,8) . '-' .
                     substr($chars,8,4) . '-' .
                     substr($chars,12,4) . '-' .
                     substr($chars,16,4) . '-' .
                     substr($chars,20,12);
  }


  // --------------------------------------------------------------------------

  /**
  * Print the xml opening tag
  * 
  * @access   private
  * @return   string      XML 1.0 UTF-8 header
  */
  private function printXMLHead() {
    return '<?xml version="1.0" encoding="utf-8"?>' . "\n";
  }

  /**
  * Print rss header and namespaces
  * 
  * @access   private
  * @return   string      RSS XML header
  */
  private function printHead() {
    switch ($this->version) {
      case RSS2:
        return '<rss version="2.0" '.implode(" ", $this->namespaces).'>' . PHP_EOL;
      case RSS1:
        return '<rdf:RDF '.implode(" ", $this->namespaces).'>' . PHP_EOL;
      case ATOM:
        return '<feed '.implode(" ", $this->namespaces).'>' . PHP_EOL;
    }
  }
	
  /**
  * Pring rss closing tags
  * 
  * @access   private
  * @return   string      RSS XML footer
  */
  private function printClosureTag() {
    switch ($this->version) {
      case RSS2:
        return '</channel>' . PHP_EOL . '</rss>';
      case RSS1:
        return '</rdf:RDF>';
      case ATOM:
        return '</feed>';
    }
  }

  /**
  * Create a single node from a tag (recursive)
  * 
  * @access   private
  * @param    string      name of the tag
  * @param    mixed       tag value as string or array of nested tags in 'tagName' => 'tagValue' notation
  * @param    array       attributes (if any) in 'attrName' => 'attrValue' notation
  * @return   string      formatted xml tag
  */
  private function makeNode($tagName, $tagContent, $attributes = null) {
    // setup attributes
    $attrText = '';
    if (is_array($attributes))
      foreach ($attributes as $key => $value) 
        $attrText .= " $key=\"$value\"";
    if (is_array($tagContent) && $this->version == RSS1)
      $attrText = ' rdf:parseType="Resource"';
    $attrText .= (in_array($tagName, $this->CDATAEncoding) && $this->version == ATOM) ? ' type="html"' : '';

    // setup content
    if ( empty($tagContent) ) {
      $nodeText = "<{$tagName}{$attrText} />";
    } else {
      $nodeText = (in_array($tagName, $this->CDATAEncoding)) ? "<{$tagName}{$attrText}><![CDATA[" : "<{$tagName}{$attrText}>";
      if (is_array($tagContent)) {
        foreach ($tagContent as $key => $value) 
          $nodeText .= $this->makeNode($key, $value);
      } else {
        $nodeText .= (in_array($tagName, $this->CDATAEncoding)) ? $tagContent : htmlentities($tagContent);
      }           
      $nodeText .= (in_array($tagName, $this->CDATAEncoding)) ? "]]></$tagName>" : "</$tagName>";
    }

    return $nodeText . PHP_EOL;
  }
	
  /**
  * Print channels
  *
  * @access   private
  * @return   string      xml string
  */
  private function printChannels() {
    // start channel tag
    switch ($this->version) {
      case RSS2: 
        $output = '<channel>' . PHP_EOL;        
        // channel will be closed at the EOF (see printClosureTag)
        break;
      case RSS1: 
        $output = (isset($this->data['ChannelAbout'])) ? "<channel rdf:about=\"{$this->data['ChannelAbout']}\">" : "<channel rdf:about=\"{$this->channels['link']}\">";
        break;
    }

    // Print channel items
    foreach ($this->channels as $channel) {
      if ($this->version == ATOM && $channel['name'] == 'link') {
        // ATOM prints link element as href attribute
        $output .= $this->makeNode($channel['name'], '', array('href' => $channel['content']));
        // add ID for ATOM
        $output .= $this->makeNode('id', $this->uuid($channel['content'], 'urn:uuid:'));
      } else {
        $output .= $this->makeNode($channel['name'], $channel['content'], $channel['attributes']);
      }
    }
		
    // RSS 1.0: uses a special <rdf:Seq> tag for channels
    if ($this->version == RSS1) {
      $output .= "<items>" . PHP_EOL . "<rdf:Seq>" . PHP_EOL;
      foreach ($this->items as $item) {
        $itemElements = $item->getElements();
        $output .= "<rdf:li resource=\"{$itemElements['link']['content']}\"/>" . PHP_EOL;
      }
      $output .= "</rdf:Seq>" . PHP_EOL . "</items>" . PHP_EOL . "</channel>" . PHP_EOL;
    }

    return $output;
  }

  /**
  * Print feed items
  * 
  * @access   private
  * @return   string      xml string
  */
  private function printItems() {
    $items = '';
    foreach ($this->items as $item) {
      $itemElements = $item->getElements();
      
      // RSS 1.0: the argument is printed as rdf:about attribute
      $items .= $this->startItem($itemElements['link']['content']);
      
      foreach ($itemElements as $element) 
        $items .= $this->makeNode($element['name'], $element['content'], $element['attributes']); 

      $items .= $this->endItem();
    }
    return $items;
  }
	
  /**
  * Start item tag
  * 
  * @access   private
  * @param    string      value of about tag to be used (only used by RSS 1.0)
  * @return   string      xml string
  */
  private function startItem($about = false) {
    switch ($this->version) {
      case RSS2:
        return '<item>' . PHP_EOL;
      case RSS1:
        if ($about)
          return '<item rdf:about="'.$about.'">' . PHP_EOL;
        else
          die('Link element not set, but required by RSS 1.0 (about attribute for all items).');
      case ATOM:
        return '<entry>' . PHP_EOL;
    }
  }

  /**
  * Close item tag
  * 
  * @access   private
  * @return   string      xml string
  */
  private function endItem() {
    switch ($this->version) {
      case RSS2:
      case RSS1:
        return '</item>' . PHP_EOL; 
      case ATOM:
        return "</entry>" . PHP_EOL;
    }
  }
} // end of class FeedWriter
 
/**
* RSS Feeds PHP - FeedItem class
* 
* Used for feed elements in FeedWriter class
*
* @package     RSS Feeds PHP
* @author      Anis uddin Ahmad <anisniit@gmail.com>
* @author      Guenther Mair <guenther.mair@hoslo.ch>
* @link        http://www.crowdedplace.com
*/
class FeedItem
{
  private $version;
  private $elements = array();  // collection of feed elements
	
  /**
  * Constructor 
  * 
  * @param    content     RSS1/RSS2/ATOM (defaults to RSS2)
  * @return   object      this
  */ 
  function __construct($version = RSS2) {
    $this->version = $version;
    return $this;
  }
	
  /**
  * Add an element
  * 
  * @access   public
  * @param    string      tag name of an element
  * @param    string      content of the tag
  * @param    array       attributes (if any) in 'attrName' => 'attrValue' notation
  * @return   object      this
  */
  public function addElement($name, $content, $attributes = null) {
    $this->elements[$name]['name'] = $name;
    $this->elements[$name]['content'] = $content;
    $this->elements[$name]['attributes'] = $attributes;
    return $this;
  }
  
  /**
  * Add multiple elements (no attributes)
  * 
  * @access   public
  * @param    array       array of elements in 'tagName' => 'tagContent' notation
  * @return   object      this
  */
  public function addElements($elements) {
    if ( ! is_array($elements))
      return;

    foreach ($elements as $name => $content)
      $this->addElement($name, $content);

    return $this;
  }
  
  /**
  * Return the collection of elements in this feed item
  * 
  * @access   public
  * @return   array
  */
  public function getElements() {
    return $this->elements;
  }


  // --------------------------------------------------------------------------
  
  /**
  * Set the 'description' element (wrapper method)
  * 
  * @access   public
  * @param    string      content of 'description' element
  * @return   object      this
  */
  public function setDescription($description) {
    return $this->addElement(($this->version == ATOM) ? 'summary' : 'description', $description);
  }

  /**
  * Set the 'title' element (wrapper method)
  *
  * @access   public
  * @param    string      content of 'title' element
  * @return   object      this
  */
  public function setTitle($title) {
    return $this->addElement('title', $title);
  }
  
  /**
  * Set the 'date' element (wrapper method)
  * 
  * @access   public
  * @param    string      content of 'date' element
  * @return   object      this
  */
  public function setDate($date) {
    if ( ! is_numeric($date))
      $date = strtotime($date);

    switch ($this->version) {
      case ATOM:
        return $this->addElement('updated', date(DATE_ATOM, $date));
      case RSS2:
        return $this->addElement('pubDate', date(DATE_RSS, $date));
      case RSS1:
        return $this->addElement('dc:date', date("Y-m-d", $date));
    }
  }
  
  /**
  * Set the 'link' element (wrapper method)
  * 
  * @access   public
  * @param    string      content of 'link' element
  * @return   object      this
  */
  public function setLink($link) {
    switch ($this->version) {
      case RSS2:
      case RSS1:
        return $this->addElement('link', $link);
      case ATOM:
        $this->addElement('link', '', array('href' => $link));
        $this->addElement('id', FeedWriter::uuid($link, 'urn:uuid:'));
        return $this;
    }
  }
  
  /**
  * Set the 'enclosure' element (wrapper method -- RSS 2.0 only)
  * 
  * @access   public
  * @param    string      type attribute
  * @param    string      url attribute
  * @param    string      length attribute
  * @return   object      this
  */
  public function setEnclosure($type, $url, $length) {
    if ($this->version == RSS2)
      return $this->addElement('enclosure', '', array('type' => $type, 'url' => $url, 'length' => $length));
    else
      return $this;
  }
} // end of class FeedItem
