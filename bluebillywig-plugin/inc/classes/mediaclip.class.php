<?php
namespace BlueBillywig\Classes;
use BlueBillywig;
use SimpleXMLElement;

define('PROPERTY_DEFAULTS', array(
    'id' => 0,
    'title' => 'Unnamed Video',
    'description' => '',
    'author' => '',
    'copyright' => '',
    'cat' => array(),
    'status' => 'published'
));

class Mediaclip
{
    public $id;
    public $title;
    public $description;
    public $author;
    public $copyright;
    public $tags;
    public $status;
    public $metadata;

    private $METADATA_PROPERTY_DICTIONARY;

    public function __construct($id = 0, $title = null, $description = null, $author = null, $copyright = null, $tags = array(), $status = 1)
    {
        $this->id =             $id;
        $this->title =          $title       ?? PROPERTY_DEFAULTS['id'];
        $this->description =    $description ?? PROPERTY_DEFAULTS['description'];
        $this->author =         $author      ?? PROPERTY_DEFAULTS['author'];
        $this->copyright =      $copyright   ?? PROPERTY_DEFAULTS['copyright'];
        $this->tags =           $tags        ?? PROPERTY_DEFAULTS['tags'];
        $this->status =         $status      ?? PROPERTY_DEFAULTS['status'];
        $this->metadata = array();

        // Links database propertiy names with object properties
        $this->METADATA_PROPERTY_DICTIONARY = array(
            'id' => &$this->id,
            'title' => &$this->title,
            'description' => &$this->description,
            'author' => &$this->author,
            'copyright' => &$this->copyright,
            'copyrightSort' => &$this->copyright,
            'cat' => &$this->tags,
            'status' => &$this->status
        );
    }

    /** Loads a mediaclip from the Blue Billywig OVP
     * @param number $id id of mediaclip in the Blue Billywig OVP
     * @return Mediaclip instance of a mediaclip with metadata loaded from the Blue Billywig OVP
     */
    public static function from_remote($id)
    {
        if(is_numeric($id) && $id > 0)
        {
            $instance = new self($id);
            $instance->load_metadata();

            return $instance;
        }
    }

    public function set_metadata($properties = array())
    {
        if(!isset($properties))
            return false;

        // Set Object properties
        foreach($this->METADATA_PROPERTY_DICTIONARY as $propertyKey => $propertyValue)
        {
            if(array_key_exists($propertyKey, $properties))
            {
                if($propertyKey === 'cat')
                    $this->METADATA_PROPERTY_DICTIONARY[$propertyKey] = tags_as_array($properties[$propertyKey]);
                else
                    $this->METADATA_PROPERTY_DICTIONARY[$propertyKey] = $properties[$propertyKey];
            }
        }

        // Set metadata collection
        foreach($properties as $propertyKey => $propertyValue)
        {
            $this->metadata[$propertyKey] = $propertyValue;
        }
    }

    public function load_metadata()
    {         
        $properties = array(
			'query' => 'type:mediaclip AND id:' . $this->id,
			'limit' => '1'
		);
        $search_result = json_decode(BlueBillywig::instance()->get_rpc()->json('search', null, $properties), true);
        $metadata = $search_result['count'] > 0 ? $search_result['items'][0] : NULL;

        if($metadata === NULL)
        {
            return 'Could not find mediaclip with id ' . $this->id;
        }

        $this->id =             $metadata['id'];
        $this->title =          $metadata['title']          ??  PROPERTY_DEFAULTS['title'];
        $this->description =    $metadata['description']    ??  PROPERTY_DEFAULTS['description'];
        $this->author =         $metadata['author']         ??  PROPERTY_DEFAULTS['author'];
        $this->copyright =      $metadata['copyrightSort']  ??  PROPERTY_DEFAULTS['copyright'];
        $this->tags =           $metadata['cat']            ??  PROPERTY_DEFAULTS['cat'];
        $this->status =         $metadata['status']         ??  PROPERTY_DEFAULTS['status'];
        $this->metadata =       $metadata;

        return $metadata;
    }

    public function save_metadata()
    {
        $properties = array(
            'xml' => $this->get_metadata_as_xml()
        );
        $rpc = BlueBillywig::instance()->get_rpc();
        $response = $rpc->doAction('mediaclip', 'put', $properties);

        return $response;
    }

    public function get_preview_url()
    {
        $host = BlueBillywig::instance()->get_rpc()->host;
        return $host . '/view/default/' . $this->id . '.html';
    }

    public function is_transcoding()
    {
        return (array_key_exists('hasRunningJobs_string', $this->metadata) && $this->metadata['hasRunningJobs_string'] === 'true');
        // if(array_key_exists('assets', $this->metadata))
        // {
        //     if($this->metadata['assets'] === 'null')
        //         return true;
        //     if(is_array($this->metadata['assets']) && count($this->metadata['assets']) == 0)
        //         return true;
        // }
        // return false;
    }

    private function get_metadata_as_xml()
    {
        $xml = new SimpleXMLElement('<media-clip/>');
        $xml->addAttribute('id', $this->id);
        $xml->addAttribute('title', $this->title);
        $xml->addAttribute('status', $this->status);

        $metadata = array(
            'description' => $this->description,
            'author' => $this->author,
            'copyright' => $this->copyright,
            'cat' => $this->tags,
        );

        foreach($metadata as $metaKey => $metaValue){
            if(is_array($metaValue) && $metaKey === 'cat'){
                $catTree = $xml->addChild('categorization')->addChild('category-tree');
                $catTree->addAttribute('type', 'keywords');
                foreach($metaValue as $tag){
                    $catTree->addChild('category')->addAttribute('name', $tag);
                }
            }else{
                $xml->addChild($metaKey, $metaValue);
            }
        }

        return $xml->asXML();
    }
}

?>